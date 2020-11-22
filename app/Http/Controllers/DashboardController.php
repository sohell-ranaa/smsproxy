<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\SmsDetails;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function adminIndex()
    {

        $select = [
            'receiver_number',
            'msg_status',
            'msg_guid',
            'tMsgId',
            'msg_provider',
            'telecom_operator',
            'delivered_data',
            'sms_details.created_at'
        ];

         $todaysNodesSms = SmsDetails::toBase()
            ->select($select)
            ->join('dlrs','sms_details.id','dlrs.sms_id')
            ->where('sms_details.msg_client', 'nodes')
            ->whereDate('sms_details.created_at', '=', Carbon::today()->toDateString())
            ->get();

         $totalNodesSms = SmsDetails::toBase()
            ->select($select)
            ->join('dlrs','sms_details.id','dlrs.sms_id')
            ->where('sms_details.msg_client', 'nodes')
            ->get();

         $totalNodesDelivered = $totalNodesSms->where('msg_status','Delivered');
         $todaysNodesDelivered = $todaysNodesSms->where('msg_status','Delivered');

         if((int)count($todaysNodesSms) != 0){
             $toDayPercentage = round((int)count($todaysNodesDelivered)*100/(int)count($todaysNodesSms)) ;
         }else{
             $toDayPercentage = 0;
         }

        if((int)count($totalNodesSms) != 0){
            $totalPercentage = round((int)count($totalNodesDelivered)*100/(int)count($totalNodesSms)) ;
        }else{
            $totalPercentage = 0;
        }



        return view('dashboard.admin_dashboard',compact('todaysNodesSms',
            'totalNodesSms',
            'totalNodesDelivered',
            'todaysNodesDelivered',
            'toDayPercentage',
            'totalPercentage'

        ));
    }
}
