<?php 

class MessageEntity
{
    public $t;
    public $msg;

    public function setT($t)
    {
        $this->t = $t;
    }

    public function setMsg($msg)
    {
        $this->msg = $msg;
    }

    public function getJson()
    {
        return json_encode($this);
    }
}

$msg = new MessageEntity();

$msg->setT(1);
$msg->setMsg('aaaaaa');

var_dump($msg->getJson());

var_dump(json_encode($msg));

