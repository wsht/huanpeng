<?php

class ChatBuffer
{
    private $buffer;

    public function addBuffer($string)
    {
        $this->buffer .= $string;
    }

    public function read()
    {
        $len = (int)$this->buffer;
        $trimStr = $len."\r\n";
        $this->buffer = ltrim($this->buffer, $trimStr);

        $msg = substr($this->buffer, 0, $len);

        if(strlen($msg) != $len)
        {
            return '';
        }
        else
        {
            $this->buffer = substr($this->buffer, $len - 1);
            return $msg;
        }
    }
}


$msg = "91
enc=yes
command=receivemessage
content=q1YqUbJSMjQ0MFHSUcrNTAFxTA0sLA1NjY0tgEKpQAEDpVoA
46
enc=no
command=result
content=send.success
";

$buffer = new ChatBuffer();

$buffer->addBuffer($msg);

var_dump($buffer->read());
var_dump($buffer->read());
