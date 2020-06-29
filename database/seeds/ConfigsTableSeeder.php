<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConfigsTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('configs')->insert([
            [
                'name' => 'SCAN_DELAY_SECOND', 
                'value' => '10',
                'options' => null,
                'lastUpdate' => '2020-06-01 00:00:00'
            ], [
                'name' => 'F14_DELAY_SECOND', 
                'value' => '10',
                'options' => null,
                'lastUpdate' => '2020-06-01 00:00:00'
            ], [
                'name' => 'SYNC_PERSONALS_PERIOD', 
                'value' => '0',
                'options' => null,
                'lastUpdate' => '2020-06-01 00:00:00'
            ], [
                'name' => 'SYNC_PERSONALS_PERIOD_lastProcess', 
                'value' => time(),
                'options' => null,
                'lastUpdate' => null
            ], [
                'name' => 'STATION_LIMIT_RECORDS', 
                'value' => '0',
                'options' => null,
                'lastUpdate' => '2020-06-01 00:00:00'
            ], [
                'name' => 'SYNC_CONFIG_PERIOD',
                'value' => '0',
                'options' => null,
                'lastUpdate' => '2020-06-01 00:00:00'
            ], [
                'name' => 'SYNC_CONFIG_PERIOD_lastProcess',
                'value' => '0',
                'options' => null,
                'lastUpdate' => null
            ], [
                'name' => 'CHECK_ONLINE_PERIOD',
                'value' => '5',
                'options' => null,
                'lastUpdate' => '2020-06-01 00:00:00'
            ], [
                'name' => 'CHECK_ONLINE_PERIOD_lastProcess',
                'value' => time(),
                'options' => null,
                'lastUpdate' => null
            ], [
                'name' => 'REFRESH_GUI_PERIOD',
                'value' => '720',
                'options' => null,
                'lastUpdate' => '2020-06-01 00:00:00'
            ], [
                'name' => 'STATION_NAME',
                'value' => 'Station-Name',
                'options' => null,
                'lastUpdate' => '2020-06-01 00:00:00'
            ], [
                'name' => 'STATION_ACTIVE',
                'value' => 'true',
                'options' => null,
                'lastUpdate' => '2020-06-01 00:00:00'
            ], [
                'name' => 'STATION_ONLINE',
                'value' => 'true',
                'options' => null,
                'lastUpdate' => '2020-06-01 00:00:00'
            ], [
                'name' => 'FLEX_CHECKHAND_PERIOD',
                'value' => '60',
                'options' => null,
                'lastUpdate' => '2020-06-01 00:00:00'
            ], [
                'name' => 'FLEX_CHECKHAND_PERIOD_lastProcess',
                'value' => time(),
                'options' => null,
                'lastUpdate' => null
            ]
        ]);
    }

}