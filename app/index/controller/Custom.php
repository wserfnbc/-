<?php

namespace app\index\controller;

use think\admin\Controller;
use think\facade\Db;

/**
 * //自定义标签/函数库
 * @package app\index\controller
 */
class Custom extends Controller
{
    /**
     * 获得网站配置
     */
    public function getConfig()
    {
        //显示网站的配置
        $sys = $this->app->db->name('SystemConfig')->where(['type' => 'base'])->column('name,value');
        $db = [];
        foreach ($sys as $key => $val) {
            $db[$val['name']] = $val['value'];
        }
        $config['site_icon'] = $db['site_icon'];                        //网站小图标
        $config['name'] = $db['site_name'];                         //网站名称
        $config['host'] = $db['site_host'];                      //网站域名
        $config['pic'] = $db['website_logo'];                       //网站LOGO
        $config['footer_pic'] = $db['website_bottom_logo'];                       //网站页脚LOGO
        $config['content'] = $db['site_description'];               //描述
        $config['keyword'] = $db['site_keywords'];                  //关键字
        $config['beian'] = $db['miitbeian'];                        //ICP备案号
        $config['copy'] = $db['site_copy'];                         //版权声明
        $config['Nsbeian'] = $db['beian'];                          //网安备案号
        $config['tel'] = $db['contact_tel'];                        //联系电话
        $config['email'] = $db['contact_email'];                    //联系邮箱
        $config['address'] = $db['contact_address'];                //联系地址
        $config['page_num'] = $db['page_num'];                      //列表页每页显示数量

        return $config;
    }

    /**
     * 获得轮播组图
     */
    public function sliderPictures($dataId)
    {
        $value = $this->app->db->name('SystemData')->where('id', $dataId)->column('value');      //where里的id去查看system_data数据表里name为sliderHomePage所在的id
        return unserialize($value[0]);
    }

    /**
     * 获得指定广告位的信息
     */
    public function getAdPosition($position_id)
    {
        return $this->app->db->name('DataAdPosition')
            ->where(['id' => $position_id, 'status' => 1, 'is_deleted' => 0])
            ->find();     //$position_id 可以去后台的广告位管理里查看ID
    }

    /**
     * 获得指定广告位所有的广告信息列表
     */
    public function getAllAd($position_id)
    {
        return $this->app->db->name('DataAd')->alias('a')->field("a.*")
            ->join("data_ad_position p", "p.id = a.position_id", "LEFT")
            ->where(['a.position_id' => $position_id, 'a.status' => 1, 'a.is_deleted' => 0, 'p.status' => 1, 'p.is_deleted' => 0])
            ->select()->toArray();     //$position_id 可以去后台的广告位管理里查看ID
    }

    /**
     * 获得指定广告信息列表
     */
    public function getAd($poster_id)
    {
        return $this->app->db->name('DataAd')->alias('a')->field("a.*")
            ->join("data_ad_position p", "p.id = a.position_id", "LEFT")
            ->where(['a.id' => $poster_id, 'a.status' => 1, 'a.is_deleted' => 0, 'p.status' => 1, 'p.is_deleted' => 0])
            ->find();     //$poster_id 可以去后台的广告信息管理里查看ID
    }

    /**
     * 获得所有栏目及所有下级分类
     * @param string $catId 栏目ID
     * @param string $nav_id 导航ID
     */
    public function getAllCat()
    {
        //上级分类
        $allCat = $this->app->db->name('DataArticleCate')
            ->where(['parent_id' => 0, 'status' => 1, 'is_nav' => 1])->order("sort desc,id asc")
            ->select()->toArray();
        foreach ($allCat as $key => $value) {
            //二级分类
            //            if ($value['is_page'] == 1){
            //                $allCat[$key]['url'] = "/index/article/list/id/".$value['id'].".html";
            //            }
            if ($value['force_link']) {
                //判断如果后台设置的跳转链接
                $allCat[$key]['url'] = $value['force_link'];
            } else {
                if (empty($value['link_name'])) {
                    $value['link_name'] = "List";
                }
                $allCat[$key]['url'] = "/site-" . $value['link_name'] . "-" . $value['id'] . ".html";
            }
            $allCat[$key]['listCount'] = $this->app->db->name('DataArticleCate')->where(['parent_id' => $value['id'], 'status' => 1, 'is_nav' => 1])->count();
            $allCat[$key]['childList'] = $this->app->db->name('DataArticleCate')
                ->where(['parent_id' => $value['id'], 'status' => 1, 'is_nav' => 1])->order("sort desc,id asc")
                ->select()->toArray();

            foreach ($allCat[$key]['childList'] as $k => $v) {

                //三级分类
                if ($v['force_link']) {
                    //判断如果后台设置的跳转链接
                    $allCat[$key]['childList'][$k]['url'] = $v['force_link'];
                } else {
                    if (empty($v['link_name'])) {
                        $v['link_name'] = "List";
                    }
                    $allCat[$key]['childList'][$k]['url'] = "/site-" . $v['link_name'] . "-" . $v['id'] . ".html";
                }

                $allCat[$key]['childList'][$k]['listCount'] = $this->app->db->name('DataArticleCate')->where(['parent_id' => $v['id'], 'status' => 1, 'is_nav' => 1])->count();
                $allCat[$key]['childList'][$k]['childList'] = $this->app->db->name('DataArticleCate')
                    ->where(['parent_id' => $v['id'], 'status' => 1, 'is_nav' => 1])->order("sort desc,id asc")
                    ->select()->toArray();
                foreach ($allCat[$key]['childList'][$k]['childList'] as $kc => $vc) {
                    //增加url
                    if ($vc['force_link']) {
                        //判断如果后台设置的跳转链接
                        $allCat[$key]['childList'][$k]['childList'][$kc]['url'] = $vc['force_link'];
                    } else {
                        if (empty($vc['link_name'])) {
                            $vc['link_name'] = "List";
                        }
                        $allCat[$key]['childList'][$k]['childList'][$kc]['url'] = "/site-" . $vc['link_name'] . "-" . $vc['id'] . ".html";
                    }
                }

                foreach ($allCat[$key]['childList'][$k]['childList'] as $k4 => $v4) {

                    //四级栏目
                    if ($v4['force_link']) {
                        //判断如果后台设置的跳转链接
                        $allCat[$key]['childList'][$k]['childList'][$k4]['url'] = $v4['force_link'];
                    } else {
                        if (empty($v4['link_name'])) {
                            $v4['link_name'] = "List";
                        }
                        $allCat[$key]['childList'][$k]['childList'][$k4]['url'] = "/site-" . $v4['link_name'] . "-" . $v4['id'] . ".html";
                    }

                    $allCat[$key]['childList'][$k]['childList'][$k4]['listCount'] = $this->app->db->name('DataArticleCate')->where(['parent_id' => $v4['id'], 'status' => 1, 'is_nav' => 1])->count();
                    $allCat[$key]['childList'][$k]['childList'][$k4]['childList'] = $this->app->db->name('DataArticleCate')
                        ->where(['parent_id' => $v4['id'], 'status' => 1, 'is_nav' => 1])->order("sort desc,id asc")
                        ->select()->toArray();
                    foreach ($allCat[$key]['childList'][$k]['childList'][$k4]['childList'] as $kc4 => $vc4) {
                        //增加url
                        if ($vc4['force_link']) {
                            //判断如果后台设置的跳转链接
                            $allCat[$key]['childList'][$k]['childList'][$k4]['childList'][$kc4]['url'] = $vc4['force_link'];
                        } else {
                            if (empty($vc['link_name'])) {
                                $vc['link_name'] = "List";
                            }
                            $allCat[$key]['childList'][$k]['childList'][$k4]['childList'][$kc4]['url'] = "/site-" . $vc4['link_name'] . "-" . $vc4['id'] . ".html";
                        }
                    }
                }
            }
        }
        //        dump($allCat);die();
        return $allCat;
    }

    /**
     * 获得指定栏目所在的顶级栏目及所有下级分类，不要在四级分类里使用该方法。
     * @param string $catId 栏目ID
     * @param string $nav_id 导航ID
     */
    public function getCatList($catId, $nav_id = 0)
    {
        //上级分类ID
        $parentId1 = $this->app->db->name('DataArticleCate')
            ->where(['id' => $catId, 'status' => 1])->order("sort desc,id asc")
            ->value("parent_id");

        //获得顶级栏目ID
        if (!empty($parentId1)) {
            $parentId2 = $this->app->db->name('DataArticleCate')
                ->where(['id' => $parentId1, 'status' => 1])->order("sort desc,id asc")
                ->value("parent_id");
            if (!empty($parentId2)) {
                $parentId = $parentId2;
            } else {
                $parentId = $parentId1;
            }
        } else {
            $parentId = $catId;
        }

        //顶级栏目信息
        $topCateInfo =  $this->app->db->name('DataArticleCate')
            ->where(['id' => $parentId, 'status' => 1])->find();

        //二级分类
        $topCateList = $this->app->db->name('DataArticleCate')
            ->where(['parent_id' => $topCateInfo['id'], 'status' => 1])->order("sort desc,id asc")->order("sort desc,id asc")
            ->select()->toArray();
        foreach ($topCateList as $key => $value) {
            //三级分类
            $topCateList[$key]['url'] = "/site-" . $value['link_name'] . "-" . $value['id'] . ".html";
            $topCateList[$key]['listCount'] = $this->app->db->name('DataArticleCate')
                ->where(['parent_id' => $value['id'], 'status' => 1])
                ->count();
            $topCateList[$key]['childList'] = $this->app->db->name('DataArticleCate')
                ->where(['parent_id' => $value['id'], 'status' => 1])->order("sort desc,id asc")->order("sort desc,id asc")
                ->select()->toArray();
            foreach ($topCateList[$key]['childList'] as $k => $v) {
                if (empty($v['link_name'])) {
                    $v['link_name'] = "List";
                }
                $topCateList[$key]['childList'][$k]['url'] = "/site-" . $v['link_name'] . "-" . $v['id'] . ".html";
            }
        }

        $return = ['topCate' => $topCateInfo, 'catList' => $topCateList];

        return $return;
    }

    /**
     * 获得指定栏目的所有下级分类，不要在四级分类里使用该方法
     * @param string $catId 栏目ID
     * @param int $num 栏目ID
     */
    public function getCatChildList($catId, $num = 0)
    {
        if ($num == 0) {
            $catList = Db::name('DataArticleCate')
                ->where(['parent_id' => $catId, 'status' => 1])->order("sort desc,id asc")
                ->select()->toArray();
        } else {
            $catList = Db::name('DataArticleCate')
                ->where(['parent_id' => $catId, 'status' => 1])->order("sort desc,id asc")->limit($num)
                ->select()->toArray();
        }

        foreach ($catList as $key => $value) {
            if (!empty($value['force_link'])) {
                //如果存在跳转链接
                $catList[$key]['url'] = $value['force_link'];
            } else {
                //否则使用站内链接
                if (empty($value['link_name'])) {
                    $value['link_name'] = "List";
                }
                $catList[$key]['url']  = "/site-" . $value['link_name'] . "-" . $value['id'] . ".html";
            }
        }

        return $catList;
    }

    /**
     * 获得指定栏目信息
     * @param string $catId 栏目ID
     */
    public function getCatInfo($catId)
    {
        return $this->app->db->name('DataArticleCate')
            ->where(['id' => $catId, 'status' => 1])
            ->find();
    }

    /**
     * 获得指定栏目下所有次级分类图文列表信息，要求所有下级分类必须为同一模型
     * @param string $catId 栏目ID
     * @param string $pageSize 每页数量
     * @param string $pageName 分页名称
     * @param string $sort 排序方式x
     */
    public function getCatArticleAllPage(int $catId = 0, ?int $pageSize = null, string $sort = 'DESC'): array
    {
        $allCatIds = $this->getAllChildCatIds($catId);
        if (empty($allCatIds)) {
            $allCatIds = [$catId];
        }
        
        $contentTemplateId = $this->app->db->name('DataArticleCate')
            ->whereIn("id", $allCatIds)
            ->group("content_template_id")
            ->value("content_template_id");
         
        if (empty($contentTemplateId)) {
            return ['list' => [], 'page' => '', 'total' => 0];
        }
        
        $tableName = $this->app->db->name('DataTemplate')
            ->where(["id" => $contentTemplateId])
            ->value("table_name");
        
        if (empty($tableName)) {
            return ['list' => [], 'page' => '', 'total' => 0];
        }
        
        $baseQuery = $this->app->db->name($tableName)
            ->where(["status" => 1])
            ->whereIn("cat_id", $allCatIds);
        
        $total = $baseQuery->count();
        
        $query = clone $baseQuery;
        $query->order("sort " . $sort . ",id " . $sort);
        
        if (empty($pageSize)) {
            $list = $query->select()->toArray();
            $page = '';
        } else {
            $list = $query->paginate($pageSize, false, ['query' => request()->param()])
                ->each(function ($item, $key) {
                    return $item;
                });
            $page = $list->render();
        }
    
        return ['list' => $list, 'page' => $page, 'total' => $total];
    }
      /**
     * 获得指定栏目下所有次级分类图文列表信息，要求所有下级分类必须为同一模型
     * @param string $catId 栏目ID
     * @param int $num 限制数量，0为不限制
     * @param int $is_best 是否只看推荐，0为全部，1为推荐
     */
    public function getCatArticleAll(int $catId = 0, int $num = 0, int $is_best = 0): array
    {
        $allCatIds = $this->getAllChildCatIds($catId);
        if (empty($allCatIds)) {
            $allCatIds = [$catId];
        }
        
        $contentTemplateId = $this->app->db->name('DataArticleCate')
            ->whereIn("id", $allCatIds)
            ->group("content_template_id")
            ->value("content_template_id");
         
        if (empty($contentTemplateId)) {
            return [];
        }
        
        $tableName = $this->app->db->name('DataTemplate')
            ->where(["id" => $contentTemplateId])
            ->value("table_name");
        
        if (empty($tableName)) {
            return [];
        }
        
        $where = ["status" => 1];
        if ($is_best) {
            $where['is_best'] = $is_best;
        }
        
        $baseQuery = $this->app->db->name($tableName)
            ->where($where)
            ->whereIn("cat_id", $allCatIds);
        
        $query = clone $baseQuery;
        $query->order("sort DESC,id DESC");
        
        if ($num > 0) {
            $query->limit($num);
        }
        
        return $query->select()->toArray();
    }
   /**
    * 递归获取所有子分类ID（包括子分类的子分类）
    * @param int $catId 分类ID
    * @return array 所有子分类ID数组
    */
   public function getAllChildCatIds($catId = 0)
   {
       $childIds = [];
       
       // 查询直接子分类
       $directChilds = $this->app->db->name('DataArticleCate')
           ->where(["parent_id" => $catId])
           ->column('id');
           
       if (!empty($directChilds)) {
           $childIds = $directChilds;
           
           // 递归查询子分类的子分类
           foreach ($directChilds as $childId) {
               $grandChilds = $this->getAllChildCatIds($childId);
               if (!empty($grandChilds)) {
                   $childIds = array_merge($childIds, $grandChilds);
               }
           }
       }
       
       return $childIds;
   }

    /**
     * 获得指定栏目图文列表信息
     * @param string $catId 栏目ID
     * @param string $num 查询条数
     * @param string $is_best 是否推荐
     */
    public function getCatArticle($catId = 0, $num = 0, $is_best = 0)
    {
        //获得栏目对应的数据表
        $contentTemplateId = $this->app->db->name('DataArticleCate')->where(["id" => $catId])->value("content_template_id");
        $linkName = $this->app->db->name('DataArticleCate')->where(["id" => $catId])->value("link_name");    //获得列表页的静态链接名称
        if (empty($linkName)) {
            $linkName = "List";
        }
        $tableName = $this->app->db->name('DataTemplate')->where(["id" => $contentTemplateId])->value("table_name");        //内容模板获取数据表名

        //自定义修改，如果给栏目ID为0，直接查默认模型表
        if (!empty($catId)) {
            $where['cat_id'] = $catId;
        } else {
            $tableName = "data_article_show_default";
        }

        if (empty($tableName)) {
            return false;
        } else {
            $tableName = strtolower($tableName);    //转小写
        }

        if($is_best){
        $where['is_best'] = $is_best;
            
        }
        $where['status'] = 1;

        if (!empty($num)) {
            $list = $this->app->db->name($tableName)
                ->where($where)
                ->limit($num)
                ->order("is_best desc,sort desc,id desc")->select()->toArray();
        } else {
            $list = $this->app->db->name($tableName)
                ->where($where)
                ->order("is_best desc,sort desc,id desc")->select()->toArray();
        }

        //处理列表增加链接项
        foreach ($list as $key => $value) {
            $list[$key]['url'] = "site-" . $linkName . "/show-" . $value['cat_id'] . "-" . $value['id'] . ".html";
            foreach ($value as $k => $v) {
                if (!empty($v) && strpos($v, "|") !== false && !in_array($k, ['title', 'subtitle', 'content', 'keywords'])) {
                    //处理字段内是否有“|”符号，如果有则拆分成数组
                    $article[$k] = explode("|", $v);
                }
            }

            $list[$key]['associatedList'] = [];
            if (!empty($value['associated'])) {
                $associated = json_decode($value['associated'], true);
                $list[$key]['associatedList'] = Db::name($associated['tableName'])
                    ->where('id', 'IN', $associated['id'])->order('sort desc,id desc')
                    ->select()->toArray();
            }
        }

        return $list;
    }

    /**
     * 获得指定图文信息
     * @param string $id 图文ID
     * @param string $cat_id 栏目ID
     */
    public function getArticle($id, $cat_id)
    {
        //获得当前图文所属的栏目信息
        $articleCate = $this->app->db->name('DataArticleCate')->where(["id" => $cat_id])->find();  //分类信息
        if (empty($articleCate['link_name'])) {
            $articleCate['link_name'] = "List";
        }
        $contentTemplateInfo = $this->app->db->name('DataTemplate')->where(["id" => $articleCate['content_template_id']])->find();        //内容模板信息

        //获得当前图文信息
        $article = Db::name($contentTemplateInfo['table_name'])->where('id', $id)->find();
        $article['url'] = "site-" . $articleCate['link_name'] . "/show-" . $article['cat_id'] . "-" . $article['id'] . ".html";

        foreach ($article as $k => $v) {
               if (!empty($v) && strpos($v, "|") !== false && !in_array($k, ['title', 'subtitle', 'content', 'keywords'])) {
                    //处理字段内是否有“|”符号，如果有则拆分成数组
                    $article[$k] = explode("|", $v);
                }
        }

        return $article;
    }

    /**
     * 获得指定图文的上一篇图文
     * @param string $id 图文ID
     * @param string $cat_id 栏目ID
     * @param string $table 数据表
     */
    public function getArticlePrev($id, $cat_id, string $table)
    {
        $prefix = $this->getPrefix();
        $list = $this->app->db->table($prefix . $table)->field("id,cat_id,title")->where('cat_id', $cat_id)->where(['status' => 1])->order('sort desc,id desc')->select()->toArray();
        foreach ($list as $key => $value) {
            if ($value['id'] == $id) {

                $thisKey = $key;
            }
        }
        //        dump($thisKey);die();
        $thisKey = empty($thisKey) ? 0 : $thisKey;
        foreach ($list as $k => $v) {
            if ($thisKey == 0) {
                $articlePrev = [];      //如果为第一条记录则为空
            } else {
                if ($k == $thisKey - 1) {
                    //上一条记录
                    $articlePrev = ['id' => $v['id'], 'cat_id' => $v['cat_id'], 'title' => $v['title']];
                }
            }
        }
        return $articlePrev;
    }

    /**
     * 获得指定图文的下一篇图文
     * @param string $id 图文ID
     * @param string $cat_id 栏目ID
     * @param string $table 数据表
     */
    public function getArticleNext($id, $cat_id, string $table)
    {
        $prefix = $this->getPrefix();
        $list = $this->app->db->table($prefix . $table)->field("id,cat_id,title")->where('cat_id', $cat_id)->where(['status' => 1])->order('sort desc,id desc')->select()->toArray();
        foreach ($list as $key => $value) {
            if ($value['id'] == $id) {
                $thisKey = $key;
            }
        }
        $thisKey = empty($thisKey) ? 0 : $thisKey;
        foreach ($list as $k => $v) {
            if ($k == $thisKey + 1) {
                //下一条记录
                $articleNext = ['id' => $v['id'], 'cat_id' => $v['cat_id'], 'title' => $v['title']];
            }
        }
        if (!isset($articleNext)) {
            $articleNext = [];
        }
        return $articleNext;
    }

    /**
     * 获得内容模型列表(用于搜索功能)
     */
    public function getModelList()
    {
        return $this->app->db->name('DataTemplate')->where(['template_type' => 1])->select();
    }
    /**
     * 获得表单信息
     * @param string $formId 图文ID
     */
    public function getForm($formId = 0)
    {
        $formInfo = [];
        //获得表单页面
        $form = DB::name('DataForm')->where(['id' => $formId, 'is_deleted' => 0, 'status' => 1])->find();

        $param = DB::name('DataFormParam')->where(['form_id' => $formId, 'is_deleted' => 0, 'status' => 1])->order("sort asc,id asc")->select()->toArray();
        $params = [];
        foreach ($param as $key => $val) {
            $params['list'][$key] = $val;
            $params['params'][$val['form_name']]['name'] = $val['form_name'];
            $params['params'][$val['form_name']]['label'] = $val['form_label'];
            $params['params'][$val['form_name']]['value'] = $val['form_value'];
            if ($val['type'] == 1 || $val['type'] == 4) {
                //表单项为单选或多选时
                $params['list'][$key]['form_value'] = explode("|", $val['form_value']);
                $params['params'][$val['form_name']]['value'] = explode("|", $val['form_value']);
            }
        }

        $formInfo = $params;
        $formInfo['form'] = $form;
        // dump($formInfo);die();

        return $formInfo;
    }

    //获取数据表前缀
    public function getPrefix()
    {
        return config("database.connections.mysql.prefix");      //数据表前缀
    }

    /**
     * Mysql查询一条记录
     * @param string $formId 图文ID
     */
    public function getSql($mysql)
    {
        $query = DB::query($mysql);
        return $query;
    }

    /**
     * Mysql执行增加、修改和删除语句
     * @param string $formId 图文ID
     */
    public function setSql($mysql)
    {
        $query = DB::execute($mysql);
        return $query;
    }
}
