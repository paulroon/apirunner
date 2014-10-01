<?php

namespace HappyCode\Core;

use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;

class ApiRunner {

    protected $endpoints;

    protected $client;
    protected $lastResponse = [];
    protected $tokens = [];
    protected $report = [];

    public function __construct($configs, $token = false) {

        if(isset($configs['endpoints'])) {
            $this->endpoints = $configs['endpoints'];
        }

        $protocol = $configs['default']['use_ssl'] ? 'https' : 'http';
        $this->client = new Client(['base_url' => $protocol . '://' . $configs['default']['base_url']]);
        $authenticationToken = (!!$token) ? $token : $this->getAuthToken($configs['authentication'], $token);

        $this->client->setDefaultOption('query', $configs['default']['default']['query']);
        $this->client->setDefaultOption(
                                        'headers',
                                        [
                                            'Authorization' => 'Bearer ' . $authenticationToken,
                                            'Cookie' => $configs['default']['default']['cookie']
                                        ]
        );

        $this->checkAuth($configs['authentication']);

    }

    public function getAuthToken($auth, $token){
        if(!$token) {
            $this->tokens['application'] = $this->fetchApplicationBearerToken($auth);
            //$this->tokens['application'] = $this->fetchUserBearerToken($auth);
        }
        return $this->tokens['application'];
    }

    protected function fetchUserBearerToken($auth){
        return "userBearerTokenuserBearerTokenuserBearerTokenuserBearerToken==";
    }

    protected function fetchApplicationBearerToken($auth){
        $appBearerRequest = [
            'name' => 'Client Bearer Token',
            'path' => $auth['uri']['base'] . $auth['uri']['token'],
            'method' =>  'POST',
            'headers' =>  [ 'Authorization' => 'Bearer ' . base64_encode($auth['app_key'] . ":" . $auth['app_secret'])],
            'query' => [],
            'body' => [ 'grant_type' => 'client_credentials', 'scope' => 'basic,alpha_builder']
        ];

        $st = microtime(true);

        $response = $this->doRequest($appBearerRequest);

        $this->reportSuccess([
            "name" => "Client Bearer Token",
            "path" => $appBearerRequest['path'],
            "method" => "POST"
        ], $response, (microtime(true) - $st));

        return $this->lastResponse['json']['access_token'];
    }

    /**
     * override the token from the configs
     * @param $token
     */
    public function setAuthToken($token) {
        $this->client->setDefaultOption('headers/Authorization', 'Bearer ' . $token);
    }


    private function checkAuth($auth){

        $verify_uri = $auth['uri']['base'] . $auth['uri']['verify'];

        echo "Using Token " . $this->client->getDefaultOption('headers/Authorization');

        $st = microtime(true);
        try{
            $response = $this->client->get($verify_uri, []);

            $user = $response->json();

            $this->reportSuccess([
                "name" => "Token Authentication",
                "path" => $verify_uri,
                "method" => "POST"
            ], $response, (microtime(true) - $st));

            echo PHP_EOL . "Token Authenticated." . ", Hi " . $user['username'] . PHP_EOL;
        }catch(\Exception $e){
            echo PHP_EOL . "Oauth Authentication Failed in " . number_format((microtime(true) - $st), 3) . " secs" . PHP_EOL . "   - " . $e->getMessage() . PHP_EOL;
            exit;
        }
        return false;

    }

    public function run(){

        /**
         * Guzzle is very capable of sending requests in parallel - however this is a performance tool as well as a
         * for testing purposes
         * I don't want the requests to interfere with each other (or do I)?
         * so have chosen to do a (slower)? sequential set of tests
         */
        foreach($this->endpoints as $apiCall){

            echo PHP_EOL . "running " . $apiCall['name'] . "...";

            try{
                $start = microtime(true);
                $res = $this->doRequest($apiCall);
                $this->reportSuccess($apiCall, $res, (microtime(true) - $start));
            }catch(\Exception $e){
                echo '['.get_class($e).'] - ' . $e->getMessage() . PHP_EOL;
                $this->reportFail($apiCall, $e);
            }

        }

        return $this;
    }

    public function doRequest($apiCall) {

        $options = [];
        foreach(['query', 'body', 'headers'] as $opt) {
            if(isset($apiCall[$opt])) {

                // Endpoint query arrays are either augmented to the default OR et totally empty
                if(is_array($apiCall[$opt]) && empty($apiCall[$opt])){
                    $this->client->setDefaultOption($opt, []);
                }

                // Redefine
                if($opt == "headers"){
                    $this->client->setDefaultOption($opt, []);
                }


                $options[$opt] = $apiCall[$opt];
            }
        }

        $request = $this->client->createRequest(strtoupper($apiCall['method']), $apiCall['path'], $options);

        $response = $this->client->send($request);

        if('application/json' == $response->getHeader('content-type')) {
            $this->lastResponse['json'] = $response->json();
        }

        return $response;

    }

    public function reportSuccess($apiCall, Response $res, $took){

        $url = str_replace(['http://', 'https://'], "", $res->getEffectiveUrl() );
        $slPos = strpos($url, "/", 0);
        $host = substr($url, 0, $slPos);
        $path = str_replace(['http://', 'https://', $host], "", $apiCall['path']);

        $this->report[] = [
            'success' => true,
            'host' => $host,
            'name' => $apiCall['name'],
            'path' => $path,
            'method' => $apiCall['method'],
            'time' => number_format($took, 3),
            'status_code' => $res->getStatusCode(),
            'content_type' => $res->getHeader('content-type'),
            'response_length' => strlen($res->getBody())
        ];
    }

    public function reportFail($apiCall, \Exception $e){
        $this->report[] = [
            'success' => false,
            'name' => $apiCall['name'],
            'error' => $e->getMessage()
        ];
    }

    public function report(){
        echo PHP_EOL . PHP_EOL . "====== REPORT ====== " . PHP_EOL . PHP_EOL;


        $resultRow = function($host = null, $name = null, $path = null, $method = null, $time = null, $st = null, $r_len = null, $ct = null){
            $pad = ($name == null) ? "+" : " ";
            return sprintf("+ %s + %s + %s + %s + %s + %s + %s + %s +" .  PHP_EOL,
                str_pad($host, 30, $pad, STR_PAD_RIGHT),
                str_pad($name, 30, $pad, STR_PAD_RIGHT),
                str_pad($path, 45, $pad, STR_PAD_RIGHT),
                str_pad($method, 10, $pad, STR_PAD_RIGHT),
                str_pad($time, 15, $pad, STR_PAD_BOTH),
                str_pad($st, 10, $pad, STR_PAD_BOTH),
                str_pad($r_len, 20, $pad, STR_PAD_BOTH),
                str_pad($ct, 20, $pad, STR_PAD_RIGHT)
            );
        };

        echo $resultRow();
        echo $resultRow("Host", "Call", "Endpoint", "Method", "(Time )secs", "Status", "Content-Length", "Type");
        echo $resultRow();
        foreach($this->report as $callReport) {
            if($callReport['success']){
                echo $resultRow(
                    $callReport['host'],
                    $callReport['name'],
                    $callReport['path'],
                    $callReport['method'],
                    $callReport['time'],
                    $callReport['status_code'],
                    $callReport['response_length'],
                    $callReport['content_type']
                );
            }else{
                echo sprintf("+ %s: ERROR: (%s) +" .  PHP_EOL, str_pad($callReport['name'], 20, " ", STR_PAD_RIGHT), $callReport['error']);
            }
        }
        echo $resultRow();
    }
}