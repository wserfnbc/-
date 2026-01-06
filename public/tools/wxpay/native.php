<?php
/**
*
* example目录下为简单的支付样例，仅能用于搭建快速体验微信支付使用
* 样例的作用仅限于指导如何使用sdk，在安全上面仅做了简单处理， 复制使用样例代码时请慎重
* 请勿直接直接使用样例对外提供服务
* 
**/

require_once "lib/WxPay.Api.php";
require_once "WxPay.NativePay.php";
require_once 'log.php';

//初始化日志
$logHandler= new CLogFileHandler("public/tools/wxpay/logs/".date('Y-m-d').'.log');
$log = Log::Init($logHandler, 15);

//模式一
//不再提供模式一支付方式
/**

 * 流程：
 * 1、组装包含支付信息的url，生成二维码
 * 2、用户扫描二维码，进行支付
 * 3、确定支付之后，微信服务器会回调预先配置的回调地址，在【微信开放平台-微信支付-支付配置】中进行配置
 * 4、在接到回调通知之后，用户进行统一下单支付，并返回支付信息以完成支付（见：native_notify.php）
 * 5、支付完成之后，微信服务器会通知支付成功
 * 6、在支付成功通知中需要查单确认是否真正支付成功（见：notify.php）
 */

$notify = new NativePay();
//$url1 = $notify->GetPrePayUrl("123456789");
$price = 1;
//模式二
/**
 * 流程：
 * 1、调用统一下单，取得code_url，生成二维码
 * 2、用户扫描二维码，进行支付
 * 3、支付完成之后，微信服务器会通知支付成功
 * 4、在支付成功通知中需要查单确认是否真正支付成功（见：notify.php）
 */
$input = new WxPayUnifiedOrder();
$input->SetBody($goods_names);
$input->SetAttach("test2");
$input->SetOut_trade_no($order_sn);
$input->SetTotal_fee($price);
$input->SetTime_start(date("YmdHis"));
//$input->SetTime_expire(date("YmdHis", time() + 600));
$input->SetGoods_tag("test3");
$input->SetNotify_url("http://www.xiaojushiyan.com/index/flow/wxpay_return");
$input->SetTrade_type("NATIVE");
$input->SetProduct_id("123456789");

$result = $notify->GetPayUrl($input);
if($result['result_code'] == 'SUCCESS' && $result['return_code'] == 'SUCCESS' && $result['return_msg'] == 'OK'){
    $url2 = $result["code_url"];
    $qrcode = "<img src='/public/tools/wxpay/qrcode.php?data=".urlencode($url2)."'>";
}
else{
    $qrcode = $result['err_code_des'];
}

return $qrcode;
?>