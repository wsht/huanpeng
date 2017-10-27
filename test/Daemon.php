<?php
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


class Daemon
{
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

    public function singnalHandler($signo)
    {
        switch($signo)
        {
            case SIGTERM:
            case SIGINT:
            case SIGQUIT:
                $child = array_keys($this->_child);
                if($child){
                    foreach($child as $_child_pid){
                        posix_kill($_child_pid,SIGTERM);
                    }
                }
                $this->_terminate = true;
                break;
            
            // 子进程退出
            case SIGCHLD:
                while(($pid = pcntl_waitpid(-1, $status, WNOHANG)) > 0){
                    $this->workersCount--;
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
            if($this->workerCount < $this->startCount)
            {
                $pid = pcntl_fork();
                if($pid > 0)
                {
                    $this->workerCount ++;
                    $this->child[$pid] = date("Y-m-d H:i:s");
                    $this->log("child pid : $pid starting");
                    call_user_func_array($this->callback, $this->callParams);
                }
            }
            pcntl_signal_dispatch();//尝试设置不同位置
            sleep(1);
        }
    }
}