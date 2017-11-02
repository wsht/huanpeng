<?php

class SystemCall{
    protected $callback;
    
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function __invoke(Task $task, Scheduler $scheduler)
    {
        $callback = $this->callback;
        return $callback($task, $scheduler);
    }
}

class Task{
    protected $taskId;
    protected $coroutine;
    protected $sendValue = null;
    protected $beforeFirstYield = true;


    public function __construct($taskId, Generator $coroutine)
    {
        $this->taskId = $taskId;
        $this->coroutine = $coroutine;
    }

    public function getTaskId()
    {
        return $this->taskId;
    }

    public function setSendValue($sendValue)
    {
        $this->sendValue = $sendValue;
    }

    public function run()
    {
        if($this->beforeFirstYield)
        {
            $this->beforeFirstYield = false;
            return $this->coroutine->current();
        }
        else
        {
            $retval = $this->coroutine->send($this->sendValue);
            $this->sendValue = NULL;
            return $retval;
        }
    }

    public function isFinished()
    {
        return !$this->coroutine->valid();
    }
}

class Scheduler{
    protected $maxTaskId = 0;
    protected $taskMap = [];
    protected $taskQueue;

    public function __construct()
    {
        $this->taskQueue = new SplQueue();
    }

    public function newTask(Generator $coroutine)
    {
        $tid = ++$this->maxTaskId;
        $task = new Task($tid, $coroutine);
        $this->taskMap[$tid] = $task;
        $this->schedule($task);
        return $tid;
    }

    public function schedule(Task $task)
    {
        $this->taskQueue->enqueue($task);
    }

    public function run()
    {
        // var_dump($this->taskQueue);
    
        while(!$this->taskQueue->isEmpty())
        {
            $task = $this->taskQueue->dequeue();
            // var_dump($task);
            $retval = $task->run();

            if($retval instanceof SystemCall)
            {
                $retval($task, $this);
                continue;
            }

            if($task->isFinished())
            {
                unset($this->taskMap[$task->getTaskId()]);
            }
            else
            {
                $this->schedule($task);
            }
            // var_dump($this->taskQueue);
        }
    }

    public function killTask($tid)
    {
      
        if(!isset($this->taskMap[$tid]))
        {
            return false;
        }

        unset($this->taskMap[$tid]);

        // This is a bit ugly and could be optimized so it does not have to walk the queue,
        // but assuming that killing tasks is rather rare I won't bother with it now
        foreach ($this->taskQueue as $i => $task) {
            if ($task->getTaskId() === $tid) {
                unset($this->taskQueue[$i]);
                var_dump("stop $tid");
                // exit();
                // break;
            }
        }
    
        return true;
    }
}