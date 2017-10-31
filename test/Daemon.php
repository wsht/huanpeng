<?php

require_once __DIR__."/chatHelper.php";

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
class Daemon
{
    private $child = [];
    private $workerCount;

    private $callback;
    private $callbackParams;

    public $task=[];

    public function __construct()
    {
        $this->setSignalHandler();
        if (function_exists('gc_enable')) {
            gc_enable();
        }
    }

    // public function setCallback($callback, $params)
    // {   
    //     $this->callback = $callback;
    //     $this->callbackParams = $params;
    // }

    public function addTask($func, $params)
    {
        array_push($this->task,['func'=>$func, 'params'=>$params]);
    }

    public function getTask()
    {
        $result = array_shift($this->task);
        $this->callback = $result['func'];
        $this->callbackParams = $result['params'];

        return $result;
    }

    public function runCallBackFunction()
    {
        echo __FUNCTION__. "get callback \n";
        
        print_r($this->child);
        $pid = posix_getpid();
        $callback = $this->callback; 
        $callbackParams = $this->callbackParams;

        if(!$callback || !$callbackParams)
        {
            exit();
        }

        $this->child[$pid]['task']['func'] = $callback;
        $this->child[$pid]['task']['params'] = $callbackParams;

        if(is_array($callback))
        {
            if(is_object($callback[0]))
            {
                return call_user_func_array($callback, $callbackParams);
            }
            else
            {
                if(method_exists($callback[0], $callback[1]))
                {
                    $callback[0] = new $callback[0]();
                }
                return call_user_func_array($callback, $callbackParams);                
            }
        }
        else
        {
            return call_user_func_array($callback, $callbackParams);            
        }
    }

    private function setSignalHandler()
    {
        pcntl_signal(SIGTERM, array(__CLASS__, 'signalHandler'), false);
        pcntl_signal(SIGINT, array(__CLASS__, 'signalHandler'), false);
        pcntl_signal(SIGQUIT, array(__CLASS__, 'signalHandler'), false);
        pcntl_signal(SIGCHLD, array(__CLASS__, 'signalHandler'), false);
        pcntl_signal(SIGUSR1, array(__CLASS__, 'signalHandler'), false);
    }

    private function restoreSignalhandler()
    {
        pcntl_signal(SIGTERM, SIG_DFL);
        pcntl_signal(SIGINT, SIG_DFL);
        pcntl_signal(SIGQUIT, SIG_DFL);
        pcntl_signal(SIGCHLD, SIG_DFL);
        pcntl_signal(SIGUSR1, SIG_DFL);
    }

    public function signalHandler($signo)
    {
        switch($signo)
        {
            case SIGTERM:
            case SIGINT:
            case SIGQUIT:
                $child = array_keys($this->child);
                if($child){
                    foreach($child as $_child_pid){
                        posix_kill($_child_pid, SIGTERM);
                        $this->log("kill child id posix_kill($_child_pid, SIGTERM);");
                    }
                }
                // $this->terminate = true;
                break;
            
            // 子进程退出
            case SIGCHLD:
                while(($pid = pcntl_waitpid(-1, $status, WNOHANG)) > 0){
                    $this->workerCount--;
                    $func = $this->child[$pid]['task']['func'];
                    $params = $this->child[$pid]['task']['params'];
                    if($func && $params)
                        $this->addTask($func, $params);
                    unset($this->child[$pid]);
                    $this->log("The parent process receives the child:{$pid} process exit signal");
                }
                break;
            
            case SIGUSR1:
                break; 
        }
    }

    public function run($count)
    {
        $this->startCount = $count;
        while(true)
        {
            $this->log("current start count is {$this->startCount}");
            $this->log("current worker count is {$this->workerCount}");

            if($this->workerCount < $this->startCount)
            {
                $pid = pcntl_fork();
                if($pid > 0)
                {
                    $this->workerCount ++;
                    $this->child[$pid]['stime'] = date("Y-m-d H:i:s");
                    $this->log("child pid : $pid starting");
                    $this->log("add the worker count is ".$this->workerCount);
                    $task = $this->getTask();
                    $this->child[$pid]['task'] = $task;
                    var_dump($this->child);
                    
                    // continue;
                    // call_user_func_array($this->callback, $this->callbackParams);
                }elseif($pid == 0)
                {
                    $this->log("child run get mypid".getmypid());
                    try{
                        // call_user_func_array($this->callback, $this->callbackParams);
                        $this->runCallBackFunction();                    
                    }catch(Exception $e)
                    {
                        // exit($e->getCode.":".$e->getMessage."\n");
                        print_r($e);
                        exit();
                    }
                }
                usleep(100000);            
                // sleep(1);  
            }
            else
            {
                sleep(1);
            }
            pcntl_signal_dispatch();//尝试设置不同位置
        }
    }

    public function log($msg)
    {
        file_put_contents("log/log".date("Y-m"), $msg."\n", FILE_APPEND);
    }
}


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
    $callback = [new UserLogin(), 'login'];
    $params = [$uid, $info['encpass'], 3375,'122.70.146.49','8082'];

    $daemon->addTask($callback, $params);
}

echo "get user task\n";
var_dump($daemon->task);
// var_dump($daemon->getTask());

// $daemon->runCallBackFunction();

// $callback = [UserLogin::class, "login"];
// $params = [3375,'122.70.146.49','8082'];

// $daemon->setCallback($callback, $params);

$daemon->run($count);
