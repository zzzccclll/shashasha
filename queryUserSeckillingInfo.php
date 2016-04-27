<?php
//秒杀的时候调用
require __DIR__ . '/ms.php';

$ms = new miaosha(['redis'=>['host'=>'localhost', 'port'=>'6381'], 'flag'=>'BMXCHD']);

//$acId = 1;
//
////var_dump($argv[1]);die;
//$acId = _GET['']
//$tel = isset($argv[1]) ? $argv[1] : '13248308897';
//$result = $ms->run($acId, $tel);
//var_dump($result);
//
//sleep(5);
//$result = $ms->queryUserSeckillingInfo($tel,$acId);
//var_dump(json_decode($result));die;
$productId = $_GET["productid"];
$userID = $_GET["userid"];
//$result = $ms->run(1, 13248308897);
//$result = $ms->run($productId, $userID);
$result = $ms->queryUserSeckillingInfo($userID,$productId);
var_dump($result);die;
//header("content-type:text/html;charset=utf-8");