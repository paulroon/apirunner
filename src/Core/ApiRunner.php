<?php

namespace HappyCode\Core;

use GuzzleHttp\Client;

class ApiRunner {

    protected $endpoints;

    protected $client;

    public function __construct($configs) {

        if(isset($configs['endpoints'])){
            $this->endpoints = $configs['endpoints'];
        }

        $protocol = $configs['api']['use_ssl'] ? 'https' : 'http';
        $this->client = new Client([
            'base_url' => $protocol . '://' . $configs['api']['base_url'],
            'defaults' => [
                'headers' => [
                    'Authorization' => 'Bearer ' . $configs['api']['auth_token'],
                    'Cookie' => 'XDEBUG_SESSION=PHPSTORM'
                ]
            ]
        ]);

    }

    public function run(){
        $report = [];
        /**
         * Guzzle is very capable of sending requests in paralel - however this is a performance tool as well as a
         * for testing purposes
         * I don't want the requests to interfere with each other (or do I)?
         * so have chosen to do a (slower)? sequential set of tests
         */
        foreach($this->endpoints as $apicall){

            echo PHP_EOL . "running " . $apicall['name'] . "...";

            try{
                $start = microtime(true);
                $res = $this->client->get($apicall['path']);
                $took = microtime(true) - $start;
                $report[] = [
                    'success' => true,
                    'name' => $apicall['name'],
                    'time' => number_format($took, 3),
                    'status_code' => $res->getStatusCode(),
                    'content-type' => $res->getHeader('content-type'),
                    'response_length' => strlen($res->getBody())
                ];
            }catch(\Exception $e){
                echo '['.get_class($e).'] - ' . $e->getMessage() . PHP_EOL;
                $report[] = [
                    'success' => false,
                    'name' => $apicall['name'],
                    'error' => $e->getMessage()
                ];
            }

        }

        return $report;
    }
} 