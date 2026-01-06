<?php
namespace app\index\controller;

use app\index\controller\Custom;
use think\admin\Controller;
use think\facade\Db;
use think\facade\Route;
use We;
use WeChat\Script;

/**
 * 应用入口
 * Class Index
 * @package app\index\controller
 */
class Flow extends Controller
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
        $cates = DB::name('StoreGoodsCate')->field("id,title,icon,wap_icon")->where(['parent_id'=>0,'status'=>1,'is_deleted'=>0])->order("sort asc,id asc")->select();
        $this->assign('cates',$cates);

    }

    //控制器默认跳转到购物车
    public function index(){
        $this->redirect('Flow/cart');
    }

    /**
     * 购物车页
     */
    public function cart()
    {
        $userID = $this->app->session->get('uid');
        if($userID == 0){
            $this->redirect('/login');
        }

        //查询用户的购物车里商品信息
        $db = DB::name('StoreCart')->where('member_id',$userID)->order("add_time desc,id DESC")->select()->toArray();

        foreach ($db as $keys => $val) {
            //格式化商品属性
            $db[$keys]['spec_info'] =  str_replace(",","<br>",$val['spec_info']);

            //获得商品所属分类名称
            $cate = Db::name('StoreGoodsCate')->field('title')->where('id',$val['goods_cat'])->find();
            $db[$keys]['goods_cat'] = $cate['title'];

            //获得商品库存，即最大购买数量
            if(empty($val['goods_spec'])){
                //如果没有用户选择的组合商品属性，则从商品数据表中获取库存
                $goods = Db::name('StoreGoods')->field('goods_number')->where('id',$val['goods_id'])->find();
                $db[$keys]['max_number'] = $goods['goods_number'];
            }else{
                $cart = Db::name('StoreGoodsStock')->field('number_stock')->where('goods_spec',$val['goods_spec'])->find();
                $db[$keys]['max_number'] = $cart['number_stock'];
            }

            //如果后台删除商品，查询对应购物车的商品是否为下架或删除状态
            $goods_find = Db::name('StoreGoods')->where(['id'=>$val['goods_id'], 'status'=>1])->count();
            if($goods_find == 0){
                $db[$keys]['is_del'] = 1;
            }else{
                $db[$keys]['is_del'] = 0;
            }

            //如果后台下架或删除商品，查询对应购物车的商品是否为下架或删除状态
            $goods_find_2 = Db::name('StoreGoods')->field("id,goods_price,title")->where(['id'=>$val['goods_id']])->find();
            $goods_find_old_price = Goods::goods_price($goods_find_2['id'],$goods_find_2['goods_price']);

            if($goods_find_old_price['discount_price'] == $val['goods_price']){
                $db[$keys]['is_upd'] = 0;
            }else{
                $db[$keys]['is_upd'] = 1;
                $db[$keys]['info'] = "价格当前已变更，原价为 ¥".$val['goods_price'];
                $db[$keys]['goods_price'] = $goods_find_old_price['discount_price'];

                Db::name('StoreCart')->where(['goods_id'=>$val['goods_id']])->update(['goods_price'=>$goods_find_old_price['discount_price']]);
            }
            //同步商品名称
            if($goods_find_2['title'] != $val['goods_name']){
                Db::name('StoreCart')->where(['goods_id'=>$val['goods_id']])->update(['goods_name'=>$goods_find_2['title']]);
                $db[$keys]['title'] = $goods_find_2['title'];
            }


        }

        $this->assign('cart',$db);

        return $this->fetch('cart');
    }
    
    /**
     * 购物结算页
     */
    public function checkout()
    {
        
        $userID = $this->app->session->get('uid');
        if($userID == 0){
            $this->redirect('/login');
        }
        $cart_ids = $_POST['cart_ids'] ?? $_GET['cart_ids'];     //用户选中的购物车ID
        $goodsPrice = $_POST['amount'] ?? $_GET['goods_price'];         //商品价格
        
        
        //查询要结算的商品信息
        $goodsList = DB::name('StoreCart')->where('id','IN',$cart_ids)->where('member_id',$userID)->select()->toArray();
        foreach ($goodsList AS $key => $value){
            $goodsList[$key]['spec_info'] =  str_replace(",","<br>",$value['spec_info']);
        }
        $this->assign('goodsList',$goodsList);
        
        $this->assign('cart_ids',$cart_ids);
        
        
        /*获取用户所有收货地址*/
        $addressList = DB::name('DataUserAddress')->where(['uuid'=>$userID])->order("is_default DESC,id asc")->select()->toArray();
        foreach ($addressList AS $key => $value){
            $addressList[$key]['province'] = substr($value['province'],strripos($value['province'],".")+1);
            $addressList[$key]['city'] = substr($value['city'],strripos($value['city'],".")+1);
            $addressList[$key]['area'] = substr($value['area'],strripos($value['area'],".")+1);
        }
        $this->assign('addressList',$addressList);
        
        /*获取用户设置的默认收货地址*/
        $defaultAddress = DB::name('DataUserAddress')->where(['uuid'=>$userID,'is_default'=>1])->find();
        $defaultAddress["ID_card_front"]="";
        $defaultAddress["ID_card_back"]="";
        
        $this->assign('default_address',$defaultAddress);

        //联动地区省份列表
        $province = DB::name('SystemRegion')->field("id AS id,parent_id,region_name AS name,region_type")->where(['region_type'=>1])->select();
        $this->assign('province',$province);
        //联动地区城市列表
        $city = DB::name('SystemRegion')->field("id AS id,parent_id,region_name AS name,region_type")->where(['region_type'=>2])->select();
        $this->assign('city',$city);
        //联动地区县区列表
        $district = DB::name('SystemRegion')->field("id AS id,parent_id,region_name AS name,region_type")->where(['region_type'=>3])->select();
        $this->assign('district',$district);
        
        //$express_price = $this->express_price($defaultAddress['province']);
        $express_price=0;
        if(empty($addressList)){
            $express_price = sprintf("%.2f",0);
        }
        $amount = $goodsPrice + $express_price;
        
        
        $this->assign('express_price',$express_price);
        $this->assign('goodsPrice',$goodsPrice);
        $this->assign('amount',sprintf("%.2f",$amount));
        
        return $this->fetch('checkout');
    }

    /**
     * 结算生成订单并支付
     */
    public function pay()
    {
        $userID = $this->app->session->get('uid');
        if($userID == 0){
            $this->redirect('/login');
        }
        $cartIds = $_POST['cart_ids'] ?? 0;
        $cartIdCount = DB::name("StoreCart")->where('id','IN',$cartIds)->count();    //获得购物车内商品数量
        if(empty($cartIdCount)){
            $url = ['action'=>"/index/Flow/cart"];
            $this->message("购物车内无商品！", $url);

        }

        $url = ['action'=>"/index/Flow/checkout?cart_ids=".$_POST['cart_ids']."&goods_price=".$_POST['goods_price']];

        $data = [];

        /*上传身份证正面图片*/
        if(!empty($_FILES['ID_card_front']['name'])){
            $file = !empty($_FILES['ID_card_front'])?$_FILES['ID_card_front']:array('name'=>"");//得到传输的数据
            //得到文件名称
            $name = $file['name'];
            $type = strtolower(substr($name,strrpos($name,'.')+1)); //得到文件类型，并且都转化成小写
            $allow_type = array('jpg','jpeg','gif','png','bmp'); //定义允许上传的类型
            //判断文件类型是否被允许上传
            if(!in_array($type, $allow_type)){
                //如果不被允许，则直接停止程序运行
                $this->message("网站已设置禁止上传非图片文件！", $url);
            }
            //判断是否是通过HTTP POST上传的
            if(!is_uploaded_file($file['tmp_name'])){
                //如果不是通过HTTP POST上传的
                $this->message("非法上传文件！", $url);
            }
            //保存位置--图片名
            $image_name=date('His').str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT).".".$type;
            $image_file_path = 'public/upload/IDcard/'.date('Ymd');
            $image_file = $image_file_path;
            $imge_real_url = $image_file.'/'.$image_name;
            $imge_web_url = $image_file_path.'/'.$image_name;

//            dump($image_file);die();

            if (!file_exists($image_file)){
                if (!mkdir($image_file, 0777, true) && !is_dir($image_file)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $image_file));
                }
                fopen($image_file.'\\'.$image_name, "w");
            }

            //开始移动文件到相应的文件夹
            if(move_uploaded_file($file['tmp_name'],$imge_real_url)){
                $data['ID_card_front'] = $imge_web_url;
            }else{
                $this->message("文件上传失败，请尝试重新上传！", $url);
            }
        }else{
            $data['ID_card_front'] = !empty($_POST['ID_card_front_old'])?$_POST['ID_card_front_old']:"";
        }

        /*上传身份证反图片*/
        if(!empty($_FILES['ID_card_back']['name'])){
            $file2 = !empty($_FILES['ID_card_back'])?$_FILES['ID_card_back']:array('name'=>"");//得到传输的数据
            //得到文件名称
            $name2 = $file2['name'];
            $type2 = strtolower(substr($name2,strrpos($name2,'.')+1)); //得到文件类型，并且都转化成小写
            $allow_type = array('jpg','jpeg','gif','png','bmp'); //定义允许上传的类型
            //判断文件类型是否被允许上传
            if(!in_array($type2, $allow_type)){
                //如果不被允许，则直接停止程序运行
                $this->message("网站已设置禁止上传非图片文件！",$url);
            }
            //判断是否是通过HTTP POST上传的
            if(!is_uploaded_file($file2['tmp_name'])){
                //如果不是通过HTTP POST上传的
                $this->message("非法上传文件！",$url);
            }

            //保存位置--图片名
            $image_name2=date('His').str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT).".".$type2;
            $image_file_path = 'public/upload/IDcard/'.date('Ymd');
            $image_file = $image_file_path;
            $imge_real_url2 = $image_file.'/'.$image_name2;
            $imge_web_url2 = $image_file_path.'/'.$image_name2;

            //开始移动文件到相应的文件夹
            if(move_uploaded_file($file2['tmp_name'],$imge_real_url2)){
                $data['ID_card_back'] = $imge_web_url2;
            }else{
                $this->message("文件上传失败，请尝试重新上传！",$url);
            }
        }else{
            $data['ID_card_back'] = !empty($_POST['ID_card_back_old'])?$_POST['ID_card_back_old']:"";
        }


        $goodsPrice = $_POST['goods_price'];
        $expressPrice = $_POST['express_price'];
        $price_discount = $_POST['price_discount'];
        $payAmount = $_POST['amount'];
        $payId = $_POST['pay_id'];
        $address_id = $_POST['address_list'] ?? 0;   //用户收货地址ID
        $name = $_POST['name'];
        $phone = $_POST['phone'];
        $province = substr($_POST['province'],strripos($_POST['province'],".")+1);
        $city = $province == "海外"?"":substr($_POST['city'],strripos($_POST['city'],".")+1);
        $area = $province == "海外"?"":substr($_POST['area'],strripos($_POST['area'],".")+1);
        $address = $_POST['address'];
        $order_desc = $_POST['order_desc']??"";
        $coupon_id = $_POST['coupon_id']??0;

        $add_address = $_POST['add_address']??0;
        if($add_address == 1){
            $addressIns = [];
            /* 判断是否创建用户收货地址入用户地址簿 */
            $addressIns['mid'] = $_SESSION['member_id'];
            $addressIns['name'] = $name;
            $addressIns['phone'] = $phone;

            $addressIns["province"] = $province;
            $addressIns["city"] = $city;
            $addressIns["area"] = $area;
            $addressIns["address"] = $address;

            $addressIns["is_default"] = 1;

            DB::name('StoreMemberAddress')->insert($addressIns);
        }

        do{
            $error = 0;
            $orderCn = $this->new_order_sn();  //获得新订单号
            $oCount = Db::name('StoreOrder')->field('COUNT(id) AS o_count')->where('order_no',$orderCn)->find();
            if($oCount['o_count'] > 0){
                $error = 1;
            }
        }while($error == 1);    //如果在订单记录中有重复订单号则重新生成

        $data['mid'] = $userID;
        $data['order_no'] = $orderCn;
        $data['price_total'] = $payAmount; //待付金额，即总金额
        $data['price_goods'] = $goodsPrice; //商品金额
        $data['price_express'] = $expressPrice; //快递金额
        $data['price_discount'] = $price_discount; //优惠
        $data['pay_id'] = $payId; //支付方式ID
        $data['pay_type'] = $payId == 1?"微信支付":"支付宝"; //支付状态
        $data['status'] = 2; //订单状态为待支付状态
        $data['express_address_id'] = $address_id; //收货地址ID
        $data['express_name'] = $name; //收货人姓名
        $data['express_phone'] = $phone; //收货人手机
        $data['express_province'] = $province; //收货地址省份
        $data['express_city'] = $city; //收货地址城市
        $data['express_area'] = $area; //收货地址区域
        $data['express_address'] = $address; //收货详细地址
        $data['order_desc'] = $order_desc; //订单备注

        $orderId = Db::name('StoreOrder')->insertGetId($data);

        //设置优惠券
        if(!empty($coupon_id)){
            DB::name("StoreCoupon")->where(['id'=>$coupon_id])->update(['is_used'=>1, 'uid'=>$userID, 'use_date'=>date("Y-m-d H:i:s", time())]);
        }
        
        
        $cartIds = explode(",",$cartIds);

        $goods_names = '';
        //循环获取购物车ID并取得对应商品信息插入订单商品表
        foreach ($cartIds AS $v){
            $orderGoods = array();
            $goods = DB::name("StoreCart")->alias("c")->leftJoin("store_goods g","g.id = c.goods_id")
                ->where("c.id",$v)->field("g.*,c.goods_price AS buy_price,c.goods_number AS buy_num,c.spec_info,c.goods_spec")->find();
            $orderGoods['mid'] = $userID;
            $orderGoods['order_id'] = $orderId;
            $orderGoods['order_no'] = $orderCn;
            $orderGoods['goods_id'] = $goods['id'];
            $orderGoods['goods_title'] = $goods['title'];
            $orderGoods['goods_logo'] = $goods['logo'];
            $orderGoods['price_real'] = $goods['buy_price'] * $goods['buy_num'] ;
            $orderGoods['price_selling'] = $goods['buy_price'];
            $orderGoods['number_goods'] = $goods['buy_num'];
            $orderGoods['spec_info'] = $goods['spec_info'];
            $orderGoods['goods_spec'] = $goods['goods_spec'];

            DB::name("StoreOrderList")->insert($orderGoods);    //插入订单商品表
            DB::name("StoreCart")->where('id',$v)->delete();    //删除对应的购物车记录

            $goods_names .= $goods['title']."x".$goods['buy_num'].",";

        }

        $goods_names = substr($goods_names,0,-1);
        Db::name('StoreOrder')->where('order_no',$orderCn)->update(['order_info'=>$goods_names]);

        dump("订单号：".$orderCn.". 购买的商品信息：".$goods_names.". 订单金额：".$payAmount."。连接支付接口中");die();

        $is_mobile = self::is_mobile_request(); //判断是否手机移动端访问
        $is_Weixin = self::is_Weixin();

        if($payId == 1) {
            //是否生成过订单
            $where = array();
            $where['order_no'] = $orderCn;
            $sel = Db::name('StoreOrder')->where($where)->find();
            if(!$sel){
                $this->message('订单错误',$url);
            }

            if($is_mobile){
                $is_mobile = 1;

                if($is_Weixin){
                    header("Location: /wxpay/jsapi.php?order_sn=".$orderCn);
                    exit;

                }else{
                    header("Location: /wxpay/h5.php?order_sn=".$orderCn);
                    exit;
                }

            }else{
                $is_mobile = 0;
                //生成微信支付二维码
                $qrcode = self::wxpay($orderCn, $payAmount,$goods_names);
                $this->assign('qrcode',$qrcode);
            }


        }

//        if($payId == 1){
//            //引入支付宝支付（自己后定义的）
//            require_once APP_PATH."/alipay_wap/config.php";
//            require_once APP_PATH.'/alipay_wap/wappay/service/AlipayTradeService.php';
//            require_once APP_PATH.'/alipay_wap/wappay/buildermodel/AlipayTradeWapPayContentBuilder.php';
//
//            //是否生成过订单
//            $where = array();
//            $where['order_no'] = $orderCn;
//            $sel = Db::name('StoreOrder')->where($where)->find();
//            if(!$sel){
//                $this->error('订单错误');
//            }
//
//            header("Content-Type: text/html;charset=utf-8");
//
//            //商户订单号，商户网站订单系统中唯一订单号，必填
//            $out_trade_no = $sel['order_no'];
//
//            //订单名称，必填
//            $subject = $orderInfo;
//
//            //付款金额，必填
//            $total_amount = $payAmount;
////         $total_amount = 0.01;
//
//            //商品描述，可空
//            $body = '';
//
//            //超时时间
//            $timeout_express="1m";
//
//            $payRequestBuilder = new \AlipayTradeWapPayContentBuilder();
//            $payRequestBuilder->setBody($body);
//            $payRequestBuilder->setSubject($subject);
//            $payRequestBuilder->setOutTradeNo($out_trade_no);
//            $payRequestBuilder->setTotalAmount($total_amount);
//            $payRequestBuilder->setTimeExpress($timeout_express);
//
//            $payResponse = new \AlipayTradeService($config);
//            $result=$payResponse->wapPay($payRequestBuilder,$config['return_url'],$config['notify_url']);
//
//            return ;
//        }

        $this->assign('is_mobile',$is_mobile);
        $this->assign('payId',$payId);
        $this->assign('orderCn',$orderCn);
        $this->assign('price_goods',$goodsPrice);
        $this->assign('price_express',$expressPrice);
        $this->assign('payAmount',$payAmount);
        $this->assign('order_info',$goods_names);

        return $this->fetch();
    }

    /**
     * 结算生成订单并支付
     */
    public function toPay()
    {
        $userID = $this->app->session->get('uid');
        if(empty($userID)){
            $this->error('用户未登陆，请登陆后操作');
        }else{
            $data = [];

            $goodsId = $_POST['goods_id'];
            $goodsPrice = $_POST['goods_price'];
            $payAmount = $_POST['amount'];
            $payId = $_POST['pay_id'];
            $name = $_POST['name'];
            $phone = $_POST['phone'];
            $order_desc = $_POST['order_desc'];

            do{
                $error = 0;
                $orderCn = $this->new_order_sn();  //获得网站新订单号
                $oCount = Db::name('StoreOrder')->field('COUNT(id) AS o_count')->where('order_no',$orderCn)->find();
                if($oCount['o_count'] > 0){
                    $error = 1;
                }
            }while($error == 1);    //如果在订单记录中有重复订单号则重新生成

            $data['mid'] = $userID;
            $data['order_no'] = $orderCn;
            $data['price_total'] = $payAmount; //待付金额，即总金额
            $data['price_goods'] = $goodsPrice; //商品金额
            $data['pay_id'] = $payId; //支付方式ID
            $data['pay_type'] = $payId === 1?"微信支付":"支付宝"; //支付状态
            $data['status'] = 2; //订单状态为待支付状态
            $data['express_name'] = $name; //订单状态为待支付状态
            $data['express_phone'] = $phone; //订单状态为待支付状态
            $data['order_desc'] = $order_desc; //订单备注

            $orderId = Db::name('StoreOrder')->insertGetId($data);

            $goods_names = '';
            //商品信息插入订单商品表
            $orderGoods = array();
            $goods = DB::name("StoreGoods")->where("id",$goodsId)->find();
            $orderGoods['mid'] = $userID;
            $orderGoods['order_id'] = $orderId;
            $orderGoods['order_no'] = $orderCn;
            $orderGoods['goods_id'] = $goodsId;
            $orderGoods['goods_title'] = $goods['title'];
            $orderGoods['goods_logo'] = $goods['logo'];
            $orderGoods['price_real'] = $goodsPrice;
            $orderGoods['price_selling'] = $goodsPrice;
            $orderGoods['number_goods'] = 1;
            DB::name("StoreOrderList")->insert($orderGoods);    //插入订单商品表

            $goods_names .= $goods['title'];
            Db::name('StoreOrder')->where('order_no',$orderCn)->update(['order_info'=>$goods_names]);

            $is_mobile = self::is_mobile_request(); //判断是否手机移动端访问
            $is_Weixin = self::is_Weixin();

            if($payId == 1) {

                if($is_mobile){
                    $is_mobile = 1;

                    if($is_Weixin){
                        header("Location: /public/tools/wxpay/jsapi.php?order_sn=".$orderCn);
                        exit;

                    }else{
                        header("Location: /public/tools/wxpay/h5.php?order_sn=".$orderCn);
                        exit;
                    }

                }else{
                    $is_mobile = 0;
                    //生成微信支付二维码
                    $qrcode = self::wxpay($orderCn, $payAmount,$goods_names);
                    $this->assign('qrcode',$qrcode);
                }


            }
            elseif($payId == 2){
                    # 在项目中加载初始化文件
                    include "public/tools/developer/include.php";
                    include "public/tools/developer/config_aliPay.php";
                    try {
                        if($is_mobile){
                            $pay = We::AliPayWap($config);
                        }else{
                            // 实例支付对象
                            $pay = We::AliPayWeb($config);
                            // $pay = new \AliPay\Web($config);
                        }
                        // 参考链接：https://docs.open.alipay.com/api_1/alipay.trade.page.pay
                        $result = $pay->apply([
                            'out_trade_no' => $orderCn, // 商户订单号
                            'total_amount' => $payAmount,    // 支付金额
//                            'total_amount' => 0.01,    // 支付金额
                            'subject'      => $goods_names, // 支付订单描述
                        ]);
                        echo $result; // 直接输出HTML（提交表单跳转)
                    } catch (Exception $e) {
                        // 异常处理
                        echo $e->getMessage();
                    }
            }
            elseif($payId == 3){
                # Paypal接口
                
            }

            $this->assign('is_mobile',$is_mobile);
            $this->assign('payId',$payId);
            $this->assign('orderCn',$orderCn);
            $this->assign('price_goods',$goodsPrice);
            $this->assign('payAmount',$payAmount);
            $this->assign('order_info',$goods_names);


        }

        return $this->fetch('done');
    }
    
    /**
     * 加入购物车
     */
    public function add_to_cart()
    {
        $userID = $this->app->session->get('uid');
        if($userID == 0){
            $this->redirect('/login');
        }
        
        $id = $_POST['id'];
        $goods_number = $_POST['goods_number'];
        $goods_price = $_POST['goods_price'];
        $callBack = array('code'=>0,'info'=>'','data'=>'');
        
        $StoreGoods = DB::name('StoreGoods')->where('id',$id)->find(); //获得商品详情数据
        $goods_cur_price = Goods::goods_price($StoreGoods['id'],$StoreGoods['goods_price']);
        
        $goods_price =$goods_cur_price['discount_price'];
        
        if(empty($userID)){
            $this->error("Please log in to the member!","/member");
        }
        
        //检查商品是否已加入过购物车
        $cartCount = Db::name('StoreCart')->field("count(id) AS c_count,goods_number")
            ->where(['goods_id'=>$id, 'member_id'=>$userID])->find();
        
        if($cartCount['c_count'] > 0){
            if($goods_number == 0){
                //如果数量为0直接删除该购物车里的商品
                Db::name('StoreCart')->where(['goods_id'=>$id, 'member_id'=>$userID])->delete();
            }else{
                //如果购物车有对应商品，则更新该商品在购物车的数量
                Db::name('StoreCart')->where(['goods_id'=>$id, 'member_id'=>$userID])->update(["goods_number" => $goods_number,'goods_price' => $goods_price]);
                
            }
            
            $cart_num = Db::name('StoreCart')->field("sum(goods_number) AS cart_count")
                ->where(['member_id'=>$userID])->find();
            $this->success("已成功添加至购物车",$cart_num['cart_count']>0?$cart_num['cart_count']:0);
            
        }
        
        //获得商品信息
        $goods = Db::name('StoreGoods')->where('id',$id)->find();
        
        $cartGoods = array('member_id' => $userID,
                           'goods_id' => $id,
                           'goods_name' => $goods['title'],
                           'goods_logo' => $goods['logo'],
                           'goods_number' => $goods_number,
                           'goods_price' => $goods_price,
                           'goods_cat' => $goods['cate_id'],
                           'goods_type' => $goods['goods_type']);
        
        $return = Db::name('StoreCart')->insert($cartGoods);
        
        if($return > 0){
            $cart_num = Db::name('StoreCart')->field("sum(goods_number) AS cart_count")
                ->where(['member_id'=>$userID])->find();
            
            $this->success("已成功添加至购物车",$cart_num['cart_count']>0?$cart_num['cart_count']:0);
        }else{
            $this->error("Failed to add goods to the shopping cart");
        }
    }

    /**
     * 支付完成后收取商品隐秘信息
     */
    public static function done_info()
    {
        $orderId = $_GET['id'];
        var_dump($orderId);die();
    }
    
    /**
     * Ajax修改购物车商品数量
     */
    public function set_num()
    {
        $userID = $this->app->session->get('uid');
//        dump($_POST);die();
        $cartId = $_POST['cart_id'];
        $num = $_POST['num'];
        $goodsId = $_POST['goods_id'];
        $callBack = array('error_code'=>0, 'error_smg'=>'');
        
        //查询购物里对应的商品并查询这个商品是否还有库存（防止并发访问购买）
        $Goods = DB::name('StoreGoods')->field('goods_number')->where(['id'=>$goodsId])->find();
        if($Goods['goods_number'] == 0){
            $callBack['error_code'] = 2;
            $callBack['error_smg'] = "此商品已被售完";
            echo json_encode($callBack);
            exit;
        }
        
        DB::name('StoreCart')->where(['member_id'=>$userID,'id'=>$cartId])->update(['goods_number'=>$num]);
        $callBack['error_code'] = 0;
        $callBack['error_smg'] = "";
        
        echo json_encode($callBack);
    }
    
    /**
     * Ajax删除购物车商品
     */
    public function del()
    {
        $id = $_POST['id'];
        $callBack = array('code'=>0,'info'=>'','data'=>'');
        
        $userID = $this->app->session->get('uid');
        //删除该商品
        Db::name('StoreCart')->where(['id'=>$id, 'member_id'=>$userID])->delete();
        $callBack['info'] = "删除成功！";
        echo json_encode($callBack);
        exit;
        
    }
    
    /**
     * Ajax检测优惠并计算优惠价格及订单价格
     */
    public function coupon_price()
    {
        $userID = $this->app->session->get('uid');
        if($userID == 0){
            $this->error('Please Login!');
        }
        
        $data = $this->request->param();
        $couponInfo = DB::name('StoreCoupon')->where(['code'=>$data['coupon_code']])->find();
        if(empty($couponInfo)){
            $this->error("无此优惠券或此优惠券被禁用！");
        }else{
            if ($couponInfo['is_used'] == 99){
                $this->error("无此优惠券或此优惠券被禁用！");
            }
            if ($couponInfo['is_used'] == 1){
                $this->error("对不起，此优惠券已被使用！");
            }
            if($couponInfo['reduction'] > $data['goods_price']){
                $this->error("对不起，此优惠券不适用！");
            }
            //根据优惠券类型计算价格
            if ($couponInfo['code_type'] == 1){
                //折扣券
                $new_price = $data['goods_price'] * ($couponInfo['rate']/100);
                $discount_rate = $data['goods_price'] - $new_price;
                $this->success("1",['new_price'=>$new_price, 'discount'=>$discount_rate, 'rate'=>$couponInfo['rate'],'coupon_id'=>$couponInfo['id']]);
            }else{
                //满减券
                $new_price = $data['goods_price'] - $couponInfo['discount'];    //原价-优惠价=最新价格
                $this->success("0",['new_price'=>$new_price, 'discount'=>$couponInfo['discount'],'coupon_id'=>$couponInfo['id']]);
            }
            
        }
        dump($couponInfo);die();
    }
    
    /**
     * 生成订单号
     */
    public static function new_order_sn()
    {
        /* 选择一个随机的方案 */
        mt_srand((double) microtime() * 1000000);
        
        return date('Ymd') . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * 获得快递运费
     * goods_id 商品ID
     * goods_price 商品价格(可选)
     */
    public static function express_price($express_province = '')
    {
        $expressTemplate = Db::name('storeExpressTemplate')->field("rule,first_price")
            ->where('is_default', 0)->order("id ASC")->select()->toArray();   //查询是否为活动折扣

        $price = 0;
        $province_list = [];
        foreach ($expressTemplate AS $key => $value){
            $province_list = explode(",",$value['rule']);

            if(in_array($express_province, $province_list)){
                $price = $value['first_price'];
            }
        }
        if($price == 0){
            $expressTemplateDefault = Db::name('storeExpressTemplate')->field("first_price")->where('is_default', 1)->find();   //查询是否为活动折扣
            $price = $expressTemplateDefault['first_price'];
        }

        return $price;
    }

    /**
     * 微信支付
     * order_sn 订单ID
     * price    支付价格
     */
    public static function wxpay($order_sn,$price,$goods_names)
    {
        $price = $price * 100;
        require_once "public/tools/wxpay/native.php";

        return $qrcode;
    }

    /**
     * 微信支付回调处理
     */
    public function wxpay_return()
    {
        $data = [];
        if($this->request->isGet()){
            $order_no = isset($_GET['out_trade_no'])?$_GET['out_trade_no']:"";
            $price_total = isset($_GET['total_fee'])?$_GET['total_fee']:0;
            $data['pay_price'] = $price_total / 100;
            $wx_transaction = isset($_GET['transaction_id'])?$_GET['transaction_id']:"";
            $pay_at = isset($_GET['time_end'])?$_GET['time_end']:"";
            $data['pay_at'] = date("Y-m-d H:i:s", strtotime($pay_at));
        }else{
            $order_no = isset($_POST['out_trade_no'])?$_POST['out_trade_no']:"";
            $price_total = isset($_POST['total_fee'])?$_POST['total_fee']:0;
            $data['pay_price'] = $price_total / 100;
            $wx_transaction = isset($_POST['transaction_id'])?$_POST['transaction_id']:"";
            $pay_at = isset($_POST['time_end'])?$_POST['time_end']:"";
            $data['pay_at'] = date("Y-m-d H:i:s", strtotime($pay_at));
        }

        $testxml  = file_get_contents("php://input");

        $jsonxml = json_encode(simplexml_load_string($testxml, 'SimpleXMLElement', LIBXML_NOCDATA));

        $result = json_decode($jsonxml, true);//转成数组，
        if($result){
            //如果成功返回了
            $out_trade_no = $result['out_trade_no'];
            if($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS'){
                //执行业务逻辑
                $order_no = isset($result['out_trade_no'])?$result['out_trade_no']:"";
                $price_total = isset($result['total_fee'])?$result['total_fee']:0;
                $data['pay_price'] = $price_total / 100;
                $wx_transaction = isset($result['transaction_id'])?$result['transaction_id']:"";
                $pay_at = isset($result['time_end'])?$result['time_end']:"";
                $data['pay_at'] = date("Y-m-d H:i:s", strtotime($pay_at));
            }
        }



        $data['status'] = 5;
        $data['pay_state'] = 1;
        $data['pay_type'] = "微信支付";
        $data['pay_no'] = $wx_transaction;

        Db::name('storeOrder')->where(['order_no'=>$order_no])->update($data);

        //记录日志
        $inLog = DB::name('StoreMemberPaymentLog')->where(['order_no'=>$order_no])->count();
        if(empty($inLog)){
            $orderInfo = Db::name('storeOrder')->where('order_no', $order_no)->find();   //查询是否为活动折扣
            $logData['member_id'] = $orderInfo['mid'];
            $logData['in_or_ex'] = "0";
            $logData['price_amount'] = $data['pay_price'];
            $logData['payment_type'] = $data['pay_type'];
            $logData['order_no'] = $orderInfo['order_no'];
            $logData['payment_desc'] = "商城订单号-".$order_no."。支付金额-¥ ".$data['pay_price']."。支付单号-".$wx_transaction;
            $logData['for_admin'] = 0;
            DB::name('StoreMemberPaymentLog')->insert($logData);
        }
        if($this->request->isGet()){
            $this->redirect('/index/Member/orders?order_no='.$order_no);
        }

    }

    /**
     * 支付宝支付回调处理
     */
    public function alipay_return()
    {
        $data = [];
        if($this->request->isGet()){
            $order_no = isset($_GET['out_trade_no'])?$_GET['out_trade_no']:"";
            $price_total = isset($_GET['total_amount'])?$_GET['total_amount']:0;
            $data['pay_price'] = $price_total;
            $wx_transaction = isset($_GET['trade_no'])?$_GET['trade_no']:"";
            $data['pay_at'] = $_GET['timestamp'] ?? date("Y-m-d H:i:s", time());
        }else{
            $order_no = isset($_POST['out_trade_no'])?$_POST['out_trade_no']:"";
            $price_total = isset($_POST['total_amount'])?$_POST['total_amount']:0;
            $data['pay_price'] = $price_total;
            $wx_transaction = isset($_POST['trade_no'])?$_POST['trade_no']:"";
            $data['pay_at'] = $_POST['timestamp'] ?? date("Y-m-d H:i:s", time());
        }


        $data['status'] = 5;
        $data['pay_state'] = 1;
        $data['pay_type'] = "支付宝";
        $data['pay_no'] = $wx_transaction;

        DB::name('storeOrder')->where(['order_no'=>$order_no])->update($data);

        //记录日志
        $inLog = DB::name('StoreMemberPaymentLog')->where(['order_no'=>$order_no])->count();
        if(empty($inLog)){
            $orderInfo = Db::name('storeOrder')->where('order_no', $order_no)->find();   //查询是否为活动折扣
            $logData['member_id'] = $orderInfo['mid'];
            $logData['in_or_ex'] = "0";
            $logData['price_amount'] = $data['pay_price'];
            $logData['payment_type'] = $data['pay_type'];
            $logData['order_no'] = $orderInfo['order_no'];
            $logData['payment_desc'] = "商城订单号-".$order_no."。支付金额-¥ ".$data['pay_price']."。支付单号-".$wx_transaction;
            $logData['for_admin'] = 0;
            DB::name('StoreMemberPaymentLog')->insert($logData);
        }
        if($this->request->isGet()){
            $this->redirect('/index/Member/orders?order_no='.$order_no);
        }

    }

    /**
     * //判断是否手机设备
     * @return boolean
     */
    public static function is_mobile_request()
    {
        $_SERVER['ALL_HTTP'] = isset($_SERVER['ALL_HTTP']) ? $_SERVER['ALL_HTTP'] : '';
        $mobile_browser = '0';
        if(preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|iphone|ipad|ipod|android|xoom)/i', strtolower($_SERVER['HTTP_USER_AGENT'])))
            $mobile_browser++;
        if((isset($_SERVER['HTTP_ACCEPT'])) and (strpos(strtolower($_SERVER['HTTP_ACCEPT']),'application/vnd.wap.xhtml+xml') !== false))
            $mobile_browser++;
        if(isset($_SERVER['HTTP_X_WAP_PROFILE']))
            $mobile_browser++;
        if(isset($_SERVER['HTTP_PROFILE']))
            $mobile_browser++;
        $mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'],0,4));
        $mobile_agents = array(
            'w3c ','acs-','alav','alca','amoi','audi','avan','benq','bird','blac',
            'blaz','brew','cell','cldc','cmd-','dang','doco','eric','hipt','inno',
            'ipaq','java','jigs','kddi','keji','leno','lg-c','lg-d','lg-g','lge-',
            'maui','maxo','midp','mits','mmef','mobi','mot-','moto','mwbp','nec-',
            'newt','noki','oper','palm','pana','pant','phil','play','port','prox',
            'qwap','sage','sams','sany','sch-','sec-','send','seri','sgh-','shar',
            'sie-','siem','smal','smar','sony','sph-','symb','t-mo','teli','tim-',
            'tosh','tsm-','upg1','upsi','vk-v','voda','wap-','wapa','wapi','wapp',
            'wapr','webc','winw','winw','xda','xda-'
        );
        if(in_array($mobile_ua, $mobile_agents))
            $mobile_browser++;
        if(strpos(strtolower($_SERVER['ALL_HTTP']), 'operamini') !== false)
            $mobile_browser++;
        // Pre-final check to reset everything if the user is on Windows
        if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows') !== false)
            $mobile_browser=0;
        // But WP7 is also Windows, with a slightly different characteristic
        if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows phone') !== false)
            $mobile_browser++;
        if($mobile_browser>0)
            return true;
        else
            return false;
    }

    /**
     * //判断是否微信浏览器
     * @return boolean
     */
    public static function is_Weixin() {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            return true;
        } else {
            return false;
        }
    }

}