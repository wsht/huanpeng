<?php

require_once __DIR__."/chatHelper.php";
require_once __DIR__."/../lib/Daemon.php";


$existList = [
    'pcntl_fork',
    'pcntl_signal',
    'gc_enable'
];


foreach($existList as $func_name)
{
    $isExist = function_exists("$func_name");
    // var_dump("$func_name is exist?=>". json_encode());
    if(!$isExist)
    {
        echo $func_name."is not exist please install\n";
    }
}


// class CallbackObj
// {
//     private $time;

//     public function addTimer($time)
//     {
//         while(true){
//             if (!$this->time){
//                 $this->time = $time;
//             }

//             file_put_contents("log/" . getmypid(), "--->{$this->time}\n", FILE_APPEND);

//             if ($this->time >= 10000){
//                 exit();
//             }

//             $this->time++;
//             sleep(1);
//         }
 
//     }
// }


class UserLogin
{
    // private $uid;
    // private $enc;

    public function __construct()
    {
        // if($this->init())
        // {
          
        // }else
        // {
        //     exit("login init failed..");
        // }
    }

    public static function getInstance()
    {
        // return new 
    }

    // public function getUserInfoEntity():array
    // {
    //     var_dump($GLOBALS['uenc']);
    //     return $GLOBALS['uenc'];
    // }

    // public function setUsed($uid)
    // {
    //     $GLOBALS['uenc'][$uid]['used'] = 1;
    // }

    // public function init()
    // {
    //     if($result = $this->getEncpass())
    //     {
    //         // $this->uid = $result['uid'];
    //         // $this->enc = $result['encpass'];
    //         return true;
    //     }

    //     return false;
    // }

    // public function getEncpass()
    // {
    //     // $keys = array_keys($this->getUserInfoEntity());
        
    //     foreach($this->getUserInfoEntity() as $key=>$info)
    //     {
    //         if($info['used'] == 0)
    //         {
    //             $this->uid = $key;
    //             $this->enc = $info['encpass'];
    //             $this->setUsed($key);
    //             // return ['uid'=>$key, 'encpass'=> $info['encpass']];
    //             var_dump($GLOBALS['uenc'][$key]);
    //             return true;
    //         }
    //     }

    //     return false;
    //     // return [];
    // }

    public function login($uid,$enc,$roomid, $ip, $port)
    {
        // if($this->init())
        // {
            echo "\$chatHelper->login({$uid}, {$enc}, $roomid)\n";

            $chatHelper = new ChatHelper();
            $chatHelper->setIp($ip);
            $chatHelper->setPort($port);

            $chatHelper->login($uid, $enc, $roomid);
            // while(true)
            // {
            //     sleep(1);
            // }
            // sleep(2)
        // }
        // exit("login init failed..");
        // throw new Exception("login init failed..", 1);
    }
}

// declare(ticks=1);



$count = $argv[1] ? $argv[1] : 1;

if(!$GLOBALS['uenc'])
{
    $res = file_get_contents("http://hantong.huanpeng.com/uenc.php?limit=$count");
    $GLOBALS['uenc'] = json_decode($res, true);

    print_r($res);
    print_r("http get uenc \n\n\n");

    // print_r($GLOBALS['uenc']);
}

$userLogin  = new UserLogin();

// var_dump($userLogin->getEncpass());

$daemon = new Daemon();

foreach($GLOBALS['uenc'] as $uid=> $info)
{
    $callback = [UserLogin::class, 'login'];
    $params = [$uid, $info['encpass'], 3375,'122.70.146.49','8082'];

    $daemon->addTask($callback, $params);
}

echo "get user task\n";
// var_dump($daemon->task);
// var_dump($daemon->getTask());

// $daemon->runCallBackFunction();

// $callback = [UserLogin::class, "login"];
// $params = [3375,'122.70.146.49','8082'];

// $daemon->setCallback($callback, $params);

$daemon->run($count);
