<?php
$database = "ceshi"; //数据库名称

$host = "localhost";
$user = "root"; //数据库用户名
$pwd = "root"; //数据库密码
$replace ='en_';//替换后的前缀
$seach = 'pre_';//要替换的前缀
$db=mysqli_connect($host,$user,$pwd,$database) or die("连接数据库失败：".mysqli_error()); //连接数据库
$tables = mysqli_query($db,"SHOW TABLES FROM ".$database);

while($name = mysqli_fetch_array($tables)) {

//修改前缀
// $table = str_replace($seach,$replace,$name['0']);

//增加前缀
$table = $replace.$name['0'];

$return = mysqli_query($db,"rename table ".$name[0]." to ".$table);
// print("rename table ".$name[0]." to ".$table.";<br>");
}
var_dump("设置成功");die();
?>