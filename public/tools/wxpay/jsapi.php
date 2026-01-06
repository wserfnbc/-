<?php
/**
 *
 * example目录下为简单的支付样例，仅能用于搭建快速体验微信支付使用
 * 样例的作用仅限于指导如何使用sdk，在安全上面仅做了简单处理， 复制使用样例代码时请慎重
 * 请勿直接直接使用样例对外提供服务
 *
 **/
require_once "lib/WxPay.Api.php";
require_once "WxPay.JsApiPay.php";
require_once 'log.php';

//初始化日志
$logHandler= new CLogFileHandler("logs/".date('Y-m-d').'.log');
$log = Log::Init($logHandler, 15);



/**
 * 微信按钮跳转授权
 * 第一步把跳转路径传到获取code的接口里accredit也就是这个方法
 */

//需要在 Wxpay.api.php 中postXmlCurl方法里添加 curl报错60的关闭证书验证 为jsapi最后发送时候curl没有发送成功
// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);



//自己加的获取code
function weChatAccredit($config)
{

    $appid = $config->GetAppId();
    $appsecret = $config->GetAppSecret();

    $url = 'http://www.xiaojushiyan.com/public/tools/wxpay/jsapi.php';
    $url = urlencode($url);
    $code = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$appid}&redirect_uri={$url}&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect";
    return $code;
}

//打印输出数组信息
function printf_info($data)
{
    foreach($data as $key=>$value){
        echo "<font color='#00ff55;'>$key</font> :  ".htmlspecialchars($value, ENT_QUOTES)." <br/>";
    }
}

//①、获取用户openid
try{

    if($_GET['order_sn'] == ''){
        echo "<script> alert('订单号不能为空');history.back();</script>";exit;
    }
//订单号
    $order_sn = $_GET['order_sn'];

    date_default_timezone_set("PRC");
    $conn = new mysqli('116.255.187.144', 'kexi_lingwangmal', 'nmY8sDbMrJ4Cfzws', 'kexi_lingwangmal');
    if ($conn -> connect_errno) {
        printf("Connect failed: %s\n", $conn->connect_error);
        exit();
    }

    session_start();


//查询订单
    $sql = "select * from  store_order where order_no = '$order_sn'";

    $query = $conn->query($sql);

    if($query){
        $select = mysqli_fetch_assoc($query);
        if($select){

            $order  = $select;
            //商品价钱
            $fee = $order['price_total'] * 100;

            $order_info = $order['order_info'];
        }
    }

    $tools = new JsApiPay();
    //截取订单号
    $result = substr($order_sn,0,strrpos($order_sn,"order_sn"));
// var_dump($order_sn);die();
    //②、统一下单
    $input = new WxPayUnifiedOrder();
    $input->SetBody($order_info);
    $input->SetAttach($order_info);
    $input->SetOut_trade_no($result);
    $input->SetTotal_fee($fee);
    $input->SetTime_start(date("YmdHis"));
//    $input->SetTime_expire(date("YmdHis", time() + 600));
    $input->SetGoods_tag("购买");
    $input->SetNotify_url("http://www.xiaojushiyan.com/index/flow/wxpay_return");
    $input->SetTrade_type("JSAPI");

    //得到配置信息
    $config = new WxPayConfig();
    //得到code
    $code = weChatAccredit($config);
    
    //获取openid
    $openId = $tools->GetOpenid();

    $input->SetOpenid($openId);

    $order = WxPayApi::unifiedOrder($config, $input);
    // var_dump($order);die();
    echo '<div style="margin:0;padding:5vh;background-color: #999999"><font color="#fff"><b>微信支付统一下单支付信息</b></font></div>';

    $jsApiParameters = $tools->GetJsApiParameters($order);
    //获取共享收货地址js函数参数
    $editAddress = $tools->GetEditAddressParameters();
} catch(Exception $e) {
    Log::ERROR(json_encode($e));
}
//③、在支持成功回调通知中处理成功之后的事宜，见 notify.php
/**
 * 注意：
 * 1、当你的回调地址不可访问的时候，回调通知会失败，可以通过查询订单来确认支付是否成功
 * 2、jsapi支付时需要填入用户openid，WxPay.JsApiPay.php中有获取openid流程 （文档可以参考微信公众平台“网页授权接口”，
 * 参考http://mp.weixin.qq.com/wiki/17/c0f37d5704f0b64713d5d2c37b468d75.html）
 */
?>

<html>
<head>
    <meta http-equiv="content-type" content="text/html;charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <script src="jquery-1.9.1.min.js"></script>
    <title>微信支付</title>
    <script type="text/javascript">
        history.pushState(null,null,"/public/tools/wxpay_wap/");//解决设置了支付目录还提示url未注册


        //调用微信JS api 支付
        function jsApiCall()
        {
            WeixinJSBridge.invoke(
                'getBrandWCPayRequest',
                <?php echo $jsApiParameters; ?>,
                function(res){
                    // WeixinJSBridge.log(res.err_msg);
                    //支付成功后跳转
                    window.location.href="http://www.xiaojushiyan.com/index/member/orders";
                    // alert(res.err_code+res.err_desc+res.err_msg);
                }
            );
        }

        function callpay()
        {
            if (typeof WeixinJSBridge == "undefined"){
                if( document.addEventListener ){
                    document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
                }else if (document.attachEvent){
                    document.attachEvent('WeixinJSBridgeReady', jsApiCall);
                    document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
                }
            }else{
                jsApiCall();
            }
        }
    </script>
    <script type="text/javascript">
        //获取共享地址
        function editAddress()
        {
            WeixinJSBridge.invoke(
                'editAddress',
                <?php echo $editAddress; ?>,
                function(res){
                    var value1 = res.proviceFirstStageName;
                    var value2 = res.addressCitySecondStageName;
                    var value3 = res.addressCountiesThirdStageName;
                    var value4 = res.addressDetailInfo;
                    var tel = res.telNumber;

                    // alert(value1 + value2 + value3 + value4 + ":" + tel);
                }
            );
        }

        window.onload = function(){
            if (typeof WeixinJSBridge == "undefined"){
                if( document.addEventListener ){
                    document.addEventListener('WeixinJSBridgeReady', editAddress, false);
                }else if (document.attachEvent){
                    document.attachEvent('WeixinJSBridgeReady', editAddress);
                    document.attachEvent('onWeixinJSBridgeReady', editAddress);
                }
            }else{
                editAddress();
            }
        };

    </script>
    <style type="text/css">
	    font{text-align: center;display: block;}
    </style>
</head>
<body style="padding:0;margin:0">
<div style="padding:15vh 0;">
	<font>订单号: <?php echo $result; ?></font><br>
	<font>本订单支付金额</font><br>
	<font><b><span style="color:#f00;font-size:30px">￥<?php echo $fee / 100; ?></span></b></font>
</div>

<div align="center">
    <button style="width:150px; height:44px; border-radius: 10px;background-color:#b4d3a5; border:0px #FE6714 solid; cursor: pointer;  color:white;  font-size:16px;line-height: 44px;" type="button" onclick="callpay()" >立即支付</button>
</div>
</body>
</html>