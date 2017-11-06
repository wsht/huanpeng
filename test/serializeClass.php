<?php

function serializeRecursion($obj)
{
    $func  = function()
    {
        return get_object_vars($this);   
    };

    $result = $func->call($obj, $obj);
    var_dump(serialize($result));
    // var_dump($result->call($obj, $obj));
} 

class SubObj implements Serializable
{
    private $a;
    public $b;
    protected $c;

    public function __construnt()
    {
        $this->a = 'a';
        $this->b = 'b';
        $this->c = 'c';
    }

    public function serialize()
    {
        // var_dump(serialize (get_object_vars($this)));
        // var_dump(serialize($this));
        return serialize(get_object_vars($this));
    }

    public function unserialize( $serialized )
    {
        $data = unserialize($serialized);
        foreach($data as $property => $value)
        {
            if(property_exists($this, $property))
            {
                $this->$property = $value;
            }
        }
    }
}

class SerializeTest implements Serializable
{
    private $a;
    public $b;
    protected $c;
    public static $d;
    protected static $e;
    public $subObj ;
    public $resource;

    public function __construct()
    {
        $this->a = 'a';
        $this->b = 'b';
        $this->c = 'c';
        static::$d = 'd';
        static::$e = 'e';
        $this->subObj = new SubObj();
        $this->resource = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    }

    public function serialize()
    {
        // var_dump(serialize (get_object_vars($this)));
        // var_dump(serialize($this));
        // serializeRecursion($this);
        return serialize(get_object_vars($this));
    }

    public function unserialize( $serialized )
    {
        $data = unserialize($serialized);
        foreach($data as $property => $value)
        {
            if(property_exists($this, $property))
            {
                $this->$property = $value;
            }
        }
    }
}

$obj = new SerializeTest();

$str = serialize($obj);
var_dump($str);
var_dump(unserialize($str));