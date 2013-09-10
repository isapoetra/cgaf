<?php
namespace System\JSON;
class JSONError extends JSONResponse
{
    public $trace;

    function getIgnore()
    {
        return false;
    }

    function __construct($msg)
    {
        parent :: __construct();
        $this->success = false;
        $this->metadata = array(
            "totalProperty" => 'results',
            "root" => 'rows',
            "id" => 'id',
            "fields" => array(
                array(
                    "name" => "msg"
                )
            )
        );
        $this->results = 1;
        $this->addMsg($msg, "system");
    }
}