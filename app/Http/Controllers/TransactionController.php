<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Transaction;
use App\Personal;
use App\Traits\FlexAPI;
use App\Traits\PersonalTrait;
use App\Traits\TransactionTrait;

class TransactionController extends Controller
{
   use FlexAPI, PersonalTrait, TransactionTrait;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
   public function __construct()
   {
      //
   }

   public function home()
   {
      return response('Transaction v.'.env('APP_VERSION'));
   }

   public function create(Request $request)
   {
      /* step
      1. แปลง qr return {Transaction} object
        1.1 แบบบรรทัดเดียวใช้ algo ตัด
        1.2 แบบแยกบรรทัด ใช้ \n ตัด
        1.3 ตัดไม่ได้ แจ้งกลับว่า qr ผิด format
      2. map hriId -> card, name, surname + server time
        2.1 map ไม่ได้ แจ้งกลับว่า ไม่มีข้อมูลคนใน station
        2.2 cal server time with qr life time if over time แจ้งกลับว่า qr หมดอายุ
      3. ส่ง + save
        3.1 ส่งผลกลับ [ชื่อ สกุล เวลา]
        3.2 call async บันทึก {Transaction} ลง DB station
      4. call async ส่งข้อมูลให้ flex
        4.1 get list on transactions where sendStatus == false
        4.2 send to flex
          4.2.1 send success -> timestamp to sendDate, sendStatus = true -> save update
          4.2.2 send error -> no action waiting for new scan
      */
      
      $error = null;
      try {
         if (!$request->qr) 
            throw new \Exception('Don\'t have QR data.');

         if (!$request->status) 
            throw new \Exception('Don\'t have QR scaning status.');

         $tran = $this->setScanTime($request->status);
         $this->cutQR($tran, $error, $request->qr);
         $person = $this->getPerson($tran, $error);
         if ($error == null) $this->isQRExpire($tran, $error);
         $rtran = $this->insertTransactionDB($tran);
         $this->sendTransactionRealAPI($tran, $person, $rtran->id);

         if ($error == null) {
            $scan_time = \DateTime::createFromFormat('Y-m-d H:i:s', $tran->scanTime);
            $result = array(
               'status' => true,
               'message' => 'save success',
               'context' => array(
                  'cardId' => $tran->cardId,
                  'name' => $person->name,
                  'surname' => $person->surname,
                  'scanTime' => $tran->scanTime,
                  'regisDate' => $scan_time->format('d M Y'),
                  'regisTime' => $scan_time->format('H:i:s'),
                  'qr' => $request->qr,
               ),
               'error' => $error,
            );
         } else {

            $result = array(
               'status' => false,
               'message' => null,
               'context' => $error,
               'error' => $this->getErrorCode($error),
               'qr' => $request->qr,
            );
         }
      } catch (\Exception $e) {
         $result = array(
            'status' => false,
            'message' => null,
            'context' => $e->getMessage(),
            'error' => $this->getErrorCode($e->getMessage()),
            'qr' => $request->qr,
         );
      }

      return response()->json($result);
   }

   private function setScanTime($scanStatus) {
      $tran = new Transaction();
      $tran->scanTime = date('Y-m-d H:i:s');
      $tran->scanStatus = $scanStatus;

      return $tran;
   }
   private function cutQR(&$tran, &$error, $qr) {
      $qr = base64_decode($qr);
      $tran->scanDetail = $qr;

      if (strlen($qr) < 50) {
         $error = 'QR WRONG!!! format.';
         $tran->scanStatus = config('station.qrstatus_unreg');

      } else {
         if (substr_count($qr, '\000026') > 0) {
            $qr = str_replace('\000026', "", $qr);
         }
   
         /*
         if (stripos($qr, "Enter")) {
            $tmp = explode("Enter", $qr);
            //\000026aaaaaaaaaaaaaa0003Enter13.793987Enter100.321535Enter14:24:55Enter19/05/2020EnterUNI_MQR
            //HRi_ID, Latitude, Longitude, Expire_Time, Expire_Date, Code
            if (count($tmp) == 6) {
               $tran->hriId = $tmp[0];
               $tran->latitude = $tmp[1];
               $tran->longtitude = $tmp[2];
               $tran->expireTime = $tmp[3];
               $tran->expireDate = $tmp[4];
               $tran->qrType = $tmp[5];
            }
         } else {
            */

            if (stripos($qr, "Enter"))
               $qr = str_replace("Enter", "", $qr);

            //2e9nt75yieo0bneyok13.794355100.32129211:50:2414/07/2020UNI_MQR
            //HRi_ID, Latitude, Longitude, Expire_Time, Expire_Date, Code

            // https://stackoverflow.com/questions/27650892/php-find-position-and-value-of-last-digit-in-string
            if(preg_match_all('/\d+/', $qr, $numbers)){
               $lastFullNum =      end($numbers[0]);               //eg. "2020"
               $lastDigit =        substr($lastFullNum, -1);       //eg. "0"
               $lastDigitPos =     strrpos($qr, $lastDigit, 0);    //eg. "54"
            }

            $qrTypePos = $lastDigitPos - strlen($qr) + 1;
            $tran->qrType = substr($qr, $qrTypePos);
            $dateLength = 10;
            $tran->expireDate = substr($qr, $qrTypePos - $dateLength, $dateLength);
            $timeLength = 8;
            $tran->expireTime = substr($qr, $qrTypePos - $dateLength - $timeLength, $timeLength);
            $hriIdLength = 18;
            $tran->hriId = substr($qr, 0, $hriIdLength);
            $latLongStr = substr($qr, $hriIdLength, strlen($qr) + $qrTypePos - $dateLength - $timeLength - $hriIdLength);
            $lastPointPos = strpos($latLongStr, '.');
            $tran->latitude = substr($latLongStr, 0, $lastPointPos + 7);
            $tran->longtitude = substr($latLongStr, $lastPointPos + 7);
         //}
      }
   }
   private function isQRExpire(&$tran, &$error) {
      $expire_time = \DateTime::createFromFormat('d/m/Y H:i:s', $tran->expireDate.' '.$tran->expireTime);
      $scan_time = \DateTime::createFromFormat('Y-m-d H:i:s', $tran->scanTime);
      $tran->timeDiffSec = $expire_time->getTimestamp() - $scan_time->getTimestamp();

      if ($scan_time > $expire_time) {
         $error = 'QR Expire!!!';
         $tran->scanStatus = config('station.qrstatus_timeout');
      }
   }

   private function getErrorCode($message) {
      switch($message) {
         case "Don't have QR data.":
            return '01';
         case "Don't have QR scaning status.":
            return '02';
         case "QR WRONG!!! format.":
            return '03';
         case "Don't have this person data on QR Station.":
            return '04';
         case "QR Expire!!!":
            return '05';

         default:
            return '00';
      }
   }

}