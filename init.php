<?php 
//初始化活动的时候调用
require __DIR__ . '/ms.php';

$ms = new miaosha(['redis'=>['host'=>'localhost', 'port'=>'6379'], 'flag'=>'BMXCHD']);

$acId = 1;
//$ms->delActivity($acId);
$ms->newActivity($acId, [
    'name'          => '活动1',
    'title'         => '秒杀优惠券',
    'start_time'    => '1456308000',
    //'end_time'      => '1456311600',
    'end_time'      => '1561784784',
    'total'         => 1000,
    'stock'         => 100
]);
$ms->setStock($acId, 100);

//header("content-type:text/html;charset=utf-8");