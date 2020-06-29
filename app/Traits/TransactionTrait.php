<?php

namespace App\Traits;

use DB;
use Model;
use App\Transaction;
use App\Traits\ConfigTrait;

trait TransactionTrait
{
    use ConfigTrait;

    public function insertTransactionDB($tran)
    { 
        DB::beginTransaction();
        try {
            if ($this->isTransactionDataEqualLimitRecord()) {
                Transaction::orderBy('scanTime', 'asc')->first()->delete();
            }
  
            $tran = Transaction::create([
                'cardId' => $tran->cardId,
                'scanTime' => $tran->scanTime,
                'scanDetail' => $tran->scanDetail,
                'hriId' => $tran->hriId,
                'latitude' => $tran->latitude,
                'longtitude' => $tran->longtitude,
                'expireDate' => $tran->expireDate,
                'expireTime' => $tran->expireTime,
                'timeDiffSec' => $tran->timeDiffSec,
                'qrType' => $tran->qrType,
                'scanStatus' => $tran->scanStatus,
                'sendStatus' => false,
                'sendDate' => null
            ]);

            DB::commit();
            return $tran;            

        } catch (\Exception $ex) {
            DB::rollback();
            throw new \Exception('ERROR on Save Transaction.');
        }
    }

    private function isTransactionDataEqualLimitRecord() {
        $ilimitRecords = $this->getLimitRecordOnStation();
        $itranRecords = $this->getTransactionCount();

        return $ilimitRecords === $itranRecords;
    }

    public function updateSendRealSuccessStatus($tran_id) {
        Transaction::where('id', '=', $tran_id)->update(array('sendStatus' => true, 'sendDate' => date('Y-m-d H:i:s')));
    }
   
    public function getTransactionCount() {
        return Transaction::count();
    }

}