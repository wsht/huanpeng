<?php

class ChatCommand
{
    private $command;
    private $socket;
    private $content;
    private $cTaskId;

    public function getCommand(){
        return $this->command;
    }

    public function getSocket(){
        return $this->socket;
    }

    public function getContent(){
        return $this->content;
    }

    public function getCTaskId()
    {
        return $this->cTaskId;
    }

    public function __construct($command, $socket, $content)
    {
        $this->command = $command;
        $this->socket = $socket;
        $this->content = $content;
    }
}



class ChatResource
{
    private $pingTiemrThread;
    private $readPackThread;
    private $cTaskId;
    private $ip;
    private $port;
    private $uid;
    private $encpass;
    private $roomid;
    private $status;
    private $socket;

    public function setCTaskId($id)
    {
        $this->cTaskId = $id;
    }
}

class ChatTask
{
    private $chatMap = [];
    private $cTaskMaxId = 0;

    public function newSource(ChatResource $chatRes)
    {
        $tid = ++$this->cTaskMaxId;
        $chatRes->setCTaskId($tid);        
        $this->chatMap[$tid] = $chatRes;
        
        return $tid;
    }

    public function task()
    {
        $command = yield;
        if($command && ($command instanceof ChatCommand) )
        {
            $ctaskId = $command->getCTaskId;
            if( !($this->chatMap[$command->getTaskId] instanceof ChatResource))
            {
                //todo log;
                unset($this->chatMap[$ctaskId]);
            }

            switch($command->getCommand()){
                case 'read':
                    
            }
        }   
    }
}

$chatRes = new ChatResource();

$chatTask = new ChatTask();

$chatTask->newSource($chatRes);
