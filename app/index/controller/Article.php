<?php

namespace app\index\controller;

use app\index\controller\Custom;
use think\admin\Controller;
use think\facade\Db;
use think\facade\Route;


/**
 * Class Index
 * @package app\index\controller
 */
class Article extends Controller
{
    public function initialize()
    {
        //检测用户登陆状态
        $userID = $this->app->session->get('uid');
        $userInfo = $this->app->db->name('DataUser')->where('id', $userID)->find();
        $this->assign('userInfo', $userInfo);

        /* 定义全局函数 */
        $GLOBALS['function'] = new \app\index\controller\Custom($this->app);
        $this->assign('function', new \app\index\controller\Custom($this->app));

        /* 定义商城全局函数 */
        $GLOBALS['shop'] = new \app\index\controller\Customshop($this->app);
        $this->assign('shop', new \app\index\controller\Customshop($this->app));


        $config = $GLOBALS['function']->getConfig();      //获得网站配置
        $this->assign('config', $config);

        //加载语言包
        $langGet = $this->request->param('lang');
        if (empty($langGet)) {
            $this->app->session->set('lang', 'cn');
        } else {
            $this->app->session->set('lang', $langGet);
        }
        $lang = [];
        $langSession = $this->app->session->get('lang');
        include "public/lang/files/" . $langSession . ".php";
        $this->assign('lang', $lang);
        $this->assign('langSession', $langSession);

        //获取语言包列表
        $files = scandir($_SERVER["DOCUMENT_ROOT"] . "/public/lang/files/");
        $LanguageFilesList = [];
        // 读取指定目录下的所有文件
        foreach ($files as $key => $value) {
            if ($value == "." || $value == "..") {
            } else {
                $languageName = substr($value, 0, strrpos($value, "."));
                $LanguageFilesList[$key] = $languageName;
            }
        }
        $LanguageFilesList = array_values($LanguageFilesList);
        $this->assign('languagesList', $LanguageFilesList);

        //顶部自定义导航
        $nav = $GLOBALS['function']->getAllCat();      //获得网站导航
        $this->assign('nav', $nav);

        /*Banner轮播图*/
        $bannerArr = $GLOBALS['function']->sliderPictures(4);   //sliderPictures()里的数值去查看system_data数据表里name为对应位置，如首页轮播sliderHomePage所在的id
        $this->assign('banner', $bannerArr);

        //获得内容模型列表(用于搜索功能)
        $this->assign('modelList', $GLOBALS['function']->getModelList());
    }

    public function index()
    {
        //        $this->redirect(sysuri('admin/login/index'));
        //        $db = DB::name('SystemConfig')->where('id','in',[2,4,6,7])->column('name,value');
        //        dump($db);die();

        return $this->fetch();
    }

    /* 列表页 */
    public function list()
    {
        $id = $this->request->param('id');
        $id = empty($id) ? 0 : $id;
        $this->assign('catId', $id);


        //获得栏目模板信息
        $articleCate = DB::name('DataArticleCate')->where(["id" => $id])->find();  //分类信息
        $this->assign('catInfo', $articleCate);     //当前分类信息

        if (empty($articleCate['link_name'])) {
            $articleCate['link_name'] = "List";
        }
        $this->assign('linkName', $articleCate['link_name']);     //当前分类的静态名称

        if ($articleCate['parent_id'] == 0 && empty($articleCate['content_template_id'])) {
            //如果是顶级栏目
            $catIds = Db::name('DataArticleCate')->where(['parent_id' => $id])->column('id');
            if (empty($catIds[0])) {
                dump("此分未设置内容模板或下级分类！");
                die();
            }
            $childCate = DB::name('DataArticleCate')->where(["id" => $catIds[0]])->find();  //第一个分类信息

            //获得指定栏目所在的所有同级栏目
            $this->assign('catTreeList', $GLOBALS['function']->getCatList($catIds[0]));

            $templateInfo = Db::name('DataTemplate')->where(["id" => $childCate['template_id']])->find();        //分模板信息

            $contentTemplateInfo = Db::name('DataTemplate')->where(["id" => $childCate['content_template_id']])->find();        //内容模板信息
            $catIds = implode(",", $catIds); //将数组转成字符串
            $where = "cat_id IN (" . $catIds . ")";
        } else {
            $templateInfo = Db::name('DataTemplate')->where(["id" => $articleCate['template_id']])->find();        //分模板信息
            $contentTemplateInfo = Db::name('DataTemplate')->where(["id" => $articleCate['content_template_id']])->find();        //内容模板信息
            $where['cat_id'] = $id;

            //获得指定栏目所在的所有同级栏目
            $this->assign('catTreeList', $GLOBALS['function']->getCatList($id));
        }

        $prefix = config("database.connections.mysql.prefix");      //数据表前缀
        if ($articleCate['is_page'] == 1) {
            //如果是单页，获得单页信息并跳转
            $articlePage = DB::table($prefix . $contentTemplateInfo['table_name'])->field("id,cat_id")->where(["cat_id" => $id])->find();  //获得单页信息

            if (empty($articlePage)) {
                dump("此页未创建内容！");
                die();
            }
            $this->redirect("/site-" . $articleCate['link_name'] . "/show-" . $articlePage['cat_id'] . "-" . $articlePage['id'] . ".html");
        }

        //获得每页设置显示数量
        $pageNum = empty($articleCate['page_num']) ? $GLOBALS['function']->getConfig()['page_num'] : $articleCate['page_num'];

        //获得筛选项
        $filterList = [];
        $filterList = DB::table($prefix . "data_template_field")->field("field_name,field_other_name,filed_setting")
            ->where(["template_id" => $articleCate['content_template_id'], "status" => 1, "is_filter" => 1])->select()->toArray();
        foreach ($filterList as $key => $value) {
            $filed_setting = explode("\r\n", $value['filed_setting']);

            foreach ($filed_setting as $k => $v) {
                $filterList[$key]['filter'][$k]['name'] = substr($v, 0, strrpos($v, "|"));
                $filterList[$key]['filter'][$k]['value'] = substr($v, strripos($v, "|") + 1);
            }
        }

        $this->assign('filterList', $filterList);

        //筛选
        $whereGet = [];
        if ($this->request->param('filter') == 1) {
            foreach ($filterList as $key => $value) {
                $fieldName = $value['field_name'];
                if ($this->request->param($fieldName)) {
                    $whereGet[$fieldName] = $this->request->param($fieldName);
                    $this->assign($fieldName, $this->request->param($fieldName));
                    if (empty($this->request->param($fieldName))) {
                        unset($whereGet[$fieldName]);
                    }
                }
            }
        }

        //指定栏目的文章列表
        $list = DB::table($prefix . $contentTemplateInfo['table_name'])
            ->where($where)->where($whereGet)->where(['status' => 1])->order('sort desc,id desc')
            ->paginate(['list_rows' => $pageNum, 'query' => request()->param()])->each(function ($item, $key) {
                //循环操作
                return $item;
            });
        $page = $list->render();
        $this->assign('page', $page);
        $this->assign('articleList', $list);

        //获得列表页模板
        $template = substr($templateInfo['file_name'], 0, strrpos($templateInfo['file_name'], "."));

        return $this->fetch($template);
    }

    /* 详情页/单页 */
    public function show()
    {
        $id = $this->request->param('id');      //获得路由传送参数
        $id = empty($id) ? 0 : $id;
        $this->assign('id', $id);

        $catId = $this->request->param('cat_id');
        $catId = empty($catId) ? 0 : $catId;
        $this->assign('catId', $catId);


        //获得指定栏目所在的所有同级栏目(左侧树状列表)
        $this->assign('catTreeList', $GLOBALS['function']->getCatList($catId));
        //        dump($GLOBALS['function']->getCatList($catId));die();

        //获得当前图文所属的栏目信息
        $articleCate = DB::name('DataArticleCate')->where(["id" => $catId])->find();  //分类信息
        $this->assign('catInfo', $articleCate);     //当前分类信息

        if (empty($articleCate['link_name'])) {
            $articleCate['link_name'] = "List";
        }
        $this->assign('linkName', $articleCate['link_name']);     //当前分类的静态名称

        $templateInfo = DB::name('DataTemplate')->where(["id" => $articleCate['template_id']])->find();        //分类模板信息
        $contentTemplateInfo = DB::name('DataTemplate')->where(["id" => $articleCate['content_template_id']])->find();        //内容模板信息

        if (!$contentTemplateInfo['table_name']) {
            return $this->fetch("error_page");
            exit;
        }

        //获得当前图文信息
        $article = Db::name($contentTemplateInfo['table_name'])->where('id', $id)->find();
        foreach ($article as $k => $v) {
            if (!empty($v) && strpos($v, "|") !== false && !in_array($k, ['title', 'subtitle', 'content', 'keywords'])) {
                //处理字段内是否有“|”符号，如果有则拆分成数组
                $article[$k] = explode("|", $v);
            }
        }
        if (!empty($article['associated'])) {
            $associated = json_decode($article['associated'], true);
            $article['associatedList'] = Db::name($associated['tableName'])
                ->where('id', 'IN', $associated['id'])->order('sort desc,id desc')
                ->select()->toArray();
        } else {
            $article['associatedList'] = [];
        }
        $this->assign('article', $article);



        $prefix = config("database.connections.mysql.prefix");      //数据表前缀
        //浏览次数+1
        Db::table($prefix . $contentTemplateInfo['table_name'])->where('id', $id)->Inc('read_count', 1)->update();

        //上一篇图文信息
        $prev_article = $GLOBALS['function']->getArticlePrev($article['id'], $articleCate['id'], $contentTemplateInfo['table_name']);
        $this->assign('prev', $prev_article);

        //下一篇图文信息
        $next_article = $GLOBALS['function']->getArticleNext($article['id'], $articleCate['id'], $contentTemplateInfo['table_name']);
        $this->assign('next', $next_article);

        //获得列表页模板
        $template = substr($contentTemplateInfo['file_name'], 0, strrpos($contentTemplateInfo['file_name'], "."));

        return $this->fetch($template);
    }

    /* 搜索内容列表 */
    public function search()
    {
        $keyword = $this->request->param('keyword') ?? $this->app->session->get('keyword');
        $this->app->session->set('keyword', $keyword);
        $this->assign('keyword', $keyword);
        $pageNum = $GLOBALS['function']->getConfig()['page_num'];    //获得每页设置显示数量
        $prefix = config("database.connections.mysql.prefix");      //数据表前缀
        $modelName = "";
        if (!isset($_GET['model']) || (isset($_GET['model']) && empty($_GET['model']))) {
            //如果没有模型选择，则搜索全部模型
            $tables = DB::query("show tables like 'data_article_show_%'");  //搜索所有内容模型数据表
            foreach ($tables as $key => $value) {
                //将所有获得的数据表数组整理成一维数组
                if (array_values($value)[0] != 'data_article_show_default') {
                    //去除默认模型
                    $tableArr[] = array_values($value)[0];
                }
            }
            $whereField = "id,cat_id,title,subtitle,create_at,logo,keywords,force_link,template_id";       //获得字段
            $whereUnion = [];
            foreach ($tableArr as $key => $value) {
                //生成与union关联的查询语句
                if($value != 'data_article_show_no'){
                    
                $whereUnion[] = DB::table($prefix . $value)->alias("a")->field($whereField)->where('title LIKE "%' . $keyword . '%" AND status = 1')->buildSql();
                }
            }

            //生成union子查询语句
            $sqlUnion = DB::table($prefix . "data_article_show_default")->alias("a")->field($whereField)
                ->where('title LIKE "%' . $keyword . '%" AND status = 1')
                ->union($whereUnion)->buildSql();
            //获得搜索结果
            $list = DB::table($sqlUnion)->alias("a")
                ->paginate($pageNum, false, ['query' => request()->param()])->each(function ($item, $key) {
                    //循环操作
                    return $item;
                });
        } else {
            //如果有模型选择，则搜索该模型
            $modelName = $_GET['model'];
            $templateInfo = DB::name('DataTemplate')->where(["template_name" => $modelName, 'template_type' => 1])->find();        //内容模型信息
            $tableName = $templateInfo['table_name'];

            //获得搜索结果
            $list = DB::table($prefix . $tableName)
                ->where('title LIKE "%' . $keyword . '%"')->where(['status' => 1])->order('sort desc,id desc')
                ->paginate($pageNum, false, ['query' => request()->param()])->each(function ($item, $key) {
                    //循环操作
                    return $item;
                });
        }

        $page = $list->render();
        $this->assign('page', $page);
        $this->assign('articleList', $list);

        $this->assign('modelName', $modelName);

        return $this->fetch('search');
    }

    /**
     * 表单提交处理
     */
    public function form_handle()
    {

        //接收表单提交
        if (request()->isPost()) {
            $data = $_POST;

            if (empty(trim($data['name']))) {
                $this->error("请填写您的姓名");
            }
            if (empty(trim($data['phone']))) {
                $this->error("请填写您的电话");
            }

            $member_id = 0;        //如果用户登陆，则获得用户ID

            //获得访问用户的IP
            $ip = $_SERVER['REMOTE_ADDR'];
            if (isset($_SERVER['HTTP_CLIENT_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) and preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)) {
                foreach ($matches[0] as $xip) {
                    if (!preg_match('#^(10|172\.16|192\.168)\.#', $xip)) {
                        $ip = $xip;
                        break;
                    }
                }
            }

            $ifThere = Db::name("DataFormList")->where(['member_id' => $member_id, 'member_IP' => $ip, 'type' => $data['type'], 'is_status' => 1])->count("id");
            if ($ifThere > 0) {
                $this->error("您留言正在审核中，请不要重复提交！", '');
            } else {
                $mailTitle = "Welcome to UNICARBON";

                Index::send_mail($data['email'], $data['name'], $mailTitle);

                Db::name('DataFormList')->insert([
                    'member_id' => $member_id,
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    //                    'email' => $data['email'],
                    'text_desc' => $data['text_desc'],
                    'type' => $data['type'],
                    'member_IP' => $ip,
                    'is_status' => 1,
                    'is_deleted' => 0
                ]);
                $this->success("提交成功！", '');
            }
        }
    }
}
