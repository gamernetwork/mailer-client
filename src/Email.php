<?php

namespace mailer;

/*
    Object representing an email
*/
class Email {

    public $recipients;
    public $subject;
    public $html_body;
    public $txt_body;
    public $sender_email;
    public $sender_name;
    
    public function __construct() {
        $this->txt_body = '';
    }

    public static function newInstance() {
        return new Email();
    }

}
