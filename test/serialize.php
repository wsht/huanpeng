<?php


//测试序列化resource资源结果
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
// var_dump($socket);//resource(4) of type (Socket)

$str = serialize($socket);
// var_dump($str);//string(4) "i:0;"

// var_dump(unserialize($str));//int(0)


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


// $ser = new SerializeTest();
// $value = serialize($ser);

// echo "Class SerializeTest serialize result is \n";
// var_dump($value);
/*
string(93) "O:13:"SerializeTest":4:{s:1:"b";i:3;s:16:"\000SerializeTest\000c";N;s:4:"\000*\000d";N;s:8:"resource";N;}"
*/

// echo "Class SerializeTest unserialize result is \n";
// $obj = unserialize($value);
// var_dump($obj);
/*
class SerializeTest#2 (4) {
  public $b =>
  int(3)
  private $c =>
  NULL
  protected $d =>
  NULL
  public $resource =>
  NULL
}
*/
// var_dump($obj->get());

/*
array(5) {
  [0] =>
  NULL
  [1] =>
  int(3)
  [2] =>
  NULL
  [3] =>
  NULL
  [4] =>
  NULL
}
*/


