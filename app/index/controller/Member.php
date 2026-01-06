<?php
namespace app\index\controller;

use app\index\model\DataUser;
use app\index\controller\Custom;
use think\admin\Controller;
use think\facade\Db;
use think\facade\Route;
use think\admin\extend\CodeExtend;
use think\admin\service\CaptchaService;
use think\Session;
use We;


/**
 * Class Index
 * @package app\index\controller
 */
class Member extends Controller
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
//        $nav = $this->app->db->name('DataNav')->where(['parent_id'=>0,'type'=>'顶部','status'=>1,'is_deleted'=>0])->select()->toArray();
        $nav = $GLOBALS['function']->getAllCat();      //获得网站导航
        $this->assign('nav',$nav);

        //获得内容模型列表(用于搜索功能)
        $this->assign('modelList',$GLOBALS['function']->getModelList());

        //检查未读消息数量
        $msg_count = $this->app->db->name('DataMsg')->where(['receiver_id'=>$userID,'status'=>1,'is_deleted'=>0,'is_new'=>1])->count();
        $this->assign('msg_count',$msg_count);
    }

    /*个人中心*/
    public function index()
    {
        $userID = $this->app->session->get('uid');
        if (empty($userID)) {
            $this->redirect(sysuri('/login'));
        }

        //member info
        $member = DB::name('DataUser')->where(['id'=>$userID,'status'=>1])->find();
        $infoIntegrity = 0;
        if(!empty($member['nickname'])){
            $infoIntegrity += 15;
        }
        if(!empty($member['email'])){
            $infoIntegrity += 15;
        }
        if(!empty($member['qq'])){
            $infoIntegrity += 15;
        }
        if(!empty($member['region_province']) && !empty($member['region_city']) && !empty($member['region_area'])){
            $infoIntegrity += 30;
        }
        if(!empty($member['address'])){
            $infoIntegrity += 25;
        }
        //资料完整度
        $this->assign('integrity',$infoIntegrity);

        $this->assign('on','index');

        return $this->fetch();
    }

    /*完善资料*/
    public function profile()
    {
        if($this->request->isPost()){
            $data = $_POST;

//            $data['region_province'] = substr($data['region_province'],strripos($data['region_province'],".")+1);
//            $data['region_city'] = substr($data['region_city'],strripos($data['region_city'],".")+1);
//            $data['region_area'] = substr($data['region_area'],strripos($data['region_area'],".")+1);
//
//            $addressCount = count($data['addressConsignee']);
//            for($i=0;$i<$addressCount;$i++){
//                if(empty($data['addressConsignee'][$i]) && empty($data['addressPhone'][$i]) && empty($data['addressInfo'][$i])){
//                    //如果整行未填写，直接清除该行数组
//                    unset($data['addressConsignee'][$i],$data['addressPhone'][$i],$data['addressInfo'][$i]);
//                }else{
//                    $data['address'][$i]['addressConsignee'] = $data['addressConsignee'][$i];
//                    $data['address'][$i]['addressPhone'] = $data['addressPhone'][$i];
//                    $data['address'][$i]['addressInfo'] = $data['addressInfo'][$i];
//
//                    //清除该行数组
//                    unset($data['addressConsignee'][$i],$data['addressPhone'][$i],$data['addressInfo'][$i]);
//                }
//            }
//            //清除所有地址列表数组
//            if(empty(count($data['addressConsignee']))){
//                unset($data['addressConsignee']);
//            }
//            if(empty(count($data['addressPhone']))){
//                unset($data['addressPhone']);
//            }
//            if(empty(count($data['addressInfo']))){
//                unset($data['addressInfo']);
//            }
//
//            $data['address'] = json_encode($data['address']);

            DataUser::mSave($data,'',['id'=>$data['id']]);
            $this->success('资料修改成功!');
        }

        //判断用户登陆状态
        $userID = $this->app->session->get('uid');
        if (empty($userID)) {
            $this->redirect(sysuri('/login'));
        }

        //member info
//        $member = DB::name('DataUser')->where(['id'=>$userID,'status'=>1])->find();
//        /*获取用户所有收货地址*/
//        $member['address'] = json_decode($member['address'], true);
//        if(empty($member['address'])){
//            $member['addressCount'] = 0;
//        }else{
//            $member['addressCount'] = count($member['address']);
//        }
//        $this->assign('member',$member);

        $infoIntegrity = 0;
        if(!empty($member['nickname'])){
            $infoIntegrity += 15;
        }
        if(!empty($member['email'])){
            $infoIntegrity += 15;
        }
        if(!empty($member['qq'])){
            $infoIntegrity += 15;
        }
        if(!empty($member['region_province']) && !empty($member['region_city']) && !empty($member['region_area'])){
            $infoIntegrity += 30;
        }
        if(!empty($member['address'])){
            $infoIntegrity += 25;
        }
        //资料完整度
        $this->assign('integrity',$infoIntegrity);

        //联动地区省份列表
        $province = DB::name('SystemRegion')->field("id,parent_id,region_name AS name,region_type")->where(['region_type'=>1])->select();
        $this->assign('province',$province);
        //联动地区城市列表
        $city = DB::name('SystemRegion')->field("id,parent_id,region_name AS name,region_type")->where(['region_type'=>2])->select();
        $this->assign('city',$city);
        //联动地区县区列表
        $district = DB::name('SystemRegion')->field("id,parent_id,region_name AS name,region_type")->where(['region_type'=>3])->select();
        $this->assign('district',$district);

        $this->assign('on','profile');

        return $this->fetch();
    }

    /*注册*/
    public function register()
    {
        if ($this->app->request->isGet()) {
            $userID = $this->app->session->get('uid');
            if (!empty($userID)) {
                $this->redirect(sysuri('/member'));
            } else {
                $this->fetch();
            }
        }else{
            $data = $this->_vali([
                'username.require' => '登录账号不能为空!',
                'username.min:4'   => '登录账号不能少于4位字符!',
                'password.require' => '登录密码不能为空!',
                'password.min:4'   => '登录密码不能少于4位字符!',
            ]);
            $data=$_POST;
            unset($data["form_id"],$data["type"],$data["c_password"]);

            /*! 用户信息验证 */
            $map = ['username' => $data['username']];
            $user = DataUser::mk()->where($map)->findOrEmpty();

            if (!$user->isEmpty()) {
                $this->error('This account has been registered!');
            }

            $data['phone'] = $_POST['username'];
//            $data['nickname'] = $_POST['nickname'];
            $data['password'] = md5($_POST['c_password']);
            unset($data['verify'],$data['uniqid']);

            $newUserId = $this->app->db->name("DataUser")->insertGetId($data);
            if($newUserId > 0){
                $userInfo = $this->app->db->name("DataUser")->where(['id'=>$newUserId])->find();
                $this->app->session->set('userInfo', $userInfo);
                $this->app->session->set('uid', $newUserId);
                $this->success('注册成功!','/member');
            }

        }

        return $this->fetch('register');
    }

    /**
     * 前台登录入口
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function login()
    {

        if ($this->app->request->isGet()) {
            $userID = $this->app->session->get('uid');

            if (!empty($userID)) {
                $this->redirect(sysuri('/index/index/index'));
            } else {

                $this->title = '系统登录';
                $this->captchaType = 'LoginCaptcha';
                $this->captchaToken = CodeExtend::uniqidDate(18);

                $this->backgrounds = strtr(sysconf('login_image') ?: '', '|', ',');

                // 标记登录验证令牌
                if (!$this->app->session->get('LoginInputSessionError')) {
                    $this->app->session->set($this->captchaType, $this->captchaToken);
                }
                $this->fetch();
            }
        } else {
            $data = $this->_vali([
                'username.require' => '登录账号不能为空!',
                'username.min:4'   => '登录账号不能少于4位字符!',
                'password.require' => '登录密码不能为空!',
                'password.min:4'   => '登录密码不能少于4位字符!',
            ]);

            /*! 用户信息验证 */
            $map = ['username' => $data['username'], 'deleted' => 0];
            $user = DataUser::mk()->where($map)->findOrEmpty();
            if ($user->isEmpty()) {
                $this->app->session->set("LoginInputSessionError", true);
                $this->error('登录账号或密码错误，请重新输入!');
            }
            if (empty($user['status'])) {
                $this->app->session->set("LoginInputSessionError", true);
                $this->error('账号已经被禁用，请联系管理员!');
            }

            if (md5($data['password']) !== $user['password']) {
                $this->app->session->set("LoginInputSessionError", true);
                $this->error('登录账号或密码错误，请重新输入!');
            }
            $this->app->session->set('userInfo', $user->toArray());
            $this->app->session->delete("LoginInputSessionError");

            $this->app->session->set('uid', $user['id']);


            $this->success('登录成功','/member');
        }
    }

    /**
     * 生成验证码
     */
    public function captcha()
    {
        $input = $this->_vali([
            'type.require'  => '验证码类型不能为空!',
            'token.require' => '验证码标识不能为空!',
        ]);
        $image = CaptchaService::instance()->initialize();
        $captcha = ['image' => $image->getData(), 'uniqid' => $image->getUniqid()];
        if ($this->app->session->get($input['type']) === $input['token']) {
            $captcha['code'] = $image->getCode();
            $this->app->session->delete($input['type']);
        }
        $this->success('生成验证码成功', $captcha);
    }

    /**
     * 用户订单支付
     */
    public function pay()
    {
        $orderId = $_GET['id'];
        $payId = $_GET['pay_id'];
        $goods_title = $_GET['goods'];

        $userID = $this->app->session->get('uid');
        if (empty($userID)) {
            $this->redirect(sysuri('/login'));
        }

        $order = Db::name('StoreOrder')->where(['id'=>$orderId, 'mid'=>$userID])->find();
        $orderCn = $order['order_no'];
        $payAmount = $order['price_total'];

        //订单商品信息
        $goodsInfo = Db::name('StoreOrderList')->where('order_no',$order['order_no'])->select();
        $goods_names = "";
        foreach ($goodsInfo AS $v){
            $goods_names .= $v['goods_title'];
        }
//        dump($order['order_no']);die();

        $is_mobile = Flow::is_mobile_request(); //判断是否手机移动端访问
        $is_Weixin = Flow::is_Weixin();     //判断是否微信浏览器访问

        if($payId == 1){
            //是否生成过订单
            $where = array();
            $where['order_no'] = $order['order_no'];
            $sel = Db::name('StoreOrder')->where($where)->find();
            if(!$sel){
                $this->message('订单错误');
            }


//            $goods_names = substr($goods_names,0,-1);

            if($is_mobile){
                $is_mobile = 1;


                if($is_Weixin){
                    header("Location: /wxpay/jsapi.php?order_sn=".$order['order_no']);
                    exit;

                }else{
                    header("Location: /wxpay/h5.php?order_sn=".$order['order_no']);
                    exit;
                }


            }else{
                $is_mobile = 0;
                //生成微信支付二维码
                $qrcode = Flow::wxpay($order['order_no'], $order['price_total'],$order['order_info']);
                $this->assign('qrcode',$qrcode);
            }


        }else{
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
//                            'total_amount' => $payAmount,    // 支付金额
                    'total_amount' => 0.01,    // 支付金额
                    'subject'      => $goods_names, // 支付订单描述
                ]);
                echo $result; // 直接输出HTML（提交表单跳转)
            } catch (Exception $e) {
                // 异常处理
                echo $e->getMessage();
            }
        }

        $this->assign('is_mobile',$is_mobile);
        $this->assign('payId',$payId);
        $this->assign('orderCn',$order['order_no']);
        $this->assign('price_goods',$order['price_goods']);
        $this->assign('price_express',$order['price_express']);
        $this->assign('payAmount',$order['price_total']);
        $this->assign('order_info',$order['order_info']);

        return $this->fetch();
    }

    /**
     * 用户取消订单
     */
    public function cancel_order()
    {
        $userID = $this->app->session->get('uid');
        if (empty($userID)) {
            $this->error("用户登录超时，请重新登录");
        }
        $order_id = $_POST['order_id'];
        DB::name('StoreOrder')->where(['id'=>$userID,'id'=>$order_id])->update(['status'=>0]);
//        $this->success('取消订单成功！', 'javascript:history.back()');
        $this->success('取消订单成功！', '/index/member/orders');
    }

    /**
     * 退出登录
     */
    public function logout()
    {
//        $this->app->session->clear();
//        $this->app->session->destroy();
        $this->app->session->delete('uid');
        $this->app->session->delete('userInfo');
        $this->success('登出成功');
    }

    /**
     * 用户订单页
     */
    public function orders()
    {
        $userID = $this->app->session->get('uid');
        if (empty($userID)) {
            $this->redirect(sysuri('/login'));
        }
        $where = array('mid'=>$userID);
    
        $on = 0;
        //筛选
        if(isset($_GET['status'])){
            $where['status'] = $on = $_GET['status'];
        }
        $this->assign('active',$on);
    
        //order info
        $list = DB::name('StoreOrder')->where($where)->order("create_at desc,id desc")->paginate(8,false,['query' => request()->param()])->each(function($item, $key){
            //循环操作
            $item['goods_names'] = "";
            $item['goodsList'] = DB::name('StoreOrderList')->where(['order_no'=>$item['order_no']])->order("id desc")->select()->toArray(); //获取数据集中的id
            foreach ($item['goodsList'] AS $k => $v){
                $item['goodsList'][$k]['goods_total'] = number_format($v['price_real'], 2);
                $item['goods_names'] .= $v['goods_title'].",";
            }
            $item['goods_names'] = substr($item['goods_names'],0,-1);
            return $item;
        });
    
        $page = $list->render();
    
        $this->assign('page', $page);
        $this->assign('order',$list);
    
        $this->assign('on','orders');
    
        return $this->fetch();
    }
    
    /**
     * 用户收藏
     */
    public function new_shoucang(){
        $userID = $this->app->session->get('uid');
        if (empty($userID)) {
            $this->redirect(sysuri('/login'));
        }
        
        $this->assign('onName','member');
        
        //获得所有产品列表
        //获得每页设置显示数量
        $pageNum = 20;
        
        $list = DB::name('StoreGoods')->where(['shoucang'=>1,'status'=>1])
            ->order('sort desc,create_at desc')
            ->paginate($pageNum,false,['query' => request()->param()])->each(function($item, $key){
                //循环操作
                
                
                
                return $item;
            });
        $page = $list->render();
        $this->assign('page', $page);
        $this->assign('shoucang_list',$list);    //获得商品列表
        
        return $this->fetch();
        
    }
    
    /**
     * 用户地址簿-列表
     */
    public function address(){
        $userID = $this->app->session->get('uid');
        if (empty($userID)) {
            $this->redirect(sysuri('/login'));
        }
        
        /*获取用户所有收货地址*/
        $addressList = DB::name('DataUserAddress')->where(['uuid'=>$userID])->order("is_default DESC,id asc")->select();
        $this->assign('addressList',$addressList);
    
        //联动地区省份列表
        $province = DB::name('SystemRegion')->field("id,parent_id,region_name AS name,region_type")->where(['region_type'=>1])->select();
        $this->assign('province',$province);
    
        return $this->fetch();
    }
    
    /**
     * 用户地址簿-列表/新增
     */
    public function new_address(){
        $userID = $this->app->session->get('uid');
        if (empty($userID)) {
            $this->redirect(sysuri('/login'));
        }
        
        $this->assign('onName','member');
        
        //接收表单提交
        if ($this->app->request->isPost()) {
            $data = $this->app->request->post();

//             $data["province"] = substr($data["province"],strripos($data["province"],".")+1);
//             $data["city"] = substr($data["city"],strripos($data["city"],".")+1);
//             $data["area"] = substr($data["area"],strripos($data["area"],".")+1);
            
            //检查是否第一次创建
            $newAdd = DB::name('DataUserAddress')->where(['uuid'=>$userID])->count();
            
            //如果创建数量超过5个，不允许创建
            if($newAdd >= 5){
                $this->error("创建收货地址数量上限为5条");
            }

            if(empty($newAdd)){
                $data["is_default"] = 1;
            }
            
            $data["uuid"] = $userID;
//             if($data['province'] == '海外'){
//                 $data['city'] = '';
//                 $data['area'] = '';
//             }
            
            $return = DB::name('DataUserAddress')->insertGetId($data);
            if(empty($return)){
                $this->success("数据保存失败！");
            }
            $this->set_default($return);
            
            $this->success("数据保存成功！");
        }else{
            /*获取用户所有收货地址*/
            $addressList = DB::name('DataUserAddress')->where(['uuid'=>$userID])->order("is_default DESC,id asc")->select();
            $this->assign('addressList',$addressList);
            
            //联动地区省份列表
            $province = DB::name('SystemRegion')->field("id,parent_id,region_name AS name,region_type")->where(['region_type'=>1])->select();
            $this->assign('province',$province);
        }
        
        
        
        return $this->fetch();
    }
    
    /**
     * 用户地址簿-列表/修改
     */
    public function edit_address(){
        $userID = $this->app->session->get('uid');
        if (empty($userID)) {
            $this->redirect(sysuri('/login'));
        }
        $this->assign('onName','修改地址');
        
        //接收表单提交
        if (request()->isPost()) {
            $data = $_POST;
            $id = $data['id'];
            unset($data['id']);
            
//            $data["province"] = substr($data["province"],strripos($data["province"],".")+1);
//            $data["city"] = isset($data["city"])?substr($data["city"],strripos($data["city"],".")+1):"";
//            $data["area"] = isset($data["area"])?substr($data["area"],strripos($data["area"],".")+1):"";
//
//            if($data['province'] == '海外'){
//                $data['city'] = '';
//                $data['area'] = '';
//            }
            
            DB::name('DataUserAddress')->where(['uuid'=>$userID,'id'=>$id])->update($data);
            
            $this->success("数据保存成功！");
        }else{
            $id = $_GET['id'];
            
            /*获取用户所有收货地址*/
            $addressList = DB::name('DataUserAddress')->where(['uuid'=>$userID])->order("is_default DESC,id asc")->select();
            $this->assign('addressList',$addressList);
            
            /*获取用户正在编辑的收货地址*/
            $editAddress = DB::name('DataUserAddress')->where(['id'=>$id])->find();
            $this->assign('edit_address',$editAddress);
//            dump($editAddress);die();
            //联动地区省份列表
            $province = DB::name('SystemRegion')->field("id,parent_id,region_name AS name,region_type")->where(['region_type'=>1])->select();
            $this->assign('province',$province);
            //联动地区城市列表
            $city = DB::name('SystemRegion')->field("id,parent_id,region_name AS name,region_type")->where(['region_type'=>2])->select();
            $this->assign('city',$city);
            //联动地区县区列表
            $district = DB::name('SystemRegion')->field("id,parent_id,region_name AS name,region_type")->where(['region_type'=>3])->select();
            $this->assign('district',$district);
        }
        
        return $this->fetch();
    }
    
    /**
     * 用户设置默认地址
     */
    public function set_default($id)
    {
        $id = $_POST['id']??$id;
        $userID = $this->app->session->get('uid');
        //先设置该用户所有地址默认参数为0
        DB::name('DataUserAddress')->where(['uuid'=>$userID])->update(['is_default'=>0]);
        //后设置指定ID为默认地址
        DB::name('DataUserAddress')->where(['uuid'=>$userID,'id'=>$id])->update(['is_default'=>1]);
        
        $this->success('设置成功！');
    }
    
    /**
     * 用户删除地址
     */
    public function del_address()
    {
        $id = $_POST['id'];
        $userID = $this->app->session->get('uid');
        //后设置指定ID为默认地址
        DB::name('DataUserAddress')->where(['uuid'=>$userID,'id'=>$id])->delete();
        $this->success('删除成功！');
    }

    /*废弃*/
    public function findPassword()
    {
        if ($this->app->request->isGet()) {
            if(!isset($_SESSION['user_id'])){
                $_SESSION['user_id'] = 0;
            }

            if (!empty($_SESSION['user_id'])) {

                $this->redirect(sysuri('index/member/index'));
            } else {
                $this->title = '找回密码';
                $this->captchaType = 'LoginCaptcha';
                $this->captchaToken = CodeExtend::uniqidDate(18);

                $this->backgrounds = strtr(sysconf('login_image') ?: '', '|', ',');

                // 标记登录验证令牌
                if (!$this->app->session->get('LoginInputSessionError')) {
                    $this->app->session->set($this->captchaType, $this->captchaToken);
                }
                $this->fetch();
            }
        }

        return $this->fetch('register');
    }

    /*修改密码*/
    public function edit_password()
    {
        if($this->request->isPost()){
            $data = $_POST;
            $info = $this->app->db->name('DataUser')->field("id,password")->where(['id'=>$data['id'],'status'=>1])->find();

            //判断登陆密码是否与提交旧密码相同
            if($info['password'] === md5($data['old_password'])){
                $this->app->db->name('DataUser')->where(['id' => $info['id']])->update(['password'=>md5($data['password'])]);
                $this->success("密码修改成功！");
                exit;
            }else{
                //密码不匹配
                $this->error('旧密码错误！');
                exit;
            }
        }

        $this->assign('left_on',"edit_password");

        return $this->fetch();
    }

    /*短消息*/
    public function msg()
    {
        //短消息列表
        $userID = $this->app->session->get('uid');
        $msgList = $this->app->db->name('DataMsg')
                        ->where(['receiver_id'=>$userID,'status'=>1,'is_deleted'=>0])
                        ->order("create_at desc, id desc")
                        ->paginate(8,false,['query' => request()->param()])
                        ->each(function($item, $key){
                            return $item;
                        });
        $page = $msgList->render();
        $count = $msgList->total();

        $this->assign('page', $page);
        $this->assign('msgList',$msgList);
//        dump($msgList);die();
        $this->assign('left_on',"msg");

        //清除所有已读
        $this->app->db->name('DataMsg')->where(['receiver_id'=>$userID])->update(['is_new'=>0]);

        return $this->fetch();
    }

    /*删除短消息*/
    public function msg_del()
    {
        if($this->request->isPost()){
            $id = $_POST['id'];
            $info = $this->app->db->name('DataMsg')->where(['id'=>$id])->update(['is_deleted'=>1]);
            $this->success("删除成功！");
            exit;
        }

    }

    /*实名认证*/
    public function authentication()
    {
        if($this->request->isPost()){
            $data = $_POST;
            $info = $this->app->db->name('DataUser')->field("id,password")->where(['id'=>$data['id'],'status'=>1])->find();

            $this->app->db->name('DataUser')->where(['id' => $info['id']])->update($data);
            $this->success("实名认证已提交，请等待后台审核结果！");
            exit;
        }

        //要做后台里身份证下载链接   红字认证状态切换   认证通过过不可修改


        $this->assign('left_on',"authentication");

        return $this->fetch();
    }

    /* ★☆★☆★☆★☆★☆★☆★☆ AJAX方法部分 ★☆★☆★☆★☆★☆★☆★☆ */
    /**
     * Ajax修改头像
     */
    public function uploadImage(){

        $userID = $this->app->session->get('uid');
        /*处理旧头像*/
        $info = DB::name('DataUser')->field('headimg')->where(['id'=>$userID])->find();
        //获得旧头像地址
        $oldImg = parse_url($info['headimg']);
        $oldImgAll = $_SERVER["DOCUMENT_ROOT"]."/".$oldImg['path'];

        $photo = input('post.photo');

        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $photo, $result)){
            //图片后缀
            $type = $result[2];
            //保存位置--图片名
            $image_name=date('His').str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT).".".$type;
            $image_file_path = 'public/upload/headimg/'.date('Ymd');
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

            //解码
            $decode=base64_decode(str_replace($result[1], '', $photo));

            if (file_put_contents($imge_real_url, $decode)){
                //将用户上传的头像地址入库
                $where['id'] = $userID;
                $post['headimg'] = "/".$imge_web_url;
                DB::name('DataUser')->where($where)->update($post);
                //删除旧头像
                if($oldImg['path']){
                    unlink($oldImgAll);
                }
                $this->success("头像修改成功！");
            }else{
                $this->error("头像修改失败！");
            }
        }else{
            $this->error("base64图片格式有误！");
        }

//        return $data;

    }

    /**
     * 获得联动地区处理
     */
    public function region()
    {
        $key = isset($_POST['key'])?$_POST['key']:0; //获取值
        if(empty($key)){
            $result['status'] = 220;
        }
        else{
            $key = substr($key,0,strrpos($key ,"."));

            $expressInfo = DB::name('SystemRegion')->field("region_name")->where(['id'=>$key])->find();

            $num = DB::name('SystemRegion')->where(['parent_id'=>$key])->count();

            if($num == 0){ //无值，返回状态码220
                $result['status'] = 220;

            }else{ //有值，组装数据
                $row = DB::name('SystemRegion')->field("id,region_name AS name")->where(['parent_id'=>$key])->select();

                $result['status'] = 200;
                $result['data'] = $row;

            }
        }

        echo json_encode($result); //返回JSON数据
        exit;
    }
    
    /**
     * 获得用户选择的收货地址处理
     */
    public function get_address_info()
    {
        $userID = $this->app->session->get('uid');
        if($userID == 0){
            $this->error('Please Login!');
        }
        
        $address_id = isset($_POST['address_id'])?$_POST['address_id']:0; //获取值
        if(empty($address_id)){
            $result['status'] = 220;
        }
        else{
            /*获取用户设置的收货地址*/
            $num = DB('DataUserAddress')->where(['uuid'=>$userID,'id'=>$address_id])->count();
            
            if($num == 0){ //无值，返回状态码220
                $result['status'] = 220;
            }else{ //有值，组装数据
                $row = DB('DataUserAddress')->where(['uuid'=>$userID,'id'=>$address_id])->find();
                
                //获得省份地址ID
                $province_id = DB('SystemRegion')->field('region_id')->where(['region_name'=>$row['province']])->find();
                $row['province_id'] = $province_id['region_id'];
                //获得城市地址ID
                $province_id = DB('SystemRegion')->field('region_id')->where(['region_name'=>$row['city']])->find();
                $row['city_id'] = $province_id['region_id'];
                //获得省份地址ID
                $province_id = DB('SystemRegion')->field('region_id')->where(['region_name'=>$row['area']])->find();
                $row['area_id'] = $province_id['region_id'];
                
                $row['express_price'] = Flow::express_price($row['province']);
                
                $result['status'] = 200;
                $result['data'] = $row;
            }
        }
        
        echo json_encode($result); //返回JSON数据
        exit;
    }

    /**
     * 手机登录/注册接口处理
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function phone_login()
    {
        if ($this->app->request->isPost()) {
            $data = $this->app->request->param();
            $returnUrl = $data['return_url']??'/index';
            $data = $this->_vali([
                'username.require' => '登录账号不能为空!',
                'username.min:4'   => '登录账号不能少于4位字符!',
                'get_code.require' => '验证码不能为空!',
                'get_code.min:6'   => '验证码不能少于6位字符!',
            ]);
            
            //获取正确的加密手机短信验证码
            $codyKey = $this->app->session->get('code_key');
            
            if($codyKey !== md5(md5($data['get_code']))){
                $loginError = empty($this->app->session->get('LoginError'))?0:$this->app->session->get('LoginError');
                //如果验证次数超过5次
                if($loginError >= 5){
                    $this->app->session->delete('code_key');
                    $this->app->session->delete('LoginError');
                    $this->error('登陆失败次数过多，请重新发送验证码并登陆');
                }
                $loginError = $loginError +1;
                $this->app->session->set('LoginError', $loginError);
                $this->error('手机验证码错误');
                
            }
            
            /*! 用户信息验证 */
            $map = ['username' => $data['username'], 'deleted' => 0];
            $user = DataUser::mk()->where($map)->findOrEmpty();
            
            if ($user->isEmpty()) {
                //如无用户信息，即注册会员
                $info = ['username'=>$data['username'],
                         'phone'=>$data['username'],
                         'password'=>'4297f44b13955235245b2497399d7a93',
                         'auth_type'=>1,
                         'status'=>1];
                $newUserId = $this->app->db->name("DataUser")->insertGetId($info);
                if($newUserId > 0){
                    $userInfo = $this->app->db->name("DataUser")->where(['id'=>$newUserId])->find();
                    $this->app->session->set('userInfo', $userInfo);
                    $this->app->session->set('uid', $newUserId);
                    
                    $this->app->session->delete("LoginInputSessionError");
                    $this->success('注册并登陆成功!',$returnUrl);
                }
            }
            if (empty($user['status'])) {
                $this->app->session->set("LoginInputSessionError", true);
                $this->error('账号已经被禁用，请联系管理员!');
            }
            
            $this->app->session->set('userInfo', $user->toArray());
            $this->app->session->delete("LoginInputSessionError");
            $this->app->session->set('uid', $user['id']);
            
            
            $this->success('登录成功',$returnUrl);
        }
    }
    
    /**
     * 手机验证码发送获取
     * @return array
     */
    public function sms(){
        if (request()->isPost()){
            $phone = $this->request->param('phone');
            if ($phone){
                $params = array ();
                $security = false;
                //获取短信配置（阿里云短信配置）
                $smsConfig = $this->app->db->name("SystemSmsSetting")->where(['id'=>1])->find();
                $accessKeyId = $smsConfig['access_key_id'];         //阿里云帐号ID
                $accessKeySecret = $smsConfig['access_key_Secret']; //密钥
                $params["SignName"] = $smsConfig['sign_name'];      //短信签名
                $params["TemplateCode"] = $smsConfig['template_code'];  //短信模板
                $params["PhoneNumbers"] = $phone;
                $code  = mt_rand(100000,999999);//生成验证码
                $params['TemplateParam'] = Array (
                    "code" => $code
                );
                //验证码模板默认code为验证码字段
                if(!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
                    $params["TemplateParam"] = json_encode($params["TemplateParam"], JSON_UNESCAPED_UNICODE);
                }
                $content = $this->requestReturn($accessKeyId,$accessKeySecret,"dysmsapi.aliyuncs.com",array_merge($params, array("RegionId" => "cn-hangzhou","Action" => "SendSms","Version" => "2017-05-25",)), $security);
                
                if($content['Code'] == 'OK'){
                    //将code加密
                    $codyKey = md5(md5($code));
                    //将加密后的验证码存入session
                    $this->app->session->set('code_key', $codyKey);
                    
                    $this->success("发送成功！请注意查收短信验证码");
                    
                }else{
                    $this->error($content['Message']);
                }
            }else{
                $this->error("手机号获取错误，请检查！");
            }
            
        }
    }
    
    //执行总方法-阿里云短信
    public function requestReturn($accessKeyId, $accessKeySecret, $domain, $params, $security=false, $method='POST') {
        //塞数组
        $apiParams = array_merge(array (
            "SignatureMethod" => "HMAC-SHA1",
            "SignatureNonce" => uniqid(mt_rand(0,0xffff), true),
            "SignatureVersion" => "1.0",
            "AccessKeyId" => $accessKeyId,
            "Timestamp" => gmdate("Y-m-d\TH:i:s\Z"),
            "Format" => "JSON",
        ), $params);
        //排序
        ksort($apiParams);
        
        //生成string
        $sortedQueryStringTmp = "";
        foreach ($apiParams as $key => $value) {
            $sortedQueryStringTmp .= "&" . $this->encode($key) . "=" . $this->encode($value);
        }
        
        //string 补值
        $stringToSign = "${method}&%2F&" . $this->encode(substr($sortedQueryStringTmp, 1));
        
        //加密
        $sign = base64_encode(hash_hmac("sha1", $stringToSign, $accessKeySecret . "&",true));
        
        //二次
        $signature = $this->encode($sign);
        
        //生成传送地址
        $url = ($security ? 'https' : 'http')."://{$domain}/";
        
        //抛出异常进行post提交
        try {
            $content = $this->fetchContent($url, $method, "Signature={$signature}{$sortedQueryStringTmp}");
            //返回index return
            return json_decode($content, true);
        } catch( \Exception $e) {
            return false;
        }
    }
    
    //阿里云短信-加密方法
    private function encode($str)
    {
        $res = urlencode($str);
        $res = preg_replace("/\+/", "%20", $res);
        $res = preg_replace("/\*/", "%2A", $res);
        $res = preg_replace("/%7E/", "~", $res);
        return $res;
    }
    
    //阿里云短信-curl发送并返回结果参数
    private function fetchContent($url, $method, $body) {
        $ch = curl_init();
        
        if($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        } else {
            $url .= '?'.$body;
        }
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "x-sdk-client" => "php/2.0.0"
        ));
        
        if(substr($url, 0,5) == 'https') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        
        $rtn = curl_exec($ch);
        
        if($rtn === false) {
            // 大多由设置等原因引起，一般无法保障后续逻辑正常执行，
            // 所以这里触发的是E_USER_ERROR，会终止脚本执行，无法被try...catch捕获，需要用户排查环境、网络等故障
            trigger_error("[CURL_" . curl_errno($ch) . "]: " . curl_error($ch), E_USER_ERROR);
        }
        curl_close($ch);
        
        return $rtn;
    }

    //上传函数
    public static function to_upload_files($file,$mime,$maxsize,$path){
        //判断文件上传错误
        switch($file['error']){
            case 1:
                return 1001;//'文件超过php.ini限制';
                exit;
            case 2:
                return 1002;//'文件超过html限制';
                exit;
            case 3:
                return 1003;//'文件上传不完整';
                exit;
            case 4:
                return 1004;//'没有选择文件';
                exit;
            case 6:
                return 1006;//'服务器内部错误';
                exit;
            case 7:
                return 1007;//'服务器内部错误';
                exit;
        }

        if($file['size']>$maxsize){
            return 1008;                          //文件太大;
            exit;
        }

        //判断用户上传的文件类型是否合法
        if(!in_array($file['type'],$mime)){
            return 1009;                        //文件类型不合法;
            exit;
        }

        $tmp = $file['tmp_name'];

        $fileName = Self::getRandName();

        //获取文件的扩展名
        $ext = pathinfo($file['name'],PATHINFO_EXTENSION);
        //拼接文件名
        $basename = $fileName.'.' . $ext;
        //拼接路径
        $dest = $path. $basename;

        //将临时文件夹中的文件，移动到目标位置
        if(move_uploaded_file($tmp,$dest)){
            return $basename;
        }else{
            return 1010;
        }

    }

    //随机文件的格式
    public static function getRandName(){
        $string = date('YmdHis');
        for($i=0;$i<6;$i++){
            switch(mt_rand(0,2)){
                case 0:
                    $string .= chr(mt_rand(97,122));   //小a
                    break;
                case 1:
                    $string .= chr(mt_rand(65,90));   //大A
                    break;
                case 2:
                    $string .= mt_rand(0,9);          //获取随机数
                    break;
            }
        }
        return $string;
    }


}