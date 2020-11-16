<?php

namespace App\Http\Controllers;


use App\Helpers\General;
use App\Mail\BeelinkReportMail;
use Illuminate\Http\Request;

class MailController extends Controller
{
    public function sendBeelinkMail()
    {

        $sendToArr = [
            'sohell.ranaa@gmail.com'
        ];

        foreach ($sendToArr as $sendTo) {
            \Mail::to($sendTo)->send(new BeelinkReportMail());
        }

    }
}
