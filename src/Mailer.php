<?php

namespace mailer;

use Requests;

use \mailer\APIError;

/*
    Wrapper on the egnsausages API defined at: https://mailer-api.gamer-network.net/api/
*/
class Mailer {
    
    /*
        Params

        api_root:               Root URL of the mailer API instance EG 
                                    http://mailer-api.gamer-network.net/api/v1/
        site:                   Identifier for the site using the API
        default_sender_email:   Default email to use as sent from email
        default_sender_name:    Default name to use as sent from name
    */
    public function __construct($api_root, $site, $default_sender_email,
            $default_sender_name) {
        $this->api_root = $api_root;
        $this->site = $site;
        $this->default_sender_email = $default_sender_email;
        $this->default_sender_name = $default_sender_name;
    }


    /*
        Send a transactional email given a \mailer\Email object and purpose string.
    */
    public function send($email, $purpose) {
        return $this->send_transactional($email->recipients, $purpose,
            $email->subject, $email->html_body, $email->txt_body, 
            $email->sender_email, $email->sender_name);
    }

    /*
        Send a transactional (one off) email to a list of recipients.

        Params
        users:          List of users to send to, of format 
                            {'name': 'bob', 'email': 'bob@example.net'}
        purpose:        Reason for sending this email (for audit trail)
        subject:        The email subject
        html_body:      Body text in html
        txt_body:       (Optional) Body text
        sender_email:   (Optional) Sent from email
        sender_name:    (Optional) Sent from name
    */
    public function send_transactional($users, $purpose, $subject, $html_body,
            $txt_body='', $sender_email=null, $sender_name=null) {
        if ($sender_email == null) {
            $sender_email = $this->default_sender_email;
        }
        if ($sender_name == null) {
            $sender_name = $this->default_sender_name;
        }
        $data = [
            "users"         => $users,
            "purpose"       => $purpose,
            "site"          => $this->site,
            "subject"       => $subject,
            "html_body"     => $html_body,
            "txt_body"      => $txt_body,
            "sender_email"  => $sender_email,
            "sender_name"   => $sender_name,
        ];
        return $this->post("send_transactional", $data);
    }

    protected function post($path, $data) {
        $response = $this->call($path, "post", $data);
        return $response;
    }

    protected function put($path, $data) {
        $response = $this->call($path, "put", $data);
        return $response;
    }

    protected function get($path) {
        $response = $this->call($path, "get");
        return $response;
    }

    protected function buildURL($path) {
        $trimmed_path = trim($path, '/');
        $url = join('/', [$this->api_root, $trimmed_path]);
        return $url;
    }

    protected function call($path, $method, $data=null) {
        $url = $this->buildURL($path);
        $headers = array('Content-Type' => 'application/json', 
            'Accept' => 'application/json');
        if ($data) {
            $encoded_data = json_encode($data);
            $response = Requests::$method($url, $headers, $encoded_data);
        }
        else {
            $response = Requests::$method($url, $headers);
        }
        switch ($response->status_code) {
            case 200:
            case 201:
                $response->data = json_decode($response->body, true);
                return $response->data;
            default:
                $response_data = json_decode($response->body, true);
                $message = "Mailer returned an error: " . $response->status_code;
                if (array_key_exists('detail', $response_data)) {
                    $message = $message . " " . $response_data['detail'];
                }
                throw new APIError($response_data, $response->status_code, $message);
        }
    }
}
