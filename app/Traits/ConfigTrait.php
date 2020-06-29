<?php

namespace App\Traits;
use App\Config;

trait ConfigTrait
{
    public function getConfigsDB() {
        return Config::all();
    }

    public function updateConfigDB($name, $value, $options = null, $lastUpdate = null)
    { 
      $config = Config::updateOrCreate(['name' => $name],
         [
            'value' => $value,
            'options' => $options,
            'lastUpdate' => $lastUpdate,
         ]);
        
      return $config;
    }

    public function getConfigLastUpdateDB() {
      return Config::select('lastUpdate')->orderBy('lastUpdate', 'desc')->first();
    }

    public function getLimitRecordOnStation() {
      $config = Config::where('name', 'STATION_LIMIT_RECORDS')->first();
      if ($config != null)
         return intval($config->value);
      else return 0;
    }

    public function getStationName() {
      $config = Config::where('name', 'STATION_NAME')->first();
      if ($config != null)
         return $config->value;
      else return '';
    }

    public function getStationOnlineStatus() {
      $config = Config::where('name', 'STATION_ONLINE')->first();
      if ($config != null)
         return $config->value == 'true';
      else return false;
    }

    public function getStationActiveStatus() {
      $config = Config::where('name', 'STATION_ACTIVE')->first();
      if ($config != null)
         return $config->value == 'true';
      else return false;
    }

    public function getConfig4GUI() {
      return Config::where('name', '=', 'CHECK_ONLINE_PERIOD')
            ->orWhere('name', '=', 'REFRESH_GUI_PERIOD')
            ->orWhere('name', '=', 'SCAN_DELAY_SECOND')
            ->orWhere('name', '=', 'F14_DELAY_SECOND')
            ->get(['name','value']);
    }
}