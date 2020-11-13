<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UnsentSmsDetail extends Model
{
    protected $fillable = [
        'receiver_number',
        'msg_guid',
        'msg_body',
        'msg_client',
        'msg_provider',
        'telecom_operator',
        'error_code',
        'tMsgId'
    ];
}
