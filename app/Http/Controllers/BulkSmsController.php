<?php

namespace App\Http\Controllers;

use App\Helpers\General;
use App\Helpers\SmsProviders;
use App\SmsDetails;
use App\UnsentSmsDetail;
use Carbon\Carbon;
use GuzzleHttp\Client;
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
            $checkError = General::mobileValidaton($mobile);

            if (!is_null($checkError)) {
                return $checkError;
            }

            $data = [
                'mobile' => $mobile,
                'smsText' => $smsText,
                'client' => General::getClient()['nodes'],
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
            $checkError = General::mobileValidaton($mobile);

            if (!is_null($checkError)) {
                return $checkError;
            }

            $dataArr = [
                'mobile' => $mobile,
                'smsText' => $smsText,
                'client' => General::getClient()['ekshop'],
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
        $DELIVERED_DATA = null;
        if (isset($request->DELIVERED_DATA) && !empty($request->DELIVERED_DATA)) {
            $DELIVERED_DATA = Carbon::parse($request->DELIVERED_DATA)->addMinutes(30);
        }
        $MSG_STATUS = $request->MSG_STATUS;
        $CLIENT_GUID = $request->CLIENT_GUID;

        $data = General::dlrValidation($CLIENT_GUID);
        if (!is_object($data)) {
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

        return General::sendDlrToBeelink($passData);
    }

    public function dlrReportFromClient(Request $request)
    {
        return $request;
    }

    public function dlrReportAll(Request $request, $number = null)
    {
        $limit = 10;
        $passkey = '';
        if (!isset($request->key)) {
            return 'Authentication required';
        }
        if (isset($request->limit)) {
            $limit = $request->limit;
        }

        if (!is_null($number)) {
            return Dlr::where('dlrs.to', $number)
                ->orderBy('id', 'desc')
                ->limit($limit)
                ->get();
        }
        return Dlr::orderBy('id', 'desc')
            ->limit($limit)
            ->get();


    }

    public function sendSms($dataArr)
    {

        $opCode = substr($dataArr['mobile'], 0, 3);

        if ($opCode == '011') {  //$opCode == '015')

            $provider = 'Teletalk';
            $status = SmsProviders::teletalkSms($dataArr);
            $data = explode(",", $status);

            if (ltrim($data[0], '<reply>') == 'SUCCESS') {
                $guid = ltrim($data[1], 'ID=');
                $t_arr = [
                    'guid' => $guid,
                    'provider' => $provider
                ];

                $var = array_merge($dataArr, $t_arr);
                return $this->storeSuccessSms($var);

            } else {

                $guid = ltrim($data[1], 'ID=');
                $error_code = 99;

                $t_arr = [
                    'guid' => $guid,
                    'provider' => $provider,
                    'error_code' => $error_code
                ];
                $var = array_merge($dataArr, $t_arr);
                return $this->storeUnsentSms($var);
            }
        } elseif ($opCode == '012' || $opCode == '010') {  //$opCode == '016' || $opCode == '018'

            $provider = 'Robi/Airtel';
            $status = (object)SmsProviders::robiAirtelSms($dataArr);

            if ($status->StatusText == 'success') {
                $guid = $status->MessageId;

                $t_arr = [
                    'guid' => $guid,
                    'provider' => $provider
                ];

                $var = array_merge($dataArr, $t_arr);
                return $this->storeSuccessSms($var);

            } else {
                $guid = $status->MessageId;
                $error_code = $status->ErrorCode;

                $t_arr = [
                    'guid' => $guid,
                    'provider' => $provider,
                    'error_code' => $error_code
                ];
                $var = array_merge($dataArr, $t_arr);
                return $this->storeUnsentSms($var);

            }
        } else {
            $status = SmsProviders::vfSms($dataArr);
            $data = explode("&", $status);

            if (strpos($status, 'errorcode=0')) {
                $guid = ltrim($data[0], 'guid=');
                $t_arr = [
                    'guid' => $guid,
                    'provider' => 'ValueFirst'
                ];
                $var = array_merge($dataArr, $t_arr);
                return $this->storeSuccessSms($var);
            } else {
                $guid = ltrim($data[0], 'guid=');
                $error_code = ltrim($data[1], 'errorcode=');

                $t_arr = [
                    'guid' => $guid,
                    'provider' => 'ValueFirst',
                    'error_code' => $error_code
                ];
                $var = array_merge($dataArr, $t_arr);
                return $this->storeUnsentSms($var);
            }
        }
    }

    public function storeSuccessSms($var)
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
            'tMsgId' => $data['tMsgId'],
        ];
        return $storeStatus;
    }

    public function storeUnsentSms($var)
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

    public function sendTeletalk(Request $request)
    {
        return SmsProviders::teletalkSms($request);
    }

    public function requestDlr()
    {
        $checkforDlr = SmsDetails::where('msg_provider', 'Teletalk')
            ->orWhere('msg_provider', 'Robi/Airtel')
            ->where('is_dlr_received', 0);

        if ($checkforDlr->count() > 0) {

            $msg_guid = $checkforDlr->select('id', 'msg_guid', 'tMsgId', 'msg_provider')->get();


            foreach ($msg_guid as $data) {

                if ($data['msg_provider'] == 'Teletalk') {
                    $url = 'https://bulksms.teletalk.com.bd/link_sms_send.php?op=STATUS&user=Aspire&pass=ekShop@2021&sms_id=' . $data->msg_guid;
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

                    $response = $promise1->wait();

                    if (strpos($response, 'SUCCESSFULLY SENT TO')) {
                        $deliveryStatus = 'Delivered';
                    } else {
                        $deliveryStatus = 'Failed';
                    }
                    Dlr::where('sms_id', $data->id)
                        ->update([
                            'msg_status' => $deliveryStatus
                        ]);

                    SmsDetails::where('id', $data->id)
                        ->update([
                            'is_dlr_received' => '1',
                            'msg_guid' => $data->msg_guid
                        ]);

                    $passData = [
                        'tMsgId' => $data['tMsgId'],
                        'status' => $deliveryStatus
                    ];

                    General::sendDlrToBeelink($passData);

                } elseif ($data['msg_provider'] == 'Robi/Airtel') {

                    $url = 'https://api.mobireach.com.bd/GetMessageStatus?Username=aspire&Password=Dhaka@1234&MessageId=' . $data->msg_guid;

                    $client = new Client([
                        'Content-Type' => 'application/json',
                        'Host' => 'ekshop.gov.bd',
                        'Accept-Charset' => 'utf-8',
                        'Date' => date(' Y-m-d H:i:s')
                    ]);
                    $promise1 = $client->getAsync($url)->then(
                        function ($response) {
                            return $response->getBody();
                        }, function ($exception) {
                        return $exception->getMessage();
                    }
                    );

                    $response = $promise1->wait();

                    $d = General::xmltoJson($response);

                    $var = $d['ServiceClass'];

                    if ($var['ErrorCode'] == '0') {
                        $deliveryStatus = 'Delivered';
                    } else {
                        $deliveryStatus = 'Failed';
                    }
                    Dlr::where('sms_id', $data->id)
                        ->update([
                            'msg_status' => $deliveryStatus
                        ]);

                    SmsDetails::where('id', $data->id)
                        ->update([
                            'is_dlr_received' => '1',
                            'msg_guid' => $data->msg_guid
                        ]);

                    $passData = [
                        'tMsgId' => $data['tMsgId'],
                        'status' => $deliveryStatus
                    ];
                    General::sendDlrToBeelink($passData);
                }
            }
        }
    }
}
