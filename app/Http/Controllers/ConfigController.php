<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Config;
use App\Traits\ConfigTrait;

class ConfigController extends Controller
{
   use ConfigTrait;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
   public function __construct()
   {
      //
   }
    
   public function index()
   {
      $configs = $this->getConfigsDB();
      return response()->json($configs);
   }

   public function guiSetting()
   {
      $error = null;
      try {
         $configs = $this->getConfig4GUI();
         $result = array(
            'status' => true,
            'message' => 'OK',
            'context' => $configs,
            'error' => null,
         );

      } catch (\Exception $e) {
         $result = array(
            'status' => false,
            'message' => null,
            'context' => $e->getMessage(),
            'error' => $this->getErrorCode($e->getMessage()),
         );
      }

      return response()->json($result);
   }

}