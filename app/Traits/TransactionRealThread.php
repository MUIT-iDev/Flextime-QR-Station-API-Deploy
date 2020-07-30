<?php

namespace App\Traits;

use App\Traits\FlexAPI;
use App\Traits\ConfigTrait;
use App\Traits\TransactionTrait;

class TransactionRealThread extends Thread {

    use FlexAPI, ConfigTrait, TransactionTrait;

    public function __construct($tran, $person, $tran_id) {
        $this->tran = $tran;
        $this->person = $person;
        $this->tran_id = $tran_id;
    }

    public function run() {
        $stationId = $this->getStationID();
        $nonce = $this->getNonce();
        
        $data = $this->genTransactionForFlexRealtime($this->tran, $this->person);

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
            $this->updateSendRealSuccessStatus($this->tran_id);
        }
    }

    private function genTransactionForFlexRealtime($tran, $person) {
        $tmp = explode(" ", $tran->scanTime);
        return array(
            "Real" => true,
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