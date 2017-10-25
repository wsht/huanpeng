<?php

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

// $byte = new Byte();

// $byte->writeChar("aldfjalds\r\nasfsf\r\n");
// var_dump($byte->getByte());

// exit();

$address = "122.70.146.49";
$port = "8082";
$uid = 1930;
$encpass = '9db06bcff9248837f86d1a6bcf41c9e7';
$roomid = 3375;


$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

if (!$socket) {
    die("socket create failed \n");
}

$result = socket_connect($socket, $address, $port);

doLogin($uid, $encpass, $roomid, $socket);

while ($result) {
    $hear = socket_read($socket, 8192);

    if ($hear != '') {
        var_dump($hear);
    }
    usleep(100000);
}

socket_close($socket);


function mylog($msg)
{
    echo "$msg\n";
}

function doLogin($uid, $encpass, $roomid, $socket)
{
    mylog("uid:$uid login roomid:$roomid");
    $str = [
        "command=login",
        "uid=$uid",
        "encpass=$encpass",
        "roomid=$roomid",
    ];

    $str = implode("\r\n", $str)."\r\n";

    $strlen = strlen("$str");
    // mylog("$str");
    mylog($strlen);
    $appendZeroSize = 8 - strlen("$strlen");
    $lenString = '';
    for ($i = 0; $i<$appendZeroSize; ++$i) {
        $lenString .= '0';
    }

    $lenString  .= $strlen;
    $lenString = $lenString."\r\n";

    // mylog($lenString);

    $str = $lenString.$str;
    // $str = "00000080\r\n".$str;

    mylog($str);

    // $byte = new Byte();
    // $byte->writeChar($str);
    // $str = $byte->getByte();
    
    socket_write($socket, $str);

    return true;
}


class SocketServer
{
    private $socket;
    private $ip;
    private $port;
    private $stopFlags;
    
    const THREAD_SLEEP_MICROSECOND = 100000;

    public function __construct()
    {
        $this->log("run SocketServer");
    }

    private function connect()
    {
        $this->stopFlags = false;
        $result = socket_connect($this->socket, $this->ip, $this->port);
        if (!$result) {
            throw new Exception("Socket connect failed", 1);
        }
    }

    private function disconnect()
    {
        $this->stopFlags = true;
        $this->mylog("APP call disconnect");
        socket_close($this->socket);
    }

    private function write($buffer)
    {
        socket_write($this->socket, $buffer);
    }

    private function readPacket()
    {
        while (true) {
        }
    }

    
    private function buildWriteBuffer(array $body):string 
    {
        $str = implode("\r\n", $str)."\r\n";

        $strlen = strlen("$str");
        $appendZeroSize = 8 - strlen("$strlen");
        for ($i = 0; $i<$appendZeroSize; ++$i) {
            $lenString .= '0';
        }

        $lenString  .= $strlen;
        $lenString = $lenString."\r\n";
        $str = $lenString.$str;

        return $str;
    }

    public function log($msg)
    {
        echo $msg , "\n";
    }
}
