<?php

namespace App\Http\Controllers;

use App\SmsDetails;
use App\UnsentSmsDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
                $opCode = ltrim($opCode, '88');
                $mobile = $request['number'] = ltrim($mobile, '88');
            } else if (substr($opCode, 0, 3) == +88) {
                $opCode = ltrim($opCode, '+88');
                $mobile = $request['number'] = ltrim($mobile, '+88');
            } else if (substr($opCode, 0, 1) != 0) {
                $opCode = '0' . $opCode;
                $opCode = substr($opCode, 0, 3);
                $mobile = $request['number'] = '0' . $mobile;
            } else {
                $opCode = substr($mobile, 0, 3);
            }

            return $this->sendSms($mobile, $smsText, 'Nodes');

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
        $result = Dlr::where('sms_id', $data->id)
            ->update([
                'delivered_data' => $DELIVERED_DATA,
                'msg_status' => $MSG_STATUS
            ]);

        SmsDetails::where('id', $data->id)
            ->update([
                'is_dlr_received' => '1',
                'msg_guid' => $CLIENT_GUID
            ]);

        return ((!empty($result) ? 'Saved' : 'Not saved'));

//        Assume this url from original client. I've made one for testing
        $url = 'http://smsproxy.test/bulk/client?TO=' . $TO . '&FROM=' . $FROM . '&DELIVERED_DATA=' . $DELIVERED_DATA . '&MSG_STATUS=' . $MSG_STATUS . '&CLIENT_GUID=' . $CLIENT_GUID;
        $client = new Client(['Content-Type' => 'application/json', 'Host' => 'ekshop.gov.bd',
            'Accept-Charset' => 'utf-8', 'Last-Modified' => date(' Y-m-d H:i:s')]);
        $obj = $client->getAsync($url)->then(
            function ($response) {
                return $response->getBody();
            },
            function ($exception) {
                return $exception->getMessage();
            }
        );
        return $obj->wait();
    }

    public function dlrReportFromClient(Request $request)
    {
        return $request;
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
                $opCode = ltrim($opCode, '88');
                $mobile = $request['number'] = ltrim($mobile, '88');
            } else if (substr($opCode, 0, 3) == +88) {
                $opCode = ltrim($opCode, '+88');
                $mobile = $request['number'] = ltrim($mobile, '+88');
            } else if (substr($opCode, 0, 1) != 0) {
                $opCode = '0' . $opCode;
                $opCode = substr($opCode, 0, 3);
                $mobile = $request['number'] = '0' . $mobile;
            } else {
                $opCode = substr($mobile, 0, 3);
            }

            return $this->sendSms($mobile, $smsText, 'ekShop-core');


        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return 0;
    }

    public function sendSms($mobile, $sms, $client)
    {
        $status = $this->vfSms($mobile, $sms);
        $data = explode("&", $status);

        if (strpos($status, 'errorcode=0')) {
            $guid = ltrim($data[0], 'guid=');
            $this->storeVfSms($mobile, $sms, $guid, 'ValueFirst', $client);
        }else{
            $guid = ltrim($data[0], 'guid=');
            $error_code = ltrim($data[1], 'errorcode=');
            $this->storeVfUnsentSms($mobile, $sms, $guid, 'ValueFirst', $client, $error_code);

        }
        return explode("&", $status);
    }

    public function storeVfSms($mobile, $sms, $guid, $provider, $client)
    {

        $data['receiver_number'] = '88' . $mobile;
        $data['msg_guid'] = $guid;
        $data['msg_body'] = $sms;
        $data['msg_client'] = $client;
        $data['msg_provider'] = $provider;
        $data['telecom_operator'] = null;

        $result = SmsDetails::create($data)->id;
        $dlr['to'] = '88' . $mobile;
        $dlr['sms_id'] = $result;

        Dlr::create($dlr)->id;
    }

    public function storeVfUnsentSms($mobile, $sms, $guid, $provider, $client, $error_code)
    {
        $data['receiver_number'] = '88' . $mobile;
        $data['msg_guid'] = $guid;
        $data['msg_body'] = $sms;
        $data['msg_client'] = $client;
        $data['msg_provider'] = $provider;
        $data['telecom_operator'] = null;
        $data['error_code'] = $error_code;
        UnsentSmsDetail::create($data)->id;
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

    public function vfSms($mobile, $sms)
    {
        return 'guid=kkbbg130788050b130011c-3g3A2ITRANSHT&errorcode=0&seqno=8801612363773';
        $url = 'https://http.myvfirst.com/smpp/sendsms?username=A2itranshttp&password=j3@W8mt@Lz&coding=3&category=bulk&from=eksShop&to=88' . $mobile . '&text=' . $sms;

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
