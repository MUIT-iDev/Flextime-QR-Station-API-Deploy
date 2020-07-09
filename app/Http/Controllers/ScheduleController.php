<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use App\Traits\FlexAPI;
use App\Traits\ConfigTrait;
use App\Traits\PersonalTrait;
use App\Traits\TransactionTrait;

class ScheduleController extends Controller
{
    use FlexAPI, ConfigTrait, PersonalTrait, TransactionTrait;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function checkConnectionOnline() {
        $error = null;
        try {
            $api_version = env('APP_VERSION');
            $online_status = false;
            $station_active = false;
            $online_status = $this->getStationOnlineStatus();
            $station_active = $this->getStationActiveStatus();
            $person_on_station_count = $this->getPersonalCount();
            $station_name = $this->getStationName();
            $transaction_on_station_count = $this->getTransactionCount();
            $limit_record = $this->getLimitRecordOnStation();

            $result = array(
                'status' => true,
                'message' => 'OK',
                'context' => array(
                    'apiVersion' => $api_version,
                    'stationName' => $station_name,
                    'onlineStatus' => $online_status,
                    'stationActive' => $station_active,
                    'peopleOnStation' => $person_on_station_count,
                    'transactionRecord' => $transaction_on_station_count,
                    'limitRecord' => $limit_record
                ),
                'error' => null,
             );

            //update last sycn
            $c = json_decode('{
                "key": "CHECK_ONLINE_PERIOD_lastProcess",
                "value": '.time().'
            }');
            $this->updateConfigDB(
                $c->key, 
                $c->value
            );

        } catch (\Exception $e) {
            $result = array(
                'status' => false,
                'message' => null,
                'context' => $e->getMessage(),
                'error' => $e->getMessage(),
            );
        }

        return response()->json($result);
    }

    public function run($mode) {
        $result = [];
        $result[] = 'API version: '.env('APP_VERSION').'<br/>';
        $result[] = 'Start On: '.date('Y-m-d H:i:s.u').'<br/>';
        $configs = $this->getConfigsDB();
        $config_sync = new \DateTime();
        $personal_sync = new \DateTime();
        $config_period = 0;
        $personal_period = 0;
        $station_active = 'true';
        $flex_sync = new \DateTime();
        $flex_period = 0;
        foreach($configs as $c) {
            switch($c->name) {
                case "SYNC_CONFIG_PERIOD": 
                    $config_period = $c->value;
                    break;
                case "SYNC_CONFIG_PERIOD_lastProcess": 
                    $config_sync->setTimestamp($c->value);
                    break;
                case "SYNC_PERSONALS_PERIOD": 
                    $personal_period = $c->value;
                    break;
                case "SYNC_PERSONALS_PERIOD_lastProcess": 
                    $personal_sync->setTimestamp($c->value);
                    break;

                case "STATION_ACTIVE": 
                    $station_active = $c->value;
                    break;
                case "FLEX_CHECKHAND_PERIOD": 
                    $flex_period = $c->value;
                    break;
                case "FLEX_CHECKHAND_PERIOD_lastProcess": 
                    $flex_sync->setTimestamp($c->value);
                    break;
                default: 
                    break;
            }
        }

        $now = new \DateTime();
        $is_admin = $mode == 'admin' ? true : false;
        $is_sync_config = $this->timeToSync($now, $config_sync, intval($config_period));
        $is_sync_personal = $this->timeToSync($now, $personal_sync, intval($personal_period));
        $is_checkhand_flex = $this->timeToSync($now, $flex_sync, intval($flex_period));

        if ($is_checkhand_flex) {
            $result[] = $this->checkhandWithFlexServer();
        }

        if ($is_admin || (!$is_admin && $is_sync_config)) {
            $result[] = $this->setConfigFn();
        }

        if (($is_admin || (!$is_admin && $is_sync_personal)) && ($station_active === 'true')) {
            $result[] = '<br/>';
            $result[] = $this->setPersonalFn();
        }
      
        $result[] = '<br/>End Process: '.date('Y-m-d H:i:s.u');
        return implode(' ', $result);
    }

    private function checkhandWithFlexServer() {
        $result = [];
        try {
            $configs[] = json_decode('{
                "key": "FLEX_CHECKHAND_PERIOD_lastProcess",
                "value": '.time().'
            }');

            $resp = $this->checkOnlineStatusAPI();
            if ($resp != null) {
                $configs[] = json_decode('{
                    "key": "STATION_ACTIVE",
                    "value": '.$resp->active.'
                }');
                $configs[] = json_decode('{
                    "key": "STATION_ONLINE",
                    "value": "true"
                }');
            } else {
                $configs[] = json_decode('{
                    "key": "STATION_ONLINE",
                    "value": "false"
                }');
            }
                
            $result[] = 'Update CheckHandFlex Config <';
            DB::beginTransaction();
            foreach($configs as $c) {
                $this->updateConfigDB(
                    isset($c->key) ? $c->key : null, 
                    isset($c->value) ? $c->value : null, 
                    isset($c->options) ? $c->options : null,
                    isset($c->lastUpdate) ? $c->lastUpdate : null
                );
                $result[] = $c->key.', ';
            }
            DB::commit();
            $result[] = '> Success';
                
        } catch (\PDOException $e) {
            // Woopsy
            DB::rollBack();
            $result[] = "<br />Error: $e";
        }
            
        return implode(' ', $result);
    }

    private function setConfigFn() {
        $result = [];
        try {
            $last_update_Flex = $this->getConfigLastUpdateAPI();
            $last_update_DB = $this->getConfigLastUpdateDB();
            $set_new_config = $last_update_Flex != $last_update_DB;

            DB::beginTransaction();
            if ($set_new_config) {
                $configs = $this->getConfigAPI();
                $configs[] = json_decode('{
                    "key": "SYNC_CONFIG_PERIOD_lastProcess",
                    "value": '.time().'
                }');
            
                $result[] = 'Update Config <';
                foreach($configs as $c) {
                    $this->updateConfigDB(
                        isset($c->key) ? $c->key : null, 
                        isset($c->value) ? $c->value : null, 
                        isset($c->options) ? $c->options : null,
                        isset($c->lastUpdate) ? $c->lastUpdate : null
                    );
                    $result[] = $c->key.', ';
                }
            } else $result[] = '<br />NO update Config value from Flex.';

            DB::commit();
            $result[] = '> Success';
        } catch (\Exception $e) {
            // Woopsy
            DB::rollBack();
            $result[] = "<br />Error: $e";
        }
        
        return implode(' ', $result);
    }

    private function setPersonalFn() {
        $result = [];
        try {
            $last_update_Flex = $this->getPersonalLastUpdateAPI();
            $last_update_DB = $this->getPersonalLastUpdateDB();
            $set_person = $last_update_Flex != $last_update_DB;

            DB::beginTransaction();
            if ($set_person) {
                $persons = $this->getPersonalAPI();

                $result[] = 'Insert Personal Card <';
                $this->clearAllPersonalDB();
                foreach($persons as $p) {
                    $this->updatePersonalDB($p);
                    $result[] = $p->cardId.', ';                  
                }
                       
                //update last sycn
                $c = json_decode('{
                    "key": "SYNC_PERSONALS_PERIOD_lastProcess",
                    "value": '.time().'
                }');
                $this->updateConfigDB(
                    $c->key, 
                    $c->value
                );
            } else $result[] = '<br />NO update Personal from Flex.';

            DB::commit();
            $result[] = '> Success';
            
        } catch (\PDOException $e) {
            // Woopsy
            DB::rollBack();
            $result[] = "<br />Error: $e";
        }
        
        return implode(' ', $result);
    }

    private function timeToSync($now, $time, $period) {
        $diff_min = $this->getDiffMin($now, $time);
        return $period <= $diff_min;
    }
    private function getDiffMin($now, $time) {
        $diff_config = $now->diff($time);
        return ($diff_config->days * 24 * 60) + ($diff_config->h * 60) + $diff_config->i;
    }
}