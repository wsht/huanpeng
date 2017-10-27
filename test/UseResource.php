

<?php

class SocketThread extends Thread
{
    public  $socket;

    public function init()
    {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    }

    public function run()
    {   
        while(true)
        {
            echo __CLASS__."is runing .\n";
            var_dump($this->socket);
            sleep(1);
        }
    }
}


class SocketTimerTask extends Thread
{
    public $socketThread;

    public function init($thread)
    {
        $this->socketThread = $thread;
        // $this->socketThread = new SocketThread();
        // $this->socketThread->init();
        // $this->socketThread->start();
    }

    public function run()
    {
        while(true)
        {
            sleep(1);
            var_dump("obj resource is \n");
            var_dump($this->socketThread->socket);

            // var_dump("obj static resource is \n");
            // var_dump(SocketThread::$socket);
        }
        
    }
}


$socket = new SocketThread();
$socket->init();

$socket->start();

$task = new SocketTimerTask();
$task->init($socket);
$task->start();
