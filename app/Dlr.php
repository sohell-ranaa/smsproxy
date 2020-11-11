<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Dlr extends Model
{
    protected $fillable = [
        'to',
        'from',
        'delivered_data',
        'msg_status',
        'sms_id'
    ];
}
