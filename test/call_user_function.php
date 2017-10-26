<?php

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


class TimerTask //extends Thread
{
    private $callab;

    private function setCallAb(HPCallAble $callab)
    {
        $this->callab = $callab;
    }

    private function getCallAb(): HPCallAble
    {
        return $this->callab;
    }

    public function init(HPCallAble $callab)
    {
        $this->setCallAb($callab);
    }

    public function run()
    {
        while(true)
        {
            $this->getCallAb()->run();
            sleep(1);
        }
    }
}

class ServerTest
{
    public  $i;

    private $timerTask;

    public function setTimerTask($timerTask)
    {
        $this->timerTask = $timerTask;
    }

    public function getTimerTask() : TimerTask
    {
        return $this->timerTask;
    }

    public function __construct($i){
        $this->i = $i;
        $this->setTimerTask(new TimerTask());
    }

    public function run()
    {
        $callab = new HPCallAble("task", [], $this);
        $this->getTimerTask()->init($callab);
        $this->getTimerTask()->run();

        echo "end\n";
    }

    public function task()
    {
        echo $this->i."\n";
        $this->i++;
    }
}

// $ser = new ServerTest(0);

// $ser->run();

// $ser2 = new ServerTest(5);
// $ser2->run();


class TimerTask2 extends Thread
{
    private $callab;

    private function setCallAb($callab)
    {
        $this->callab = $callab;
    }

    private function getCallAb()
    {
        return $this->callab;
    }

    public function init( $callab)
    {
        $this->setCallAb($callab);
    }

    public function run()
    {
        while(true)
        {
            $this->getCallAb()->task();
            sleep(1);
        }
    }
}

class ServerTest2
{
    public  $i;

    private $timerTask;

    public function setTimerTask($timerTask)
    {
        $this->timerTask = $timerTask;
    }

    public function getTimerTask() : TimerTask2
    {
        return $this->timerTask;
    }

    public function __construct($i){
        $this->i = $i;
        $this->setTimerTask(new TimerTask2());
    }

    public function run()
    {
        // $callab = new HPCallAble("task", [], $this);
        $this->getTimerTask()->init($this);
        $this->getTimerTask()->start();

        echo "end\n";
    }

    public function task()
    {
        echo $this->i."\n";
        $this->i++;
    }
}

$ser2 = new ServerTest2(5);
$ser2->run();