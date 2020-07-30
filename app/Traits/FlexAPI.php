<?php

namespace App\Traits;

use GuzzleHttp\Client;
use App\Traits\TransactionTrait;
use App\Traits\PersonalTrait;
use App\Traits\ConfigTrait;
use App\Traits\TransactionRealThread;

trait FlexAPI
{
    use TransactionTrait, PersonalTrait, ConfigTrait;

    public function getGuzzleHttpClient() {
        $domain = $this->getFlexDomain();

        // ERROR: curl_setopt_array(): cannot represent a stream of type Output as a STDIO FILE*
        // fix: https://github.com/guzzle/guzzle/issues/1413
        // set 'debug' => fopen('php://stderr', 'w') in request option parameter
        return new \GuzzleHttp\Client(['base_uri' => $domain]);
    }
    private function sendGuzzleGetRequest($api, $options = []) {
        // TEST GET
        //$request = new \GuzzleHttp\Psr7\Request('GET', "stationService/v1/onlineStatus/$stationId/$nonce");
        //$response = $client->send($request, ['debug' => fopen('php://stderr', 'w')]);

        $stationId = $this->getStationID();
        $nonce = $this->getNonce();
        $options[] = ['debug' => fopen('php://stderr', 'w')];

        $client = $this->getGuzzleHttpClient();
        $response = $client->get("stationService/v1/$api/$stationId/$nonce", $options);

        $resp_obj = json_decode($response->getBody()->getContents());
        //return json_encode($resp_obj->data->stationId); // for debug

        if ($resp_obj->status == 'OK') {
            $data = $resp_obj->data;
            if ($this->isRightResponseFromFlex($stationId, $nonce, $data->stationId, $data->nonce)) {
                return $data->data;
            }
            else return null;

        } else {
            throw new \Exception("API: <$api> ERROR=>{$resp_obj->exception}");
        }
    }

    public function getConfigLastUpdateAPI() {
        $api = 'getConfigLastUpdate';
        $data = $this->sendGuzzleGetRequest($api);

        $result = null;
        if ($data != null) {
            $result = $data->lastUpdate;
        }

        return $result;
    }
    public function getConfigAPI() {
        $api = 'getConfig';
        $data = $this->sendGuzzleGetRequest($api);

        $result = [];
        if ($data != null) {
            $result = $data;
        }

        return $result;
    }

    public function getPersonalLastUpdateAPI() {
        $api = 'getPersonalLastUpdate';
        $data = $this->sendGuzzleGetRequest($api);

        $result = null;
        if ($data != null) {
            $result = $data->lastUpdate;
        }

        return $result;
    }
    public function getPersonalAPI() {
        $api = 'getPersonal';
        $data = $this->sendGuzzleGetRequest($api);

        $result = [];
        if ($data != null) {
            $result = $data;
        }

        return $result;
    }

    public function checkOnlineStatusAPI() {
        $stationId = $this->getStationID();
        $nonce = $this->getNonce();
        
        $data = array(
            "geolocation" => $this->getGeolocation(),
            "personalNo" => $this->getPersonalCount(),
            "timestampNo" => $this->getTransactionCount()
        );

        $client = $this->getGuzzleHttpClient();

        try {
        // Send an request.
            $response = $client->post(
                "stationService/v1/onlineStatus/$stationId/$nonce", 
                [
                    'json' => $data, 
                    'debug' => fopen('php://stderr', 'w'), 
                    'timeout' => 1, 
                    'headers' => [
                        'User-Agent' => 'PHP/StationQR v.'.env('APP_VERSION').' ['.config('station.id').']',
                    ]
                ]
            );

            $resp_obj = json_decode($response->getBody()->getContents());

            if ($resp_obj->status == 'OK') {
                $data = $resp_obj->data;
                if ($this->isRightResponseFromFlex($stationId, $nonce, $data->stationId, $data->nonce)) {
                    return $data->data;
                }
                else return null;

            } else {
                //throw new \Exception("API: <$api> ERROR=>{$resp_obj->exception}");
                return null;
            }
        } catch (\Exception $e) {
            return null;
        }
    }
    private function getGeolocation() {
        try {
            $client = new \GuzzleHttp\Client();
            $options[] = ['debug' => fopen('php://stderr', 'w')];
            $url = 'http://ip-api.com/json/?fields=status,message,continent,continentCode,country,countryCode,region,regionName,city,district,zip,lat,lon,timezone,offset,currency,isp,org,as,asname,reverse,mobile,proxy,hosting,query';
            $response = $client->get($url, $options);

            $resp_obj = $response->getBody()->getContents();
            return base64_encode($resp_obj);
        } catch (\Exception $e) {
            return base64_encode(json_encode(array("status" => 'fail', "message" => $e->getMessage(), "exception" => $e)));
        }
    }

    public function getStationID() {
        return base64_encode(config('station.id'));
    }
    public function getNonce() {
        return uniqid(mt_rand(), true);
    }
    private function getFlexDomain() {
        return config('station.mode') == 'qas' ? config('station.qas_server') : config('station.prd_server');
    }
    private function isRightResponseFromFlex($qr_station, $qr_nonce, $flex_station, $flex_nonce) {
        $qr_station = base64_decode($qr_station);
        return $qr_station == $flex_station && $qr_nonce == $flex_nonce;
    }

    // =========================== Guzzle for Async call ===========================
    public function sendTransactionRealAPI($tran, $person, $tran_id) {
        $th = new TransactionRealThread($tran, $person, $tran_id);
        $th->start();
    }

    public function sendTransactionQueueAPI() {
        $stationId = $this->getStationID();
        $nonce = $this->getNonce();
        
        $data = $this->genTransactionForFlexQueue($tran_id_list);

        $client = $this->getGuzzleHttpClient();

        // Send an asynchronous request.
        $promise = $client->postAsync(
            "stationService/v1/setTimeStamp/$stationId/$nonce", 
            ['json' => $data, 'debug' => fopen('php://stderr', 'w'), 'timeout' => 1]
        )->then(
            function ($res) {
                return json_decode($res->getBody()->getContents());  
            },
            function (RequestException $e) {
                $this->updateConfigDB("STATION_ONLINE", "false", null, null);
            }
        );
        $flex_resp = $promise->wait();

        if ($flex_resp->status == 'OK') {
            $this->updateSendQueueSuccessStatus($tran_id_list);
        }
    }
    private function genTransactionForFlexQueue(&$tran_id_list) {
        return array();

        $tmp = explode(" ", $tran->scanTime);
        return array(
            "Real" => false,
            "Data" => array(
                array(
                "CardId" => $tran->cardId,
                "HRiId" => $tran->hriId,
                "PID" => $person == null ? null : $person->pid,
                "NameSurname" => $person == null ? null : $person->name.' '.$person->surname,
                "Date" => $tmp[0],
                "Time" => $tmp[1],
                "DateTime" => $tran->scanTime,
                "TimeDiffSec" => $tran->timeDiffSec,
                "INOUT" => $tran->scanStatus,
                "Detail" => $tran->scanDetail,
                "Lat" => $tran->latitude,
                "Long" => $tran->longtitude,
                )
            )
        );
    }

}