<?php
require_once "lib/WxPay.Api.php";
//require_once "WxPay.JsApiPay.php";
require_once "WxPay.Config.php";
require_once 'log.php';


//跳转到微信支付 php判断微信浏览器和普通浏览器
//if($_POST['zf'] == '1'){
//
//    $ua = $_SERVER['HTTP_USER_AGENT'];
//    //判断是否微信外部浏览器的时候
//    if (strpos($ua, 'MicroMessenger') == false && strpos($ua, 'Windows Phone') == false) {
//
//        ecs_header("Location: /wxpay/example/h5.php?order_sn=$order[order_sn]");
//        exit;
//
//    } else {
//        //微信浏览器
//        ecs_header("Location: /wxpay/example/jsapi.php?order_sn=$order[order_sn]");
//        exit;
//    }
//
//}elseif($_POST['zf'] == '2'){
//    //跳转到支付宝
//    ecs_header("Location: /alipay_wap/wappay/pay.php?order_sn=$order[order_sn]");
//    exit;
//}


//得到配置信息
$config = new WxPayConfig();

$aa = 'http://'.$_SERVER['HTTP_HOST'];
//var_dump($aa);exit;

//订单号
$order_sn = $_GET['order_sn'];

date_default_timezone_set("PRC");
$conn = new mysqli('116.255.187.144', 'kexi_lingwangmal', 'nmY8sDbMrJ4Cfzws', 'kexi_lingwangmal');
if ($conn -> connect_errno) {
    printf("Connect failed: %s\n", $conn->connect_error);
    exit();
}
//查询订单
$sql = "select * from  store_order where order_no = '$order_sn'";

$query = $conn->query($sql);
if($query){
    $select = mysqli_fetch_assoc($query);

    if($select){
        $order  = $select;
        //商品价钱
        $fee = $order['price_total'] * 100;
//        $fee = 1;
        $order_info = $order['order_info'];
    }else{

    }
}


//微信h5支付
$param['appid'] = $config->GetAppId();//微信支付的appid
$param['body'] = $order_info;//微信支付的商品描述
$param['mch_id'] = $config->GetMerchantId();//微信支付的mch_id
$param['nonce_str'] = $order_sn;//随机字符串
$param['notify_url'] = $aa."/public/tools/wxpay/returnwxpay.php";//回调地址 这个地址需要微信支付后台配置白名单 上限貌似是五个
$param['out_trade_no'] = $order_sn;//商户订单号
$param['spbill_create_ip'] = $_SERVER['REMOTE_ADDR'];//终端ip
$param['total_fee'] = $fee;//价格 单位分
$param['trade_type'] = "MWEB";//交易类型 h5支付的交易类型必须为MWEB
$param['scene_info'] = '{"h5_info": {"type":"Wap","wap_url": '.$aa.'"/orders","wap_name": "购买"}}';//场景信息

$key = $config->GetKey();

//按照微信规则生成签名
$sign = makeWXQM($param,$key);//key设置路径：微信商户平台(pay.weixin.qq.com)-->账户设置-->API安全-->密钥设置

//拼接xml数据
$send = "<xml>
            <appid>{$param['appid']}</appid>
            <attach>{$param['attach']}</attach>
            <body>{$param['body']}</body>
            <mch_id>{$param['mch_id']}</mch_id>
            <nonce_str>{$param['nonce_str']}</nonce_str>
            <notify_url>{$param['notify_url']}</notify_url>
            <out_trade_no>{$param['out_trade_no']}</out_trade_no>
            <spbill_create_ip>{$param['spbill_create_ip']}</spbill_create_ip>
            <total_fee>{$param['total_fee']}</total_fee>
            <trade_type>{$param['trade_type']}</trade_type>
            <scene_info>{$param['scene_info']}</scene_info>
            <sign>{$sign}</sign>
        </xml>";

//向微信发送请求
$result=postXmlCurl($send,"https://api.mch.weixin.qq.com/pay/unifiedorder");
//var_dump($result);die();
//将xml字符串变为数组
$res = xmlToArray($result);
 var_dump($res);exit;

$url = $aa;
$url = urlencode($url);
//拼接支付后跳转的url
$res['mweb_url'] = $res['mweb_url'].'&redirect_url='.$url;

/*********过程中用到的两个函数***********/
//微信支付签名生成函数
function makeWXQM($arr,$key){

    ksort($arr);
    $str='';
    foreach($arr as $k=>$v){
        $str.=$k.'='.$v.'&';
    }

    $str.='key='.$key;

    return strtoupper(md5($str));

}

//给微信发送请求函数
function postXmlCurl($xml, $url, $second = 30)
{
    $ch = curl_init();
    //设置超时
    curl_setopt($ch, CURLOPT_TIMEOUT, $second);

    curl_setopt($ch,CURLOPT_URL, $url);
    curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
    curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);//严格校验
    //设置header
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    //要求结果为字符串且输出到屏幕上
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    //post提交方式
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
    //运行curl
    $data = curl_exec($ch);
    //返回结果
    if($data){
        curl_close($ch);
        return $data;
    } else {
        $error = curl_errno($ch);
        curl_close($ch);
        die("curl出错，错误码:$error");
    }
}


/**
 * 获取微信支付中间页deepLink参数
 * @param string $url 微信返回的mweb_url
 * @param string $ip 用户端IP
 */
function getDeeplink(string $url, string $ip)
{
    $headers = array("X-FORWARDED-FOR:$ip", "CLIENT-IP:$ip");
    ob_start();
    $ch = curl_init();
    curl_setopt ($ch, CURLOPT_URL, $url);
    curl_setopt ($ch, CURLOPT_HTTPHEADER , $headers );
    curl_setopt ($ch, CURLOPT_REFERER, "pay.o9di.cn");
    curl_setopt( $ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Linux; Android 6.0.1; OPPO R11s Build/MMB29M; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/55.0.2883.91 Mobile Safari/537.36');
    curl_exec($ch);
    curl_close ($ch);
    $out = ob_get_contents();
    ob_clean();
    $a = preg_match('/weixin:\/\/wap.*/',$out, $str);
    if ($a) {
        return substr($str[0], 0, strlen($str[0])-1);
    } else {
        return '';
    }
}


//将xml字符串变为数组
function xmlToArray($xml)
{
    if (file_exists($xml)) {
        libxml_disable_entity_loader(false);
        $xml_string = simplexml_load_file($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
    } else {
        libxml_disable_entity_loader(true);
        $xml_string = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
    }
    $result = json_decode(json_encode($xml_string), true);
    return $result;
}


?>

<html>
<head>
    <meta http-equiv="content-type" content="text/html;charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <script src="jquery-1.9.1.min.js"></script>
    <title>支付</title>

    <script>

        window.location.href="<?php echo $res['mweb_url'];?>";
    </script>

</head>
<body>
<br/>

</body>
</html>

