<?php
namespace App\Http\Controllers;
use App\Personal;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function create(Request $request) {
        $records = [];
        $person_list = getPersonListFromFlex($request);

        if (!empty($person_list)) {
            DB::table('personals')->truncate();

            foreach($person_list as $p) {
                $person = [
                    'hriId'=> $p->hriId,
                    'name' => $p->name,
                    'surname' => $p->surname,
                    'cardId' => $p->cardId,
                    'modifyDate' => date("Y-m-d H:i:s"),
                ];

                $records[] = $person;
            }
        
            Personal::insert($records);
        }
    }

}