<?php

/**
 * 当调用send的时候 相当于执行了一个 yield  如下列所示，这时候当前返回第二个yield的值 bar
 */




function gen1()
{
    yield 'foo';
    yield 'bar';
}

$gen = gen1();

// var_dump($gen->send("somthing")); //bar
// exit;


/*----------------------------------------*/
function nums()
{
    for($i=0; $i<5; ++$i)
    {
        $cmd = yield $i;
        var_dump("$cmd \n");
        if($cmd == 'stop')
        {   
            yield $i;
            return;
        }
    }
}

$gen = nums();
var_dump($gen);

for($i=0; $i<5; $i++)
{
    if(!$gen->valid())
    {
        break;
    }

    echo "start current\n";

    $v = $gen->current();
    if($v == 3)
    {
        echo "send before\n";
        var_dump($gen->send('stop'));
        echo "send after\n";
    }
    echo "number is $v\n";
    echo "next before\n";
    $gen->next();
    echo "next after\n\n";
}
exit;


// var_dump($gen->current());
foreach($gen as $v)
{
    if($v == 3)
    {
        $gen->send('stop');
    }

    echo "$v\n";
}

exit;

/*----------------------------------------*/
function gen()
{
    $ret = (yield 'yield1');
    var_dump($ret);

    $ret = (yield 'yield2');
    var_dump($ret);
}

$gen = gen();

var_dump($gen->current());

var_dump($gen->send('hahahahah'));
// var_dump($gen->next());

var_dump($gen->send('ret1'));

// var_dump($gen->send('ret2'));