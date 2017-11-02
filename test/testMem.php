<?php

include __DIR__."/../lib/Daemon.php";


function callbackFunc()
{
    while(true)
    {
        echo "pid :".getmypid()."is runing \n";
        sleep(10);
    }
}


$count = $argv[1] ? $argv[1] : 1;

$daemon = new Daemon();

for($i=0; $i < $count; $i++)
{
    $daemon->addTask('callbackFunc', []);
}

// var_dump($daemon->task);
// var_dump($daemon->getTask());
// var_dump($daemon->runCallBackFunction());
$daemon->run($count);

//338788 125576
//543584 133712