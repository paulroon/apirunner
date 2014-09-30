<?php

namespace HappyCode\Core;

use GuzzleHttp\Client;

class ApiRunner {

    protected $endpoints;

    protected $client;
    protected $report = [];

    public function __construct($configs, $token = false) {

        if(isset($configs['endpoints'])) {
            $this->endpoints = $configs['endpoints'];
        }

        $protocol = $configs['api']['use_ssl'] ? 'https' : 'http';
        $authenticationToken = (!!$token) ? $token : $configs['api']['auth_token'];
        $this->client = new Client([
            'base_url' => $protocol . '://' . $configs['api']['base_url'],
            'defaults' => [
                'query' => $configs['api']['default']['query'],
                'headers' => [
                    'Authorization' => 'Bearer ' . $authenticationToken,
                    'Cookie' => $configs['api']['default']['cookie']
                ]
            ]
        ]);

        $this->checkAuth($configs['api']['oauth2']['verify_uri']);

    }

    /**
     * override the token from the configs
     * @param $token
     */
    public function setAuthToken($token) {
        $this->client->setDefaultOption('headers/Authorization', 'Bearer ' . $token);
    }


    private function checkAuth($verify_uri){

        echo "Using Token " . $this->client->getDefaultOption('headers/Authorization');

        $st = microtime(true);
        try{
            $response = $this->client->get($verify_uri, []);

            $user = $response->json();

            $this->reportSuccess([
                "name" => "Token Authentication",
                "path" => $verify_uri
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

//        $responseBody = $response->json();

        return $response;

    }

    public function reportSuccess($apiCall, $res, $took){
        $this->report[] = [
            'success' => true,
            'name' => $apiCall['name'],
            'time' => number_format($took, 3),
            'status_code' => $res->getStatusCode(),
            'content_type' => $res->getHeader('content-type'),
            'response_length' => strlen($res->getBody())
        ];
    }

    public function reportFail($apiCall, $e){
        $this->report[] = [
            'success' => false,
            'name' => $apiCall['name'],
            'error' => $e->getMessage()
        ];
    }

    public function report(){
        echo PHP_EOL . PHP_EOL . "====== REPORT ====== " . PHP_EOL . PHP_EOL;


        $resultRow = function($name = null, $time = null, $st = null, $r_len = null, $ct = null){
            $pad = ($name == null) ? "+" : " ";
            return sprintf("+ %s + %s + %s + %s + %s +" .  PHP_EOL,
                str_pad($name, 20, $pad, STR_PAD_RIGHT),
                str_pad($time, 15, $pad, STR_PAD_BOTH),
                str_pad($st, 10, $pad, STR_PAD_BOTH),
                str_pad($r_len, 20, $pad, STR_PAD_BOTH),
                str_pad($ct, 20, $pad, STR_PAD_RIGHT)
            );
        };

        echo $resultRow();
        echo $resultRow("Endpoint", "(Time )secs", "Status", "Content-Length", "Type");
        echo $resultRow();
        foreach($this->report as $callReport) {
            if($callReport['success']){
                echo $resultRow($callReport['name'], $callReport['time'], $callReport['status_code'], $callReport['response_length'], $callReport['content_type']);
            }else{
                echo sprintf("+ %s: ERROR: (%s) +" .  PHP_EOL, str_pad($callReport['name'], 20, " ", STR_PAD_RIGHT), $callReport['error']);
            }
        }
        echo $resultRow();
    }
}