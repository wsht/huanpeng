<?php
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
        // var_dump($this);
        return $result;
    }

    public function runCallBackFunction()
    {
        echo __FUNCTION__. "get callback \n";
        
        // print_r($this->child);
        $pid = posix_getpid();
        $callback = $this->callback; 
        $callbackParams = $this->callbackParams;

        if(!$callback || !is_array($callbackParams))
        {
            exit();
        }

        //将task  和$pid 绑定
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
        $i=0;
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
                    // var_dump($this->child);
                    
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
                        // print_r($e);
                        exit();
                    }
                }
                usleep(100000);            
                // sleep(1);  
            }
            else
            {
                if($i > 15)
                {
                    // $rand = rand(2,3);
                    // $childcount = count($this->child);
                    // $num = intval($childcount/$rand);
                    // $pids = array_keys($this->child);

                    // echo "exit num is $num\n";
                    // echo "exit list is ".json_encode($pids)."\h";
                    // for($j = 0; $j < $num; $j++)
                    // {
                    //     posix_kill($pids[$j], SIGTERM);
                    // }
                    // unset($pids);
                    // unset($rand);
                    // unset($childcount);
                    // unset($num);
                    $i=0;
                }
                $i++;            
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
