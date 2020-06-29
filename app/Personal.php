<?php

namespace App;
use Illuminate\Database\Eloquent\Model;
use App\UsesUuid;

class Personal extends Model
{
    use UsesUuid;

    protected $table = 'personals';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $hidden = [
        'id'
    ];
    protected $fillable = [
        'hriId', 'pid', 'name', 'surname', 'cardId', 'modifyDate'
    ];

    public $timestamps = false;
}