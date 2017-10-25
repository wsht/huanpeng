<?php

include "chat.php";


class ChatHelper implements SocketCallBack
{
    private $server;
    private $ip;
    private $port;

    public function setPort($port)
    {
        $this->port = $port;
    }

    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    private function setServer(SocketServer $socketServer)
    {
        $this->server = $socketServer;
    }

    private function getServer():SocketServer
    {
        return $this->server;
    }

    public function login(string $uid, string $encpass, string $roomid)
    {   
        var_dump($this->ip);
        var_dump($this->port);
        if(empty($this->ip) || empty($this->port))
        {
            $this->log("empty ip:$ip or port:$port");
            return;
        }

        if($this->server)
        {
            $this->getServer()->disconnect();
        }
        
        $this->setServer((new SocketServer()));
        
        $this->getServer()->setCallBack($this);

        $this->getServer()->login($this->ip,$this->port, $uid, $encpass, $roomid);
    }

    private function sendEnterRoom()
    {   
        $msg = [
            't'=>104,
            'mid'=>time()
        ];
        $this->getServer()->sendPacketMessage(json_encode($msg));
    }

    public function onMessageCallBack(SocketResponseMessage $msg){
        var_dump($msg);
    }

    public function onErrorCallBack(int $var)
    {
        var_dump("error:$var");
        exit($var);
    }

    public function onLoginSucceed()
    {
        $this->sendEnterRoom();
    }

    private function log($msg)
    {
        echo "$msg\n";
    }
}

$address = "122.70.146.49";
$port = "8082";
$uid = 1930;
$encpass = '9db06bcff9248837f86d1a6bcf41c9e7';
$roomid = 3375;

$chatHelper = new ChatHelper();

$chatHelper->setIp($address);
$chatHelper->setPort($port);

$chatHelper->login($uid, $encpass, $roomid);