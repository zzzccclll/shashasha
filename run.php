<?php 
//秒杀的时候调用
require __DIR__ . '/ms.php';

$ms = new miaosha(['redis'=>['host'=>'localhost', 'port'=>'6381'], 'flag'=>'BMXCHD']);

$acId = 1;

//var_dump($argv[1]);die;
$tel = isset($argv[1]) ? $argv[1] : '13248308846';
$result = $ms->run($acId, $tel);

var_dump($result);
//header("content-type:text/html;charset=utf-8");