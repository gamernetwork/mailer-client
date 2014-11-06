<?php

namespace mailer;

class APIError extends \Exception {

    public $response;
    public $status;

    public function __construct($response, $status, $message, $code=0, Exception $previous=null) {
        $this->response = $response;
        $this->status = $status;
        parent::__construct($message, $code, $previous);
    }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
