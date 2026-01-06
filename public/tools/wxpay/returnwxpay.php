<?php
/**
 *
 * example目录下为简单的支付样例，仅能用于搭建快速体验微信支付使用
 * 样例的作用仅限于指导如何使用sdk，在安全上面仅做了简单处理， 复制使用样例代码时请慎重
 * 请勿直接直接使用样例对外提供服务
 *
 **/

header("Content-Type: text/html;charset=utf-8");

require_once "lib/WxPay.Api.php";
require_once 'lib/WxPay.Notify.php';
require_once "WxPay.Config.php";
require_once 'log.php';

$input = new WxPayUnifiedOrder();


// $xml = $GLOBALS ['HTTP_RAW_POST_DATA'];  //PHP5.6
$xml = file_get_contents("php://input");    //PHP7.1
// if (empty($xml)) {
//     return false;
// }

// $fp = fopen('aabc.txt','w+');
// fwrite($fp,var_export($xml,true));
// fclose($fp);

//禁止引用外部xml实体 
libxml_disable_entity_loader(true);
$xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
$rs = json_decode(json_encode($xmlstring), true);

$total_fee = $rs['total_fee'] / 100;
// $open_id = $rs['openid'];
$order_id = $rs['out_trade_no'];
$transaction_id = $rs['transaction_id'];
$time_end = $rs['time_end'];


// $fp = fopen('aab.txt','w+');
// fwrite($fp,var_export($xml,true));
// fclose($fp);

// $rs = $input->FromXml($xml);


// $order_id = $rs['out_trade_no'];
// $total_fee = $rs['total_fee'];
// $transaction_id = $rs['transaction_id'];
// $time_end = date("Y-m-d H:i:s",strtotime($rs['time_end']));
// $time_end = date("Y-m-d H:i:s",time());


date_default_timezone_set("PRC");
$conn = new mysqli('localhost', 'www_qingyunaustr', 'XSkJ2rPKdZRehd8a', 'www_qingyunaustr');
if ($conn -> connect_errno) {
    printf("Connect failed: %s\n", $conn->connect_error);
    exit();
}


// $nam2 = "select * from store_order where order_no = '$order_id'";
// $query2 = $conn->query($nam2);
// $order = mysqli_fetch_array($query2);


$pay_time = time();
$nam = "update store_order set status = 3, pay_state = 1, pay_price = '$total_fee', pay_no = '$transaction_id', pay_at = '$time_end' where order_no = '$order_id'";

$query = $conn->query($nam);


// $nam3 = "update store_order set state = 4 where id = '$order[goods_id]'";

// $query3 = $conn->query($nam3);

//返回给微信说明成功
return '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';

?>