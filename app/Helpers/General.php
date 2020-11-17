<?php

namespace App\Helpers;

use App\Dlr;
use App\SmsDetails;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Carbon\Carbon;
use DB;

class General
{

    public static function passKey()
    {
        return
            [
                'client 1' => 'Open1234', //For client 1
                'client 2' => 'jfbwajJGUHYFG237yr3wkjBUYG', //For Client 2
                'ekShop' => '09978bg45SD3SWQ' //For ekShop-Code
            ];

    }

    public static function checkValidation($request, $noTemplateCheck = null)
    {
        //Check if passkey valid
        if (!in_array($request->passkey, General::passKey())) {
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
        if ($noTemplateCheck == null && !strpos($request->smsText, 'আপনার কোড')) {
            return 'Template not matched';
        }
    }

    public static function formatMobileNumber($mobile)
    {
        $opCode = substr($mobile, 0, 5);
        if (substr($opCode, 0, 2) == 88) {
            $mobile = $request['number'] = ltrim($mobile, '88');
        } else if (substr($opCode, 0, 3) == +88) {
            $mobile = $request['number'] = ltrim($mobile, '+88');
        } else if (substr($opCode, 0, 1) != 0) {
            $mobile = $request['number'] = '0' . $mobile;
        }

        return $mobile;
    }

    public static function setVfCreateValues($var)
    {
        $opCode = substr($var['mobile'], 0, 3);
        if ($opCode == '015') {
            $operator = 'Teletalk';
        }
        if ($opCode == '016') {
            $operator = 'Airtel';
        }
        if ($opCode == '013' || $opCode == '017') {
            $operator = 'Grameenphone';
        }
        if ($opCode == '018') {
            $operator = 'Robi';
        }
        if ($opCode == '016') {
            $operator = 'Airtel';
        }
        if ($opCode == '014' || $opCode == '019') {
            $operator = 'Banglalink';
        }

        $data['receiver_number'] = '88' . $var['mobile'];
        $data['msg_guid'] = $var['guid'];
        $data['msg_body'] = $var['smsText'];
        $data['msg_client'] = $var['client'];
        $data['msg_provider'] = $var['provider'];
        $data['telecom_operator'] = null;
        $data['error_code'] = (isset($var['error_code']) ? $var['error_code'] : NULL);
        $data['tMsgId'] = (isset($var['tMsgId']) ? $var['tMsgId'] : NULL);
        $data['telecom_operator'] = $operator;
        return $data;

    }

    public static function dlrValidation($CLIENT_GUID)
    {

        $data = SmsDetails::where('msg_guid', $CLIENT_GUID)->orderBy('id', 'desc')->first();

        if (is_null($data)) {
            return 'No data found';
        }
        // if ($data->is_dlr_received != 0) {
        //     return 'Already updated';
        // }
        return $data;
    }

    public static function sendDlrToBeelink($data)
    {

        $url = 'http://161.117.59.25:6666/receive_report/BD_Nodes';
//         $url = 'http://smsproxy.test/api/bulk/dlr/client';
        // $url = 'http://smsc.ekshop.world/api/bulk/dlr/client';


        $client = new Client();
        $options = [
            'form_params' => [
                'tMsgId' => $data['tMsgId'],
                'status' => $data['status']
            ]
        ];

        try {
            $response = $client->post($url, $options);
        } catch (GuzzleException $e) {
            return $e->getMessage();
        }
        return [
            'status_code' => $response->getStatusCode(),
            'body' => (string)$response->getBody()
        ];

    }

    public static function getClient()
    {
        return [
            'nodes' => 'nodes',
            'ekshop' => 'ekshop'
        ];
    }

    public static function beelinkReport()
    {

        $data['successful'] = SmsDetails::where('msg_client', 'nodes')
            ->where('is_dlr_received', 1)
            ->whereDate('created_at', Carbon::today())
            ->count();

        $data['total'] = SmsDetails::where('msg_client', 'nodes')
            ->whereDate('created_at', Carbon::today())
            ->count();
        return $data;
    }

    public static function mobileValidaton($mobile)
    {
        if (strlen((string)$mobile) != 11) {
            return 'Invalid number length';
        }
        $OpArr = ['013', '014', '015', '016', '017', '018', '019'];
        $op = substr($mobile, 0, 3);

        if (!in_array($op, $OpArr)) {
            return 'Invalid number';
        }
        return null;
    }
}
