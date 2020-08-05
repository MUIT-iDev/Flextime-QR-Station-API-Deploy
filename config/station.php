<?php
/**
 * @category    Station-Info
 * @package     Station
 * @author      RuzeriE <kittisak.tos@mahidol.edu>
 */

$file_path = realpath(__DIR__.'/../../.secret/config.json');
$json = json_decode(file_get_contents($file_path), true);


return [

    /*
    |--------------------------------------------------------------------------
    | Station ID
    |--------------------------------------------------------------------------
    |
    | This value gives, QR Station ID
    |
    */
    'id' => $json["station"]["id"],
    'mode' => $json["server"]["mode"],
    'name' => $json["station"]["name"],
    'qas_server' => $json["server"]["qas_site"],
    'prd_server' => $json["server"]["prd_site"],
    'qrstatus_unreg' => 'unreg',
    'qrstatus_timeout' => 'timeout',
];