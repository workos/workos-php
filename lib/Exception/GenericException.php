<?php

namespace WorkOS\Exception;

class GenericException extends \Exception
{
    public $data;

    public function __construct($message, $data = null)
    {
        $this->message = $message;

        if (isset($data) && $data->length) {
            $this->data = $data;
        } else {
            $this->data = array();
        }
    }
}
