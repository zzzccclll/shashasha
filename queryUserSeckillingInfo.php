<?php
//秒杀的时候调用
require __DIR__ . '/ms.php';

header('content-type:application:json;charset=utf8');
header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Methods:POST');
header('Access-Control-Allow-Headers:x-requested-with,content-type');

$ms = new miaosha(['redis'=>['host'=>'localhost', 'port'=>'6381'], 'flag'=>'BMXCHD']);

$productId = $_GET["productid"];
$userID = $_GET["userid"];
$result = $ms->queryUserSeckillingInfo($userID,$productId);
echo $result;die;
//header("content-type:text/html;charset=utf-8");