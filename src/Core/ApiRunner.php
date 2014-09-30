<?php

namespace HappyCode\Core;

use GuzzleHttp\Client;

class ApiRunner {

    protected $endpoints;

    protected $client;
    protected $report;

    public function __construct($configs) {

        if(isset($configs['endpoints'])) {
            $this->endpoints = $configs['endpoints'];
        }
        
        $protocol = $configs['api']['use_ssl'] ? 'https' : 'http';
        $this->client = new Client([
            'base_url' => $protocol . '://' . $configs['api']['base_url'],
            'defaults' => [
                'query' => $configs['api']['default']['query'],
                'headers' => [
                    'Authorization' => 'Bearer ' . $configs['api']['auth_token'],
                    'Cookie' => $configs['api']['default']['cookie']
                ]
            ]
        ]);

    }

    public function run(){

        $this->report = [];
        /**
         * Guzzle is very capable of sending requests in parallel - however this is a performance tool as well as a
         * for testing purposes
         * I don't want the requests to interfere with each other (or do I)?
         * so have chosen to do a (slower)? sequential set of tests
         */
        foreach($this->endpoints as $apicall){

            echo PHP_EOL . "running " . $apicall['name'] . "...";

            try{
                $start = microtime(true);
                $res = $this->doRequest($apicall);
                $took = microtime(true) - $start;
                $this->report[] = [
                    'success' => true,
                    'name' => $apicall['name'],
                    'time' => number_format($took, 3),
                    'status_code' => $res->getStatusCode(),
                    'content_type' => $res->getHeader('content-type'),
                    'response_length' => strlen($res->getBody())
                ];
            }catch(\Exception $e){
                echo '['.get_class($e).'] - ' . $e->getMessage() . PHP_EOL;
                $this->report[] = [
                    'success' => false,
                    'name' => $apicall['name'],
                    'error' => $e->getMessage()
                ];
            }

        }

        return $this;
    }

    public function doRequest($apiCall) {

        $options = [];
        foreach(['query', 'body'] as $opt) {
            if(isset($apiCall[$opt])) {
                if($opt == "query"){
                    var_export($apiCall[$opt]);
                }
                $options[$opt] = $apiCall[$opt];
            }
        }

        switch(strtoupper($apiCall['method'])) {

            case 'POST':        $response =  $this->client->post($apiCall['path'], $options); break;
            case 'PUT':         $response =  $this->client->put($apiCall['path'], $options); break;
            case 'DELETE':      $response =  $this->client->delete($apiCall['path'], $options); break;
            case 'GET':
            default:
                                $response = $this->client->get($apiCall['path'], ['query' => $options]); break;
        }

        return $response;

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