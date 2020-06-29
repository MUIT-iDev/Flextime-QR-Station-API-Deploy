<?php

namespace App\Traits;

use DB;
use Model;
use App\Personal;

trait PersonalTrait
{
    public function insertPersonalDB($hriId, $name, $surname, $cardId, $modifyDate)
    { 
        return Personal::create([
            'hriId' => $hriId,
            'name' => $name,
            'surname' => $surname,
            'cardId' => $cardId,
            'modifyDate' => $modifyDate,
        ]);
    }

    public function clearAllPersonalDB() {
        DB::table('personals')->truncate();
    }

    public function getPerson(&$tran, &$error) {
        $person = Personal::where('hriId', '=', $tran->hriId)->first();
        if ($person == null) {
            if ($error == null) $error = 'Don\'t have this person data on QR Station.';
            $tran->scanStatus = config('station.qrstatus_unreg');
            $tran->cardId = 'no person';
        } else {        
            $tran->cardId = $person->cardId;
        }

        return $person;
    }

    public function getPersonalLastUpdateDB() {
        return Personal::select('modifyDate')->orderBy('modifyDate', 'desc')->first();
    }

    public function updatePersonalDB($obj)
    { 
      $person = Personal::updateOrCreate(['hriId' => $obj->hriId],
         [
            'pid' => $obj->pid,
            'name' => $obj->name,
            'surname' => $obj->surname,
            'cardId' => $obj->cardId,
            'modifyDate' => $obj->lastUpdate,
         ]);
        
      return $person;
    }

    public function getPersonalCount() {
        return Personal::count();
    }
}