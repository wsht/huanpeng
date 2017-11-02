<?php
require __DIR__."/../lib/Scheduler.php";


/*任务和调度器之间的通信*/
//system call

function getTaskId()
{
    return new SystemCall(function(Task $task, Scheduler $scheduler){
        $task->setSendValue($task->getTaskId());
        $scheduler->schedule($task);
    });
}

function newTask(Generator $coroutine)
{
    return new SystemCall(
        function(Task $task, Scheduler $scheduler) use ($coroutine){
            $task->setSendValue($scheduler->newTask($coroutine));
            $scheduler->schedule($task);
        }
    );
}

function killTask($tid)
{
    return new SystemCall(
        function(Task $task, Scheduler $scheduler) use ($tid){
            $task->setSendValue($scheduler->killTask($tid));
            $scheduler->schedule($task);
        }
    );
}

function childTask()
{
    $tid = (yield getTaskId());
    while(true)
    {
        echo "Child task $tid still alive!\n";
        yield;
    }
}

function task(){
    $tid = (yield getTaskId());
    $childTid = (yield newTask(childTask()));
    var_dump($childTid);
    for($i = 1; $i<=6; $i++)
    {
        echo "Parent task $tid iteration $i.\n";
        yield;

        if($i==3) yield killTask($childTid);
    }
    exit();
}

$scheduler = new Scheduler;
$scheduler->newTask(task());
$scheduler->run();
exit();
// var_dump(task(10));

$scheduler->newTask(task(10));
$scheduler->newTask(task(5));

$scheduler->run();