<?php

namespace App\Http\Controllers;

use App\Helpers\General;
use App\Helpers\SmsProviders;
use App\SmsDetails;
use App\UnsentSmsDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use mysql_xdevapi\Exception;
use Async;
use App\Dlr;

date_default_timezone_set('Asia/Dhaka');


class BulkSmsController extends Controller
{

    //    Used in routes
    public function nodesSmsRefactored(Request $request)
    {
        $check = General::checkValidation($request);
        if (!is_null($check)) {
            return $check;
        }

        try {
            $mobile = $request->number;
            $tMsgId = $request->tMsgId;

            $smsText = (int)filter_var($request->smsText, FILTER_SANITIZE_NUMBER_INT);
            if (empty($smsText)) {
                return 'No OTP code found';
            }

            $smsText = $smsText . ' আপনার কোড - EKSHOP';
            //  $smsText = 'আপনার কোড '.$smsText . ' - একশপ';
            //  $smsText = 'কোড '.$smsText . ' - একশপ';

            $mobile = General::formatMobileNumber($mobile);
            if (strlen((string)$mobile) != 11) {
                return 'Invalid number length';
            }

            $data = [
                'mobile' => $mobile,
                'smsText' => $smsText,
                'client' => 'Nodes',
                'tMsgId' => $tMsgId
            ];

            return $this->sendSms($data);

        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return 0;
    }

    public function ekShopSms(Request $request)
    {
        $check = General::checkValidation($request, true);
        if (!is_null($check)) {
            return $check;
        }

        try {
            $mobile = $request->number;
            $smsText = $request->smsText;

            $mobile = General::formatMobileNumber($mobile);
            if (strlen((string)$mobile) != 11) {
                return 'Invalid number length';
            }

            $dataArr = [
                'mobile' => $mobile,
                'smsText' => $smsText,
                'client' => 'elShop-core',
            ];

            return $this->sendSms($dataArr);

        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return 0;
    }

    //    Used in routes
    public function dlrReport(Request $request)
    {

        $DELIVERED_DATA = Carbon::parse($request->DELIVERED_DATA)->addMinutes(30);
        $MSG_STATUS = $request->MSG_STATUS;
        $CLIENT_GUID = $request->CLIENT_GUID;

        $data = General::dlrValidation($CLIENT_GUID);
        if(!is_object($data)){
            return $data;
        }

        Dlr::where('sms_id', $data->id)
            ->update([
                'delivered_data' => $DELIVERED_DATA,
                'msg_status' => $MSG_STATUS
            ]);

        SmsDetails::where('id', $data->id)
            ->update([
                'is_dlr_received' => '1',
                'msg_guid' => $CLIENT_GUID
            ]);

        $passData = [
            'tMsgId' => $data['tMsgId'],
            'status' => $MSG_STATUS,
            'delivered_time' => $DELIVERED_DATA
        ];

        General::sendDlrToBeelink($passData);
        return 'DLR saved';

    }

    public function dlrReportFromClient(Request $request)
    {
        return ($request);
    }

    public function dlrReportAll($number = null)
    {
        if (!is_null($number)) {
            return Dlr::where('to', $number)->orderBy('id', 'desc')->get();
        }
        return Dlr::orderBy('id', 'desc')->get();


    }

    public function sendSms($dataArr)
    {
        $status = SmsProviders::vfSms($dataArr);
        $data = explode("&", $status);

        if (strpos($status, 'errorcode=0')) {
            $guid = ltrim($data[0], 'guid=');
            $t_arr = [
                'guid' => $guid,
                'provider' => 'ValueFirst'
            ];
            $var = array_merge($dataArr, $t_arr);
            return $this->storeVfSms($var);
        } else {
            $guid = ltrim($data[0], 'guid=');
            $error_code = ltrim($data[1], 'errorcode=');

            $t_arr = [
                'guid' => $guid,
                'provider' => 'ValueFirst',
                'error_code' => $error_code
            ];
            $var = array_merge($dataArr, $t_arr);
            return $this->storeVfUnsentSms($var);
        }
    }

    public function storeVfSms($var)
    {
        $data = General::setVfCreateValues($var);
        $result = SmsDetails::create($data)->id;
        $dlr['to'] = '88' . $var['mobile'];
        $dlr['sms_id'] = $result;
        Dlr::create($dlr)->id;

        $storeStatus = [
            'code' => 200,
            'msg' => 'Successful',
            'data' => [
                'guid' => $data['msg_guid']
            ],
            'tMsgId' => $data['tMsgId']
        ];
        return $storeStatus;
    }

    public function storeVfUnsentSms($var)
    {
        $data = General::setVfCreateValues($var);
        UnsentSmsDetail::create($data)->id;

        $storeStatus = [
            'code' => 200,
            'msg' => 'Unsuccessful',
            'data' => [
                'guid' => $data['msg_guid'],
                'error_code' => $data['error_code']
            ],
            'tMsgId' => $data['tMsgId'],
        ];
        return $storeStatus;

    }

}
