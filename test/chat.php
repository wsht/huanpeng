<?php
// $byte = new Byte();

// $byte->writeChar("aldfjalds\r\nasfsf\r\n");
// var_dump($byte->getByte());

// exit();
/**
 * 注意 线程时通过序列化来实现的，那么资源类是无法传递的，但是父进程可以访问子线程方法和属性
 * 根据需求 编写socket 客户端程序，只能将socket作为最底层的线程方式来实现，通过上层调用
 * 来实现需求
 */

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

class HPCallAble
{
    private $callObj;
    private $callFunc;
    private $params;

    public function __construct($callFunc, array $params, $callObj=null)
    {
        $this->callFunc = $callFunc;
        $this->params = $params;
        $this->callObj = $callObj;
    }

    public function run()
    {
        //todo undefind call function or invaild 
        // var_dump("HPCALLABLE params is \n");
        // var_dump($this->params);
        // var_dump($this->callObj->socket);
        // var_dump("\n");
        if($this->callObj)
        {
            return call_user_func_array([$this->callObj, $this->callFunc], $this->params);
        }
        else
        {
            return call_user_func_array($this->callFunc, $this->params);
        }
    }
}

// class TimerTask extends Thread
// {
//     private $callAble;
//     private $runtime;
//     private $interval;

//     private function setCallAble(HPCallAble $callAble)
//     {
//         $this->callAble = $callAble;
//     }

//     private function getCallAble():HPCallAble
//     {
//         return $this->callAble;
//     }

//     public function init(HPCallAble $callAble, $interval)
//     {
//         $this->callAble = $callAble;
//         $this->interval = $interval;
//         $this->runtime = time();
//     }

//     public function run()
//     {   
//         sleep($this->interval);
//         if($this->callAble)
//         {
//             echo "task run at ".time()."\n last runtime={$this->runtime}\n interval={$this->interval}\n";
//             $this->runtime = time();            
//             $this->getCallAble()->run();
//         }
//     }

//     public function cancel()
//     {
//         $this->task = null;
//         $this->interval = null;
//         $this->runtime = null;
//         $this->kill();
//     }
// }


/**
 * 注意 thread 开启线程，线程内部变量需要经过序列化，详情可以看serializeClass.php
 * 所以其socket资源变为int(0) 注意，这里是父线程对子进程赋值的时候resource资源时被序列化的
 * 但是 如果自线程是在自己内部赋值，那么对于其包含关系的类是可以访问其属性的(待测试)。
 */
class TimerTask extends Thread
{
    private $runtime;
    private $interval;
    private $obj;
    private $socket;
    public function init($obj, $interval, $socket)
    {
        $this->obj = $obj;
        $this->interval = $interval;
        $this->runtime = time();
        echo "timer task params obj task is";
        var_dump($obj->socket);
        var_dump($socket);
        // var_dump($obj->socket);
        // var_dump("Timer task init the obj is\n");
        var_dump($this->obj);
        // var_dump("\n\n");
        var_dump(class_implements($this->obj));

        // var_dump("Timer task init the params socket is \n");
        // var_dump($socket);
        $this->socket = $socket;
    }

    public function run()
    {
        while($this->interval)
        {
            sleep($this->interval);
            if ($this->obj){
                $this->runtime = time();
                $this->obj->timerTask($this->socket);
            }
        }
        
    }

    public function cancel()
    {
        $this->task = null;
        $this->interval = null;
        $this->runtime = null;
        // $this->kill();
        socket_close($this->socket);
        exit();
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

    public static $test=1;

    /* share the $socket to thread*/
    public  $socket;
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

    public static function getSocket()
    {
        return $this->socekt;
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
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        // self::setSocket($socket);
        $this->socket = $socket;

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

    private function writePacket($buffer, $socket=null)
    {   
        // $socket ?? $this->socket;
        $socket = $socket ? $socket : $this->socket;
        
        socket_write($socket, $buffer);
    }

    private function readPacket()
    {
        //设置定时任务 每10秒钟执行一次

        // $callAble = new HPCallAble("timerTask",[$this->socket], $this);
        $this->getPingTimer()->init($this, 10, $this->socket);
        $this->getPingTimer()->start();
        $responseMsgStr = '';
        $i = 0;

        while (true) {
            $stime = microtime(true);
            do {
                
                $responseMsgStr = socket_read($this->socket, 8192);
                var_dump("back \$responseMsgStr is  ".json_encode($responseMsgStr));            
                if ( $responseMsgStr === false) {
                    $this->getPingTimer()->cancel();
                    return;
                }
            } while ($this->status == self::STATE_STOP);

            
            if($responseMsgStr)
                $this->getChatBufferObj()->addBuffer($responseMsgStr);
            
            $responseMsgStr = $this->getChatBufferObj()->read();
            
            if ($responseMsgStr) {
                // var_dump($responseMsgStr);
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
            $etime = microtime(true);
            $i++;
            usleep(self::THREAD_SLEEP_MICROSECOND);
        }
    }

    public function timerTask($socket=NULL)
    {
        $this->sendPacketMessage('noop', $socket);
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

            if ($msg_arr[1] == "command=result") {
                $message->setCommand(SocketResponseMessage::COMMAND_TYPE_RESULT);
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

    public function sendPacketMessage(string $message, $socket=null) : bool
    {
        $body = [
            'command=sendmessage',
            'content=' . msg_encode($message)
        ];

        $buffer = $this->buildWriteBuffer($body);
    
        $this->writePacket($buffer, $socket);

        return true;
    }

    public function log($msg)
    {
        echo $msg, "\n";
    }
}


