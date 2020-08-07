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

    public function getTransactionOfflineQueue() {
        return Transaction::where('sendStatus', '=', false)->pluck('id')->toArray();
    }
    public function getTransactionOfflineQueueData($tran_id_list) {
        $itranRecords = Transaction::whereIn('transactions.id', $tran_id_list)
            ->leftjoin('personals as ps','ps.hriId', '=', 'transactions.hriId')
            ->select(
                'ps.cardId as CardId',
                'ps.hriId as HRiId',
                'ps.pid as PID',
                DB::raw('CONCAT(ps.name, " ", ps.surname) as NameSurname'),
                DB::raw('SUBSTRING_INDEX(scanTime, " ", 1) AS Date'),
                DB::raw('SUBSTRING_INDEX(SUBSTRING_INDEX(scanTime, " ", 2), " ", -1) AS Time'),
                'scanTime as DateTime',
                DB::raw('IFNULL(timeDiffSec, -1) as TimeDiffSec'),
                'scanStatus as INOUT',
                'scanDetail as Detail',
                'latitude as Lat',
                'longtitude as Long'
            )
            ->orderBy('scanTime', 'ASC')
            ->get();

        return $itranRecords;
    }
    public function updateSendQueueSuccessStatus($tran_id_list) {
        Transaction::whereIn('id', $tran_id_list)->update(array('sendStatus' => true, 'sendDate' => date('Y-m-d H:i:s')));
    }
}