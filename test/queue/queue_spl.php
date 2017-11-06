<?php

$limit = 10000;

$start = microtime(true);
$splq = new SplQueue();

for($i=0; $i<$limit; $i++)
{
    $data = "hello $i\n";
    $splq->enqueue($data);

    if($i %100 == 99 && count($splq) > 100)
    {
        $popN = rand(10, 99);
        for($j=0; $j<$popN; $j++)
        {
            $splq->dequeue();
        }
    }
}

$popN = count($splq);
for ($j = 0; $j < $popN; $j++)
{
    $splq->pop();
}

$end = microtime(true);
var_dump(getrusage());
echo (($end-$start)*1000)."ms run the programs\n";
