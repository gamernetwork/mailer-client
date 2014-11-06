<?php

namespace mailer;

use Requests;

use \mailer\APIError;

/*
    Wrapper on the egnsausages API defined at: https://mailer-api.gamer-network.net/api/
*/
class Mailer {
    
    public function __construct($api_root, $site, $default_sender_email,
            $default_sender_name) {
        $this->api_root = $api_root;
        $this->site = $site;
        $this->default_sender_email = $default_sender_email;
        $this->default_sender_name = $default_sender_name;
    }

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
