<?php
namespace app\index\controller;

use app\index\controller\Custom;
use think\admin\Controller;
use think\facade\Db;
use think\facade\Route;

/**
 * 应用入口
 * Class Index
 * @package app\index\controller
 */
class Goods extends Controller
{
    public function initialize()
    {
        //检测用户登陆状态
        $userID = $this->app->session->get('uid');
        $userInfo = $this->app->db->name('DataUser')->where('id',$userID)->find();
        $this->assign('userInfo',$userInfo);

        /* 定义全局函数 */
        $GLOBALS['function'] = new \app\index\controller\Custom($this->app);
        $this->assign('function',new \app\index\controller\Custom($this->app));

         /* 定义商城全局函数 */
        $GLOBALS['shop'] = new \app\index\controller\Customshop($this->app);
        $this->assign('shop',new \app\index\controller\Customshop($this->app));

        $config = $GLOBALS['function']->getConfig();      //获得网站配置
        $this->assign('config',$config);

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
        $this->assign('langSession',$langSession);

        //获取语言包列表
        $files = scandir($_SERVER["DOCUMENT_ROOT"]."/public/lang/files/");
        $LanguageFilesList = [];
        // 读取指定目录下的所有文件
        foreach ($files AS $key => $value){
            if($value == "." || $value == ".."){

            }else{
                $languageName = substr($value,0,strrpos($value,"."));
                $LanguageFilesList[$key] = $languageName;
            }
        }
        $LanguageFilesList = array_values($LanguageFilesList);
        $this->assign('languagesList', $LanguageFilesList);

        //顶部自定义导航
        $nav = $GLOBALS['function']->getAllCat();      //获得网站导航
        $this->assign('nav',$nav);

        /*Banner轮播图*/
        $bannerArr = $GLOBALS['function']->sliderPictures(4);   //sliderPictures()里的数值去查看system_data数据表里name为对应位置，如首页轮播sliderHomePage所在的id
        $this->assign('banner',$bannerArr);

        //获得内容模型列表(用于搜索功能)
        $this->assign('modelList',$GLOBALS['function']->getModelList());

        //顶部商品分类列表
        $cates = Db::name('StoreGoodsCate')->field("id,title,icon,wap_icon")->where(['parent_id'=>0,'status'=>1,'is_deleted'=>0])->order("sort asc,id asc")->select();
        $this->assign('cates',$cates);

        $catTreeList['topCate']['id'] = 1;                          //导航菜单ID
        $this->assign('catTreeList',$catTreeList);          //导航定位效果
    }


    /**
     * 顶级分类页
     */
    public function index()
    {
        //获得分类列表
        $db = DB::name('StoreGoodsCate')->field('id,title,logo')->where(['parent_id'=>0,'status'=>1,'is_deleted'=>0])->order('sort desc, id asc')->select();
        $this->assign('select',$db);

        //获得所有产品列表
        //获得每页设置显示数量
        $pageNum = $GLOBALS['function']->getConfig()['page_num'];
        $list = DB::name('StoreGoods')->where(['status'=>1])
            ->order('sort desc,create_at desc')
            ->paginate($pageNum,false,['query' => request()->param()])->each(function($item, $key){
                //循环操作

                $price = self::goods_price($item['id'],$item['goods_price']);
                $item['goods_price'] = $price['discount_price'];      //获得折扣后价格
                $item['old_price'] = $price['old_price'];             //获得折扣前价格

                return $item;
            });
        $page = $list->render();
        $this->assign('page', $page);
        $this->assign('goods_list',$list);    //获得商品列表

        return $this->fetch('index');
    }

    /**
     * 商品列表页
     */
    public function goods_list(){
        $cat_id = $this->request->param('id');  //一级分类ID
        $cat_id = empty($cat_id)?0:$cat_id;

        $childCate3 = $this->request->param('child_id');  //三级分类ID
        $childCate2 = $this->request->param('parent_id');  //二级分类ID

        $whereOrId = isset($_GET['child_id']) ? $_GET['child_id'] : 0 ;

        if(!empty($childCate3)){     //三级分类ID
            $cateInfo = DB::name('StoreGoodsCate')->field("parent_id,title")->where(['id'=>$childCate3])->find();

            $this->assign('child_id',$childCate3);      //三级分类ID
            $this->assign('parent_id',$cateInfo['parent_id']);      //二级分类ID
            $this->assign('cate_name',$cateInfo['title']);          //三级分类名称
            $this->assign('catId',$cat_id);
        }
        elseif(!empty($childCate2)){     //二级分类ID
            $cateInfo = DB::name('StoreGoodsCate')->field("title")->where(['id'=>$childCate2])->find();
            $this->assign('parent_id',$childCate2);      //二级分类ID
            $this->assign('cate_name',$cateInfo['title']);          //二级分类名称
            $this->assign('catId',$cat_id);

            $childCate3 = DB::name('StoreGoodsCate')->where('parent_id',$childCate2)->where(['status'=>1,'is_deleted'=>0])->order("sort desc,id asc")->column("id");
            //三级分类ID串
            $childCate3 = implode(",",$childCate3);

        }
        else{
            $cateInfo = DB::name('StoreGoodsCate')->where(['id'=>$cat_id])->column("title");
            $this->assign('cate_name',$cateInfo[0]);            //一级分类名称
            $this->assign('catId',$cat_id);

            //查询三级分类相关
            $cateIds = DB::name('StoreGoodsCate')->where(['parent_id'=>$cat_id,'status'=>1,'is_deleted'=>0])->column("id");
            $cateIds = implode(",",$cateIds);

            //获得三级分类ID
            $childCate3 = DB::name('StoreGoodsCate')->where('parent_id','IN',$cateIds)->where(['status'=>1,'is_deleted'=>0])->order("sort desc,id asc")->column("id");

            //获得扩展分类对应的商品IDS
            $other_ids = '';
            foreach ($childCate3 AS $value){
                $other_goodsids[] = DB::name('StoreGoods')->where(['status'=>1])
                    ->where('find_in_set(:other_cate,other_cate)',['other_cate'=>$value])->column("id");
            }

            //获得扩展分类下的商品IDS数组形式并将数组去除空值
            foreach ($other_goodsids AS $k => $v){
                $other_goodsids_filter[] = array_filter($v);
            }
            //将数组拼接成字符串
            foreach ($other_goodsids_filter AS $ko => $vo){
                $other_ids .= implode(",",$vo).",";
            }

            //字符串去重
            $other_ids = explode(',', $other_ids);
            $other_ids = array_unique($other_ids);//内置数组去重算法
            $other_ids = implode(',', $other_ids);
            $other_ids = trim($other_ids,',');//trim — 去除字符串首尾处的空白字符（或者其他字符）,假如不使用，后面会多个逗号

            //三级分类ID串
            $childCate3 = implode(",",$childCate3);

        }

        //获得分类列表
        $select = DB::name('StoreGoodsCate')->field('id,title,logo')->where(['parent_id'=>0,'status'=>1,'is_deleted'=>0])->order('sort desc, id asc')->select();
        $this->assign('select',$select);

        //一级分类页面用于判断有无设置扩展分类的商品ids
        $other_ids = isset($other_ids)?$other_ids:0;

        //获得每页设置显示数量
        $pageNum = $GLOBALS['function']->getConfig()['page_num'];

        if($cat_id==0){
            $childCate3 = '';
        }else{
            $childCate3 = $cat_id;
        }

        //商品列表相关
        $list = DB::name('StoreGoods')->where('cate_id','IN',$childCate3)->where(['status'=>1])
            ->whereOr(function ($query) use ($other_ids){
                $query->where(['status'=>1])->where('id','IN',$other_ids);
            })
            ->whereOr(function ($query) use ($whereOrId){
                $query->where(['status'=>1])->where('find_in_set(:other_cate,other_cate)',['other_cate'=>$whereOrId]);
            })
            ->order('sort desc,create_at desc')
            ->paginate($pageNum,false,['query' => request()->param()])->each(function($item, $key){
                //循环操作

                $price = self::goods_price($item['id'],$item['goods_price']);
                $item['goods_price'] = $price['discount_price'];      //获得折扣后价格
                $item['old_price'] = $price['old_price'];             //获得折扣前价格

                return $item;
            });
        $page = $list->render();
        $this->assign('page', $page);
        $this->assign('goods_list',$list);    //获得商品列表

        $cate = DB::name('StoreGoodsCate')->field('id,title')->where(['parent_id'=>$cat_id,'is_deleted'=>0,'status'=>1])->order('sort asc,id desc')->select()->toArray();
        foreach ($cate AS $k => $v){
            //获得三级分类
            $cate[$k]['child_cate'] = DB::name('StoreGoodsCate')->field('id,title')
                ->where(['parent_id'=>$v['id'],'status'=>1,'is_deleted'=>0])->order("sort desc,id asc")->select()->toArray();
        }

        $this->assign('cate_list',$cate);    //获得商品筛选列表

        return $this->fetch('goods_list');
    }

    /**
     * 搜索、热销、折扣列表页
     */
    public function search_list(){
        $type = $this->request->param('type');

        //搜索功能
        $_GET['key_word'] = isset($_GET['key_word']) ? $_GET['key_word'] : "";
        $key_word = isset($_POST['key_word']) ? $_POST['key_word'] : $_GET['key_word'];
        $this->assign('key_word',$key_word);

        //搜索
        if(!empty($key_word)){
            $where = 'title like "%' . $key_word . '%"';
            $cateName = "搜索结果";
        }else{
            if($type == 'hot'){
                $where['is_hot'] = 1;
                $cateName = "热销商品";
            }elseif($type == 'discount'){
                $where['is_discount'] = 1;
                $cateName = "折扣商品";
            }else{
                $where = '';
                $cateName = "搜索结果";
            }
        }

        $db = DB::name('StoreGoods')->where($where)->where(['status'=>1])->order('sort desc,create_at desc')->paginate(16,false,['query' => request()->param()])->each(function($item, $key){
            //循环操作

            $price = self::goods_price($item['id'],$item['goods_price']);
            $item['goods_price'] = $price['discount_price'];      //获得折扣后价格
            $item['old_price'] = $price['old_price'];             //获得折扣前价格

            return $item;
        });
        $page = $db->render();
        $this->assign('page', $page);
        $this->assign('cate_name', $cateName);

        $this->assign('goods_list',$db);    //获得商品列表


        return $this->fetch('search_list');
    }

    /**
     * 商品详情页
     */
    public function goods_info(){
        $id = $this->request->param('id');
        $id = empty($id) ? 0 : $id;
        if($id == 0){
            $this->error('非法访问!', 'javascript:history.back()');
        }
        

        $db = DB::name('StoreGoods')->where('id',$id)->find(); //获得商品详情数据
        $db['image'] = explode("|",$db['image']);   //将商品组图转成数组

        $price = self::goods_price($db['id'],$db['goods_price']);
        $db['goods_price'] = $price['discount_price'];      //获得折扣后价格
        $db['old_price'] = $price['old_price'];             //获得折扣前价格


        $cate3 = DB::name('StoreGoodsCate')->where('id',$db['cate_id'])->find(); //获得商品所属3级商品分类
        $cateInfo3 = $cate3;
        $this->assign('cateInfo3',$cateInfo3);

        $cate2 = DB::name('StoreGoodsCate')->where('id',$cateInfo3['parent_id'])->find(); //获得商品所属2级商品分类
        $cateInfo2 = $cate2;
        $this->assign('cateInfo2',$cateInfo2);

        $cate1 = DB::name('StoreGoodsCate')->where('id',$cateInfo2['parent_id'])->find(); //获得商品所属1级商品分类
        $cateInfo1 = $cate1;
        $this->assign('cateInfo1',$cateInfo1);

        //获得商品属性
        $goodsId = $db['id'];  //当前商品ID
        $attrValueId = DB::name('StoreGoodsAttr')->where(['goods_id'=>$goodsId, 'status'=>1, 'is_deleted'=>0])
            ->group("attr_value_id")->order("sort desc,id asc")->select()->toArray(); //获得商品所属的商品属性值ID
        $goodsAttr = array();
        foreach ($attrValueId AS $key => $value){
            //获得属性值列表
            $goodsAttr[$key] = DB::name('StoreAttrValue')->field("id,attr_value_name,attr_value_type")
                ->where(['id'=>$value['attr_value_id'],'status'=>1,'is_deleted'=>0])->find();
        }
        foreach ($goodsAttr AS $k => $val){
            //获得商品属性设置
            $goodsAttr[$k]['attr_list'] = DB::name('StoreGoodsAttr')->where(['attr_value_id'=>$val['id'], 'goods_id'=>$goodsId, 'status'=>1, 'is_deleted'=>0])
                ->order("sort asc,id asc")->select();
        }
        $this->assign('goods_attr',$goodsAttr);
        $this->assign('goods',$db);

        //推荐产品列表
        $hot_goods = DB::name('StoreGoods')->alias("g")->field("g.*,c.parent_id")->join("store_goods_cate c","g.cate_id = c.id","LEFT")
//            ->where(["c.parent_id"=>$cateInfo3['id'],'g.status'=>1,'g.is_hot'=>1])->order('create_at desc')->select();
            ->where(["c.id"=>$cateInfo3['id'],'g.status'=>1,'g.is_hot'=>1])->order('sort desc,create_at desc')->select();
        $this->assign('hot_goods',$hot_goods);

        return $this->fetch('goods_info');
    }

    /**
     * Ajax判断商品属性组合
     */
    public function get_price()
    {
        $spec_id = $_POST['spec_id'];
        $goods_id = $_POST['goods_id'];
        $callBack = ['err'=>0,'msg'=>'','data'=>[]];

        $stockInfo = DB::name('StoreGoodsStock')->where(['goods_spec'=>$spec_id,'goods_id'=>$goods_id])->find();

        if(empty($stockInfo)){
            $callBack['err'] = 1;
            echo json_encode($callBack);
            exit;
        }else{
            $data = [];
//            $data['goods_price'] = $stockInfo['price_stock'];
            $price = self::goods_price($goods_id,$stockInfo['price_stock']);    //引用获得商品最终价格函数
            $data['goods_price'] = $price['discount_price'];        //获得会员折扣后的价格
            $data['old_price'] = $price['old_price'];        //获得会员折扣前的价格
            $data['goods_number'] = $stockInfo['number_stock'];

            $callBack['data'] = $data;
            echo json_encode($callBack);
            exit;
        }
    }

    /**
     * 获得商品最终价格
     * goods_id 商品ID
     * goods_price 商品价格(可选)
     */
    public static function goods_price($goods_id,$goods_price)
    {
        $goods = Db::name('StoreGoods')->field("is_discount,is_member_discount,goods_price,discount_price")->where('id', $goods_id)->find();   //查询是否为活动折扣

        if($goods['is_discount'] == 1){     //活动折扣
            $oldPrice = $goods['goods_price'];
            $discountPrice = $goods['discount_price'] == 0?$goods['goods_price']:$goods['discount_price'];
            $price = ['old_price'=>$oldPrice,'discount_price'=>round($discountPrice,2)];
        }else{
            if($goods['is_member_discount'] == 1){
                if(!empty($_SESSION['member_id'])){
                    //登录用户获得会员等级价格
                    $rank = Db::name('StoreMember')->field("member_rank_id")->where('id', $_SESSION['member_id'])->find();  //获得会员等级ID
                    $rankMemberPrice = Db::name('StoreGoodsMemberPrice')->field("goods_price")->where(['goods_id' => $goods_id,'member_rank'=>$rank['member_rank_id']])->find();

                    if(empty($rankMemberPrice['goods_price'])){    //未传入商品价格
                        $oldPrice = $goods_price;
                        $discountPrice = $goods_price;
                    }else{
                        //从数据库中获得商品的价格并计算为会员折扣
                        $oldPrice = $goods['goods_price'];
                        $discountPrice = $rankMemberPrice['goods_price'];
                    }

                    $price = ['old_price'=>$oldPrice,'discount_price'=>round($discountPrice,2)];
                }else{
                    $price = ['old_price'=>$goods_price,'discount_price'=>$goods_price];        //即非活动折扣也非会员登陆，则给出函数传入的价格
                }
            }else{
                $price = ['old_price'=>$goods['goods_price'],'discount_price'=>$goods['goods_price']];        //即非活动折扣也非会员折扣，则给出函数传入的价格
            }

        }

        return $price;
    }

}
