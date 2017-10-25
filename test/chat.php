<?php
// $byte = new Byte();

// $byte->writeChar("aldfjalds\r\nasfsf\r\n");
// var_dump($byte->getByte());

// exit();
set_time_limit(0);
function msg_base64_encode(string $str)
{
    $base64Str = base64_encode($str);
    $base64Str = str_replace(array('+', '/', '='), array('(', ')', '@'), $base64Str);

    return $base64Str;
}

function msg_base64_decode(string $str)
{
    $base64Str = str_replace(array('(', ')', '@'), array('+', '/', '='), $str);
    $base64Str = base64_decode($base64Str);

    return $base64Str;
}

function msg_encode(string $msg) : string
{
    $msgGz = gzdeflate($msg, 6);
    $msgGz = msg_base64_encode($msgGz);

    return $msgGz;
}

function msg_decode(string $msgGz) : string
{
    $msg = msg_base64_decode($msgGz);
    $msg = gzinflate($msg);

    return $msg;
}

class Byte
{
    private $length;
    private $byte;

    public function getByte()
    {
        return $this->byte;
    }

    public function getLength()
    {
        return $this->length;
    }

    public function writeChar($str)
    {
        $this->length += strlen($str);
        $str = array_map("ord", str_split($str));
        foreach ($str as $vo) {
            // echo "$vo=>".pack('c', $vo);
            $this->byte .= pack("c", $vo);
        }
        // $this->byte.=pack('c','0');
        // $this->length++;
    }
}

class SocketResponseMessage
{

    const COMMAND_TYPE_LOGIN = 0;
    const COMMAND_TYPE_RESULT = 1;
    const COMMAND_TYPE_SENDMESSAGE = 2;
    const COMMAND_TYPE_RECIEVEMESSAGE = 3;
    const COMMAND_TYPE_DISCONNECT = 4;

    private $isEnc;
    private $command;
    private $content;
  

    public function getIsEnc() : bool
    {
        return $this->isEnc;
    }

    public function setIsEnc(bool $isEnc)
    {
        $this->isEnc = $isEnc;
    }

    public function getCommand() : int
    {
        return $this->command;
    }

    public function setCommand(int $command)
    {
        $this->command = $command;
    }

    public function getContent() : string
    {
        return $this->content;
    }

    public function setContent(string $content)
    {
        $this->content = $content;
    }
}

interface SocketCallBack
{
    public function onMessageCallBack(SocketResponseMessage $msg);

    public function onErrorCallBack(int $var);

    public function onLoginSucceed();
}

class TimerTask
{
    private $task;

    private $runtime;
    private $interval;

    public function init($task, $interval)
    {
        var_dump($task);
        $this->task = $task;
        $this->interval = $interval;
        $this->runtime = time();
    }

    public function run()
    {   
        var_dump(time());
        if($this->task && time() >= $this->runtime + $this->interval)
        {
            echo "task run at ".time()."\n last runtime={$this->runtime}\n interval={$this->interval}\n";
            $this->runtime = time();            
            call_user_func($this->task);
        }
    }

    public function cancel()
    {
        $this->task = null;
        $this->interval = null;
        $this->runtime = null;
    }
}

class ChatBuffer
{
    private $buffer;

    public function addBuffer($string)
    {
        $this->buffer .= $string;
    }

    public function read()
    {
        $len = (int)$this->buffer;
        $trimStr = $len."\r\n";
        $this->buffer = ltrim($this->buffer, $trimStr);

        $msg = substr($this->buffer, 0, $len);

        if(strlen($msg) != $len)
        {
            return '';
        }
        else
        {
            $this->buffer = substr($this->buffer, $len - 1);
            return $msg;
        }
    }
}

class SocketServer
{
    const STATE_STOP = 0;
    const STAT_CONNECTING = 1;
    const STAT_START = 2;

    private $socket;
    private $ip;
    private $port;
    private $uid;
    private $roomid;
    private $enc;
    private $stopFlags;
    private $status;
    private $callBack;
    private $pingTimer;
    private $chatBufferObj;

    const THREAD_SLEEP_MICROSECOND = 100;

    public function __construct()
    {
        $this->log("run SocketServer");
    }

    public function setCallBack(SocketCallBack $callBack)
    {
        $this->callBack = $callBack;
    }

    private function getCallBack() : SocketCallBack
    {
        return $this->callBack;
    }

    private function setPingTimer(TimerTask $pingTimer)
    {
        $this->pingTimer = $pingTimer;
    }

    private function getPingTimer():TimerTask
    {
        return $this->pingTimer;
    }

    private function setChatBufferObj(ChatBuffer $buffer)
    {   
        $this->chatBufferObj = $buffer;
    }

    private function getChatBufferObj():ChatBuffer
    {
        return $this->chatBufferObj;
    }

    private function onError(int $errno)
    {
        $this->status = self::STATE_STOP;
        $this->getPingTimer()->cancel();

        if(!$this->stopFlags && $this->callBack)
        {
            $this->getCallBack()->onErrorCallBack($errno);
        }
    }

    private function run()
    {
        try{
            $this->connect();
        }catch(Exception $e)
        {
            $this->log("run error connect failed");
            return;
        }

        $this->status = self::STAT_CONNECTING;

        $this->sendPacketLogin($this->uid, $this->enc, $this->roomid);

        try{
            $this->readPacket();
        }catch(Exception $e)
        {
            $this->log($e->getCode()."".$e->getMessage());
        }
    
        socket_close($this->socket);
    }

    public function login(string $ip, string $port, string $uid, string $encpass, string $roomid )
    {
        $this->log("APP call login");
        $this->ip = $ip;
        $this->port = $port;
        $this->uid = $uid;
        $this->enc = $encpass;
        $this->roomid = $roomid;
        $this->setPingTimer(new TimerTask());
        $this->setChatBufferObj((new ChatBuffer()));
        $this->status = self::STAT_CONNECTING;
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        

        $this->run();
    }

    private function connect()
    {
        $this->stopFlags = false;
        $result = socket_connect($this->socket, $this->ip, $this->port);
        if (!$result) {
            throw new Exception("Socket connect failed", 1);
        }
    }

    public function disconnect()
    {
        $this->stopFlags = true;
        $this->mylog("APP call disconnect");
        socket_close($this->socket);
    }

    private function writePacket($buffer)
    {
        socket_write($this->socket, $buffer);
    }

    private function readPacket()
    {
        //设置定时任务 每10秒钟执行一次
        $this->getPingTimer()->init(function(){$this->timerTask();}, 10);
        $responseMsgStr = '';
        $i = 0;
        while (true) {
            $this->log("No.$i start");
            $stime = microtime(true);

            $this->getPingTimer()->run();
            $this->log("No.$i run line".__LINE__);

            do {
                if ( ($responseMsgStr = socket_read($this->socket, 8192)) === FALSE) {
                    $this->getPingTimer()->cancel();
                    return;
                }
                var_dump($responseMsgStr."\n");
            } while ($this->status == self::STATE_STOP);

            $this->log("No.$i run line".__LINE__);
            
            if($responseMsgStr)
                $this->getChatBufferObj()->addBuffer($responseMsgStr);
            
            $this->log("No.$i run line".__LINE__);            

            $responseMsgStr = $this->getChatBufferObj()->read();
            $this->log("No.$i run line".__LINE__);
            
            if ($responseMsgStr) {
                $responseMsg = $this->decodePacket($responseMsgStr);
                if ($this->callBack) {
                    if ($responseMsg->getContent() == "login.success") {
                        $this->getCallBack()->onLoginSucceed();
                    }
                    else{
                        $this->getCallBack()->onMessageCallBack($responseMsg);
                    }
                }
            }
            $this->log("No.$i run line".__LINE__);
            $this->log("No.$i end");            
            $etime = microtime(true);
            $i++;
            // var_dump("real run time is .".($etime - $stime));
            usleep(self::THREAD_SLEEP_MICROSECOND);
            // var_dump($i);
        }
    }

    private function timerTask()
    {
        echo "run ".__FUNCTION__."\n";
        $this->sendPacketMessage('noop');
    }

    private function buildWriteBuffer(array $body) : string
    {
        $lenString = '';
        $str = implode("\r\n", $body) . "\r\n";

        $strlen = strlen("$str");
        $appendZeroSize = 8 - strlen("$strlen");
        for ($i = 0; $i < $appendZeroSize; ++$i) {
            $lenString .= '0';
        }

        $lenString .= $strlen;
        $lenString = $lenString . "\r\n";
        $str = $lenString . $str;

        return $str;
    }

    private function decodePacket(string $msg) : SocketResponseMessage
    {
        $msg = trim($msg, "\r\n");
        $msg_arr = explode("\r\n", $msg);

        $message = new SocketResponseMessage();

        if (count($msg_arr) != 3) {
            return null;
        }
        else {
            if ($msg_arr[0] == "enc=no") {
                $message->setIsEnc(FALSE);
                $message->setContent(str_replace("content=", "", $msg_arr[2]));
            }
            else {
                $message->setIsEnc(TRUE);
                $message->setContent(msg_decode(str_replace("content=", "", $msg_arr[2])));
            }

            if ($msg_arr[1] == "command=receivemessage") {
                $message->setCommand(SocketResponseMessage::COMMAND_TYPE_RECIEVEMESSAGE);
            }

            if ($msg_type[1] == "command=result") {
                $messge->setCommand(SocketResponseMessage::COMMAND_TYPE_RESULT);
            }

            return $message;
        }
    }

    private function sendPacketLogin(string $uid, string $encpass, string $roomid) : bool
    {
        $body = [
            "command=login",
            "uid=$uid",
            "encpass=$encpass",
            "roomid=$roomid",
        ];

        $buffer = $this->buildWriteBuffer($body);
        $this->writePacket($buffer);

        return true;
    }

    public function sendPacketMessage(string $message) : bool
    {
        $body = [
            'command=sendmessage',
            'content=' . msg_encode($message)
        ];

        $buffer = $this->buildWriteBuffer($body);
        var_dump($buffer);
        $this->writePacket($buffer);

        return true;
    }

    public function log($msg)
    {
        echo $msg, "\n";
    }
}


