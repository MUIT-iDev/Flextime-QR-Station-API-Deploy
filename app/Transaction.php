<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\UsesUuid;

class Transaction extends Model
{
    use UsesUuid;

    protected $table = 'transactions';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $hidden = [
        'id'
    ];
    protected $fillable = [
        'cardId', 'scanTime','scanDetail', 'hriId', 'latitude', 'longtitude', 'expireDate', 'expireTime', 'timeDiffSec', 'qrType', 'scanStatus', 'sendStatus', 'sendDate'
    ];

    public $timestamps = false;

}