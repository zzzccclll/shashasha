<?php
//秒杀的时候调用
require __DIR__ . '/ms.php';

$ms = new miaosha(['redis'=>['host'=>'localhost', 'port'=>'6381'], 'flag'=>'BMXCHD']);

$productId = $_GET["productid"];
//$result = $ms->run(1, 13248308897);

$result = $ms->queryProductSeckillingInfo(1);
//$result = $ms->queryProductSeckillingInfo($productId);

//$result = $ms->queryUserSeckillingInfo($userID,$productId);
var_dump($result);die;
//header("content-type:text/html;charset=utf-8");