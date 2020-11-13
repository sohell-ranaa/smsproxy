<?php

namespace App\Http\Controllers;

use App\SmsDetails;
use App\UnsentSmsDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use mysql_xdevapi\Exception;
use SoapClient;
use Async;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use App\Dlr;

date_default_timezone_set('Asia/Dhaka');


class BulkSmsController extends Controller
{

    public function nodesSms(Request $request)
    {
        //passkey for client identification
        $passkey = [
            'client 1' => 'Open1234', //For client 1
            'client 2' => 'jfbwajJGUHYFG237yr3wkjBUYG', //For Client 2
        ];

        //Check if passkey valid
        if (!in_array($request->passkey, $passkey)) {
            return 'Invalid passkey';
        }
        // Check if number & sms text is valid
        if ($request->has('number') && empty($request->number)) {
            return 'No receiver number';
        }

        if ($request->has('smsText') && empty($request->smsText)) {
            return 'Text empty';
        }

        try {

            $mobile = $request->number;

            if (!strpos($request->smsText, 'আপনার কোড')) {
                return 'Template not matched';
            }

            $smsText = (int)filter_var($request->smsText, FILTER_SANITIZE_NUMBER_INT);

            if (empty($smsText)) {
                return 'No OTP code found';
            }

            $smsText = $smsText . ' আপনার কোড - EKSHOP';

            //  $smsText = 'আপনার কোড '.$smsText . ' - একশপ';
            //  $smsText = 'কোড '.$smsText . ' - একশপ';


            $opCode = substr($mobile, 0, 5);
            if (substr($opCode, 0, 2) == 88) {
                $mobile = $request['number'] = ltrim($mobile, '88');
            } else if (substr($opCode, 0, 3) == +88) {
                $mobile = $request['number'] = ltrim($mobile, '+88');
            } else if (substr($opCode, 0, 1) != 0) {
                $mobile = $request['number'] = '0' . $mobile;
            }

            return $this->sendSms($mobile, $smsText, 'Nodes');

        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return 0;
    }

    public function nodesSmsRefactored(Request $request)
    {


        //passkey for client identification
        $passkey = [
            'client 1' => 'Open1234', //For client 1
            'client 2' => 'jfbwajJGUHYFG237yr3wkjBUYG', //For Client 2
        ];

        //Check if passkey valid
        if (!in_array($request->passkey, $passkey)) {
            return 'Invalid passkey';
        }
        // Check if number & sms text is valid
        if ($request->has('number') && empty($request->number)) {
            return 'No receiver number';
        }

        if ($request->has('smsText') && empty($request->smsText)) {
            return 'Text empty';
        }

        if ($request->has('tMsgId') && empty($request->tMsgId)) {
            return 'No tMsgId';
        }

        try {

            $mobile = $request->number;
            $tMsgId = $request->tMsgId;


            if (!strpos($request->smsText, 'আপনার কোড')) {
                return 'Template not matched';
            }

            $smsText = (int)filter_var($request->smsText, FILTER_SANITIZE_NUMBER_INT);

            if (empty($smsText)) {
                return 'No OTP code found';
            }

            $smsText = $smsText . ' আপনার কোড - EKSHOP';


            //  $smsText = 'আপনার কোড '.$smsText . ' - একশপ';
            //  $smsText = 'কোড '.$smsText . ' - একশপ';


            $opCode = substr($mobile, 0, 5);
            if (substr($opCode, 0, 2) == 88) {
                $mobile = $request['number'] = ltrim($mobile, '88');
            } else if (substr($opCode, 0, 3) == +88) {
                $mobile = $request['number'] = ltrim($mobile, '+88');
            } else if (substr($opCode, 0, 1) != 0) {
                $mobile = $request['number'] = '0' . $mobile;
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

    public function dlrReport(Request $request)
    {
        $DELIVERED_DATA = Carbon::parse($request->DELIVERED_DATA)->addMinutes(30);
        $MSG_STATUS = $request->MSG_STATUS;
        $CLIENT_GUID = $request->CLIENT_GUID;
        $data = SmsDetails::where('msg_guid', $CLIENT_GUID)->orderBy('id', 'desc')->first();

        if (is_null($data)) {
            return 'No data found';
        }

            if ($data->is_dlr_received != 0) {
                return 'Already updated';
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
            'tMsgId'=> $data['tMsgId'],
            'status'=> $MSG_STATUS,
           'delivered_time'=> $DELIVERED_DATA
        ];
        $this->sendDlrToBeelink($passData);
        return 'DLR saved';

    }

    public function sendDlrToBeelink($data){

//        $url = 'http://161.117.59.25:6666/receive_report/BD_Nodes';
        $url = 'http://smsproxy.test/api/bulk/dlr/client';
        $client = new Client();
        $options = [
            'json' => [
                'tMsgId' => $data['tMsgId'],
                'status' => $data['status'],
                'delivered_time' => $data['delivered_time']
            ]
        ];

        $response = $client->post($url, $options);
        return $response->getBody();

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

    public function ekShopSms(Request $request)
    {
        //passkey for client identification
        $passkey = [
            'client 1' => '09978bg45SD3SWQ' //For client 1
        ];

        //Check if passkey valid
        if (!in_array($request->passkey, $passkey)) {
            return 'Invalid passkey';
        }
        // Check if number & sms text is valid
        if ($request->has('number') && empty($request->number)) {
            return 'No receiver number';
        }

        if ($request->has('smsText') && empty($request->smsText)) {
            return 'Text empty';
        }

        try {

            $mobile = $request->number;
            $smsText = $request->smsText;

            $opCode = substr($mobile, 0, 5);
            if (substr($opCode, 0, 2) == 88) {
                $mobile = $request['number'] = ltrim($mobile, '88');
            } else if (substr($opCode, 0, 3) == +88) {
                $mobile = $request['number'] = ltrim($mobile, '+88');
            } else if (substr($opCode, 0, 1) != 0) {
                $mobile = $request['number'] = '0' . $mobile;
            }

            $dataArr = [
                'mobile'=>$mobile,
                'smsText'=>$smsText,
                'client' => 'elShop-core',
            ];

            return $this->sendSms($dataArr);


        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return 0;
    }

    public function sendSms($dataArr)
    {
        $status = $this->vfSms($dataArr);
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

        $data['receiver_number'] = '88' . $var['mobile'];
        $data['msg_guid'] = $var['guid'];
        $data['msg_body'] = $var['smsText'];
        $data['msg_client'] = $var['client'];
        $data['msg_provider'] = $var['provider'];
        $data['telecom_operator'] = null;
        $data['tMsgId'] = $var['tMsgId'];

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
        $data['receiver_number'] = '88' . $var['mobile'];
        $data['msg_guid'] = $var['guid'];
        $data['msg_body'] = $var['smsText'];
        $data['msg_client'] = $var['client'];
        $data['msg_provider'] = $var['provider'];
        $data['telecom_operator'] = null;
        $data['error_code'] = $var['error_code'];
        $data['tMsgId'] = $var['tMsgId'];

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

    public function commonSms($mobile, $sms)
    {
        return 'failed';
        $url = 'https://api2.onnorokomsms.com/HttpSendSms.ashx?op=OneToOne&type=TEXT&mobile=' . $mobile . '&smsText=' . $sms . '&username=01612363773&password=asd12300&maskName=&campaignName=';
        $client = new Client([
            'Content-Type' => 'application/json',
            'Host' => 'ekshop.gov.bd',
            'Accept-Charset' => 'utf-8',
            'Last-Modified' => date(' Y-m-d H:i:s')
        ]);
        $promise1 = $client->getAsync($url)->then(
            function ($response) {
                return $response->getBody();
            }, function ($exception) {
            return $exception->getMessage();
        }
        );
        $re = $promise1->wait();
        return $re;
    }

    public function vfSms($data)
    {

        return 'guid=kkbbg130788050b130011c-3g3A2ITRANSHT&errorcode=0&seqno=8801612363773';
        $url = 'https://http.myvfirst.com/smpp/sendsms?username=A2itranshttp&password=j3@W8mt@Lz&coding=3&category=bulk&from=eksShop&to=88' . $data['mobile'] . '&text=' . $data['smsText'];

        $client = new Client([
            'Content-Type' => 'application/json',
            'Host' => 'ekshop.gov.bd',
            'Accept-Charset' => 'utf-8',
            'Last-Modified' => date(' Y-m-d H:i:s')
        ]);
        $promise1 = $client->getAsync($url)->then(
            function ($response) {
                return $response->getBody();
            }, function ($exception) {
            return $exception->getMessage();
        }
        );
        return $promise1->wait();

    }

}
