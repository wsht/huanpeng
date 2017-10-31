<?php

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
var_dump($socket);


$str = serialize($socket);
var_dump($str);

var_dump(unserialize($str));




exit;


class SerializeTest {
    public static $a;
    public $b = 3;
    private $c;
    protected $d;

    public $resource;

    public function __consturct()
    {
        $this->resource = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        static::$a = 1;
        $this->b = 2;
        $this->c = 3;
        $this->d = 4;
    }

    public function get()
    {
        return [static::$a, $this->b,$this->c,$this->d, $this->resource];
    }
}


$ser = new SerializeTest();


$value = serialize($ser);

echo "Class SerializeTest serialize result is \n";
var_dump($value);


echo "Class SerializeTest unserialize result is \n";
$obj = unserialize($value);
var_dump($obj);

var_dump($obj->get());


c:/Application/cygwin642/home/SEELE/project/my/huanpeng