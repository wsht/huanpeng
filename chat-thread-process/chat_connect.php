<?php


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
        $hear = trim($hear, "\r\n");
        var_dump(explode("\r\n", $hear));
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

    $str = implode("\r\n", $str) . "\r\n";

    $strlen = strlen("$str");
    // mylog("$str");
    mylog($strlen);
    $appendZeroSize = 8 - strlen("$strlen");
    $lenString = '';
    for ($i = 0; $i < $appendZeroSize; ++$i) {
        $lenString .= '0';
    }

    $lenString .= $strlen;
    $lenString = $lenString . "\r\n";

    // mylog($lenString);

    $str = $lenString . $str;
    // $str = "00000080\r\n".$str;

    mylog($str);

    // $byte = new Byte();
    // $byte->writeChar($str);
    // $str = $byte->getByte();

    socket_write($socket, $str);

    return true;
}