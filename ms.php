<?php 
class miaosha
{
    private $redisHost = '192.168.1.227';
    private $redisPort = '6381';
    private $redisAuth = '';
    private $redisDB   = '0';

    private $hdsKey         = 'XCHD';             //所有活动缓存
    const HD_REWORD_KEY     = '%s_REWARD_%s';     //活动奖励池
    const HD_LUCKY_KEY      = '%s_LUCKY_%s';      //中奖名单
    const HD_PRODUCT_KEY    = '%s_PRODUCT_%s';    //商品库存队列

    const HD_QUEUE_KEY      = '%s_QUEUE_%s';      //活动任务队列
    const HD_STOCK_KEY      = '%s_STOCK_%s';      //活动库存

    private static $redis = null;
    public static $inst = null;

    public function __construct($config=[])
    {
        if(!empty($config['redis']))
        {
            if(!empty($config['redis']['host']))
            {
                $this->redisHost = $config['redis']['host'];
            }
            if(!empty($config['redis']['port']))
            {
                $this->redisPort = $config['redis']['port'];
            }
            if(!empty($config['redis']['password']))
            {
                $this->redisAuth = $config['redis']['password'];
            }
            if(!empty($config['redis']['db']))
            {
                $this->redisDB = $config['redis']['db'];
            }
        }

        if(!empty($config['flag']))
        {
            $this->hdsKey = $config['flag'];
        }
    }


    public static function instance() 
    {
        $clz = __CLASS__;
        if(self::$inst === null){
            self::$inst = new $clz();
        }
        return self::$inst;
    }

    public function getRedis()
    {
        if(null === self::$redis){
            //Redis::__construct;
            $redis = new Redis();
            //var_dump($redis);die;
            //$conn = $redis->pconnect($this->redisHost, $this->redisPort);
            $conn = $redis->pconnect('127.0.0.1', 6379);
            //var_dump($conn);die;
            if($conn){
                $redis->auth($this->redisAuth);
                $redis->select($this->redisDB);
                self::$redis = $redis;
            }else{
                throw new \Exception('Redis Lost');
            }
        }

        return self::$redis;
    }

    public function newActivity($activityId, $data)
    {
        $redis = $this->getRedis();
        $redis->hSet($this->hdsKey, $activityId, json_encode($data));
    }


    public function delActivity($activityId)
    {
        $redis = $this->getRedis();
        $redis->hDel($this->hdsKey, $activityId);

        $stockKey   = sprintf(self::HD_STOCK_KEY, $this->hdsKey, $activityId);
        $redis->del($stockKey);

        $rewardKey   = sprintf(self::HD_REWORD_KEY, $this->hdsKey, $activityId);
        $redis->del($rewardKey);

        $luckKey   = sprintf(self::HD_LUCKY_KEY, $this->hdsKey, $activityId);
        $redis->del($luckKey);

        $queueKey   = sprintf(self::HD_QUEUE_KEY, $this->hdsKey, $activityId);
        $redis->del($queueKey);
    }


    public function unsetAll()
    {
        $redis = $this->getRedis();
        $activities = $redis->hGetAll($this->hdsKey);
        if($activities)
        {
            foreach ($activities as $key => $act) {
                $this->delActivity($key);
            }

            $redis->del($this->hdsKey);
        }
    }


    public function getActivity($activityId)
    {
        $redis = $this->getRedis();
        $data  = $redis->hGet($this->hdsKey, $activityId);
//var_dump(json_decode($data));die;
        return $data ? json_decode($data, true) : null;
    }


    public function setStock($activityId, $stock)
    {
        $redis      = $this->getRedis();
        $stockKey   = sprintf(self::HD_STOCK_KEY, $this->hdsKey, $activityId);

        $redis->set($stockKey, $stock);
    }


    public function getStock($activityId)
    {
        $redis      = $this->getRedis();
        $stockKey   = sprintf(self::HD_STOCK_KEY, $this->hdsKey, $activityId);

        return $redis->get($stockKey);
    }


    public function incrStock($activityId, $val=1)
    {
        $redis      = $this->getRedis();
        $stockKey   = sprintf(self::HD_STOCK_KEY, $this->hdsKey, $activityId);

        $redis->incrBy($stockKey, $val);
    }


    public function decrStock($activityId, $val=1)
    {
        $redis      = $this->getRedis();
        $stockKey   = sprintf(self::HD_STOCK_KEY, $this->hdsKey, $activityId);

        $redis->decrBy($stockKey, $val);
    }


    public function setRewards($activityId, $rewards)
    {
        $redis      = $this->getRedis();
        $rewardsKey = sprintf(self::HD_REWORD_KEY, $this->hdsKey, $activityId);
        foreach ($rewards as $k => $reward) {
            $redis->hSet($rewardsKey, "rwd_".$k, json_encode($reward));
        }
    }



    public function getRewards($activityId)
    {
        $redis      = $this->getRedis();
        $rewardsKey = sprintf(self::HD_REWORD_KEY, $this->hdsKey, $activityId);
        
        return $redis->hGetAll($rewardsKey);
    }


    public function getReward($activityId, $key)
    {
        $redis      = $this->getRedis();
        $rewardsKey = sprintf(self::HD_REWORD_KEY, $this->hdsKey, $activityId);
        $data       = $redis->hGet($rewardsKey, "rwd_".$key);

        return $data ? json_decode($data, true) : null;
    }


    public function pushQueue($activityId, $data)
    {
        $redis      = $this->getRedis();
        $queueKey   = sprintf(self::HD_QUEUE_KEY, $this->hdsKey, $activityId);

        $redis->lPush($queueKey, json_encode($data));
    }


    public function popQueue($activityId)
    {
        $redis      = $this->getRedis();
        $queueKey   = sprintf(self::HD_QUEUE_KEY, $this->hdsKey, $activityId);

        $data       = $redis->rPop($queueKey);

        return $data ? json_decode($data, true) : null;
    }


//    public function run($activityId, $identification)
//    {
//
//        $redis      = $this->getRedis();
//        //$redis->del($this->hdsKey);die;
//        $stockKey   = sprintf(self::HD_STOCK_KEY, $this->hdsKey, $activityId);
//
//        $now        = time();
//        $stock      = $redis->get($stockKey);
//       // var_dump($stock);die;
//        $acitivty   = $this->getActivity($activityId);
//        //var_dump($acitivty);die;
//        if($acitivty)
//        {
//           // $date = date('Y-m-d H:i:s',$acitivty['start_time']);die;
//            if(!empty($acitivty['start_time']) && $now < $acitivty['start_time'])
//            {
//                return -4;//活动未开始
//            }
////var_dump($now,$acitivty['end_time']);die;
//            if(!empty($acitivty['end_time']) && $now > $acitivty['end_time'])
//            {
//
//                return -5;//活动已结束
//            }
////var_dump($stock);die;
//            if($stock > 0)
//            {
//                $luckKey= sprintf(self::HD_LUCKY_KEY, $this->hdsKey, $activityId);
////                if($redis->hExists($luckKey, $identification))
////                {
////                    //echo 222;;die;
////                    return -1;//已领过
////                }
//
//                //redis事务
//                $redis->watch($stockKey);
//                $redis->multi();
//               // var_dump($redis->get($stockKey));die;
//                $redis->decr($stockKey);
//                //var_dump($redis->get($stockKey));die;
//                $result = $redis->exec();
//               // var_dump($result);die;
//                if($result)
//                {
//                    $stock      = $result[0];
//                    //var_dump($stock);die;
//                    $idx        = $stock;//$redis->hLen($luckKey);
//                   // $rewardData = ['id' => $identification, 'time' => $now];
//                    $rewardData = ['userid' => $identification, 'productid' => $activityId];
//                    //$reward     = $this->getReward($activityId, $idx);
////                    if($reward)
////                    {
////                        $rewardData['reward'] = $reward;
////                        var_dump($rewardData);die;
//
//                        //保存中奖信息
//                        $redis->hSet($luckKey, $identification, $rewardData);
//
//                        $this->pushQueue($activityId, $rewardData);
//                        //var_dump($stock);die;
//                        return ['stock'=>$stock, 'data'=>$rewardData];
////                    }
//
//                    return -6;//分配奖励失败, 理论上不应该发生
//                }
//                else
//                {
//                    return -2;//领取失败
//                }
//            }
//            else
//            {
//                return -3;//已领完
//            }
//        }
//
//        //活动不存在
//        return 0;
//    }


    public function seckilling($productid ,$userid)
    {

        $redis      = $this->getRedis();
        $stockKey   = sprintf(self::HD_STOCK_KEY, $this->hdsKey, $productid);
        $now        = time();
        $stock      = $redis->get($stockKey);
        $acitivty   = $this->getActivity($productid);
        if($acitivty)
        {
            // $date = date('Y-m-d H:i:s',$acitivty['start_time']);die;
            if(!empty($acitivty['start_time']) && $now < $acitivty['start_time'])
            {
                return -4;//活动未开始
            }
            if(!empty($acitivty['end_time']) && $now > $acitivty['end_time'])
            {

                return -5;//活动已结束
            }
            if($stock > 0)
            {
                $userKey= sprintf(self::HD_LUCKY_KEY, $this->hdsKey, $productid);
                $productKey= sprintf(self::HD_PRODUCT_KEY, $this->hdsKey, $productid);
                if($redis->hExists($userKey, $userid))
                {
                    return -1;//已领过
                }

                //redis事务
                $redis->watch($stockKey);
                $redis->multi();
                // var_dump($redis->get($stockKey));die;
                $redis->decr($stockKey);
                //var_dump($redis->get($stockKey));die;
                $result = $redis->exec();
                // var_dump($result);die;
                if($result)
                {
                    $aa = $acitivty['stock'];

                    //$rewardData = ['userid' => $userid, 'productid' => $productid];
                    $rewardData =  $aa - $stock +1;
                    $redis->hSet($userKey, $userid, $rewardData);
                    $data = array(
                        'userid' => $userid,
                        'goodsid' => $rewardData,
                    );

                   // $redis->hSet($productKey, $userid, json_encode($data));
                    $redis->hSet($productKey, $userid,$rewardData );

                    $this->pushQueue($productid, $rewardData);
                    return 0;
                }
                else
                {
                    return -2;//领取失败
                }
            }
            else
            {
                return -3;//已领完
            }
        }

        //活动不存在
        return 0;
    }


    /**
     * @param $userid
     * @param $productid
     */
    public function  queryUserSeckillingInfo($userid,$productid)
    {
        $redis      = $this->getRedis();
        $userKey= sprintf(self::HD_LUCKY_KEY, $this->hdsKey, $productid);
        $user = $redis->hGet($userKey,$userid);

        $acitivty   = $this->getActivity($productid);
        $now = time();
        $startTime = $acitivty['start_time'];
       // $endTime = $acitivty['end_time'];
        $data = array(
            'errno' => 0,
            'status' => 0,
            'goodsid'=> '',
        );
        if($now < $startTime)
        {
            return json_encode($data);
        }
        if(empty($user))
        {
            $data['status'] = 2;
            return json_encode($data);
        }else{
            $data['status'] = 1;
            $data['goodsid'] = $user;
            return json_encode($data);
        }
    }

    public function  queryProductSeckillingInfo($productid)
    {
        $redis  = $this->getRedis();
        $userKey= sprintf(self::HD_LUCKY_KEY, $this->hdsKey, $productid);
        $user = $redis->hGetAll($userKey);
        //var_dump($user);die;
        $arr = array();
        foreach($user as $key => $value)
        {
            $temp['userid'] = $key;
            $temp['goodsid'] = $value;
            $arr[] = $temp;
        }
        $result = array();
        $result['errno'] =0;
        $result['list'] = $arr;

        return json_encode($result);

    }


}

//header("content-type:text/html;charset=utf-8");