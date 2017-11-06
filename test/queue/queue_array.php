<?php

$limit = 10000;
$start = microtime();

$arrq = [];

for($i=0; $i<$limit; $i++)
{
    $data = "hello $i\n";
    array_push($arrq, $data);
    // $arrq->enqueue($data);

    if($i %100 == 99 && count($arrq) > 100)
    {
        $popN = rand(10, 99);
        for($j=0; $j<$popN; $j++)
        {
            array_shift($arrq);
            // $arrq->dequeue();
        }
    }
}

$popN = count($arrq);
for ($j = 0; $j < $popN; $j++)
{
    array_shift($arrq);
    // $arrq->pop();
}

$end = microtime();
var_dump(getrusage());

echo (($end-$start)*1000)."ms run the programs\n";
