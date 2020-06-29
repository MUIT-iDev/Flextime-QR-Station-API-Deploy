<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    $result = 'Flextime QR Scanner Station';
    $result .= '<br/>API v.'.env('APP_VERSION');
    $result .= '<br/>Power by ';
    $result .= $router->app->version();
    
    return $result;
});

$router->group(['prefix'=>'api/v1'], function() use($router) {
    $router->get('/configs', 'ConfigController@index');
    #$router->post('/configs', 'ConfigController@create');
    #$router->get('/configs/{name}', 'ConfigController@show');
    #$router->put('/configs/{name}', 'ConfigController@update');
    #$router->delete('/configs/{name}', 'ConfigController@destroy');
    $router->get('/configs/setting-gui', 'ConfigController@guiSetting');

    $router->get('/schedule_trigger/{mode}', 'ScheduleController@run');
    $router->get('/checkOnline', 'ScheduleController@checkConnectionOnline');

    $router->get('/transactions', 'TransactionController@home');
    $router->post('/transactions', 'TransactionController@create');

    $router->get('/testx', function() use ($router) {
        $file_path = realpath(__DIR__.'/../../.secret/config.json');
        $json = json_decode(file_get_contents($file_path), true);
        
        return $json["station"]["name"].' '.$json["station"]["id"];
    });
});