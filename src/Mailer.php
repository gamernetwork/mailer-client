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
        auth_username:          basicauth username to access api
        auth_password:          basicauth password to access api
        subject_prefix:         Prefixes all outgoing mail with this string
    */
    public function __construct($api_root, $site, $default_sender_email,
            $default_sender_name, $auth_username=null, $auth_password=null, 
            $subject_prefix="", $verify_ssl=true) {
        $this->api_root = $api_root;
        $this->site = $site;
        $this->default_sender_email = $default_sender_email;
        $this->default_sender_name = $default_sender_name;
        $this->auth_username = $auth_username;
        $this->auth_password = $auth_password;
        $this->subject_prefix = $subject_prefix;
        $this->verify_ssl = $verify_ssl;
    }


    /*
        Send a transactional email given a \mailer\Email object and purpose string.
    */
    public function send($email, $purpose) {
        $subject = $email->subject;
        if ($this->subject_prefix) {
            $subject = $this->subject_prefix . ': ' . $subject;
        }
        return $this->send_transactional($email->recipients, $purpose,
            $subject, $email->html_body, $email->txt_body, 
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
        $subject = substr($subject, 0, 69);
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

    public function get_message($message_id) {
        return $this->get("messages/$message_id");
    }

    /*
        Get latest messages on the API.        
            subject_filter: optional.  Filter message results to those 
                                        starting with this string
    */
    public function get_messages($subject_filter=null) {
        $path = "messages";
        if ($subject_filter) {
            $path .= "?subject=$subject_filter";
        }
        return $this->get($path);
    }

    public function subscribe($email, $subscription, $send_confirmation = false) {
        $data = [
            "email"             => $email,
            "subscription_list" => $subscription,
            "send_confirmation" => $send_confirmation,
        ];
        return $this->post("subscription", $data);
    }

    public function get_subscriptions($email) {
        return $this->get("subscription?email=$email");
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
        $options = [];
        if ($this->auth_username && $this->auth_password) {
            $options['auth'] = [$this->auth_username, $this->auth_password];
        }
        if ($this->verify_ssl == false) {
            $options['verify'] = false;
        }
        if ($data) {
            $encoded_data = json_encode($data);
            $response = Requests::$method($url, $headers, $encoded_data, $options);
        }
        else {
            if ($method == 'get') {
                $response = Requests::get($url, $headers, $options);
            }
            else {
                $response = Requests::$method($url, $headers, "", $options);
            }
        }
        switch ($response->status_code) {
            case 200:
            case 201:
                $response->data = json_decode($response->body, true);
                return $response->data;
            default:
                $response_data = json_decode($response->body, true);
                $message = "Mailer returned an error: " . $response->status_code;
                if ($response_data) {
                    if (array_key_exists('detail', $response_data)) {
                        $message = $message . " " . $response_data['detail'];
                    }
                }
                throw new APIError($response_data, $response->status_code, $message);
        }
    }
}
