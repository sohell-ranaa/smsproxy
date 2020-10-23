<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SoapClient;

class BulkSmsController extends Controller
{
    public function index(Request $request)
    {

        //passkey for client identification
        $passkey = [
            'client 1' => 'ABC', //For client 1
            'client 2' => 'DEF', //For Client 2
        ];



        //Check if passkey valid
        if (in_array($request->passkey, $passkey)) {


            // Check if number & sms text is valid
            if ($request->has('number') && !empty($request->number)) {

                if ($request->has('smsText') && !empty($request->smsText)) {

                    try {
                        $mobile = $request->number;
                        $smsText = $request->smsText . ' ekshp';
                        urlencode($smsText);


                        $opCode = substr($mobile, 0, 5);
                        if (substr($opCode, 0, 2) == 88) {
                            $opCode = ltrim($opCode, '88');
                            $mobile = $request['number'] = ltrim($mobile, '88');
                        } else if (substr($opCode, 0, 3) == +88) {
                            $opCode = ltrim($opCode, '+88');
                            $mobile = $request['number'] = ltrim($mobile, '+88');
                        }

                        // return $opCode; 


                        //Grameenphone
                        if ($opCode == '017' || $opCode == '013') {

                            $request->request->add(['operator' => 'Grameenphone']);
                            return $request;
                        }

                        //Airtel
                        else if ($opCode == '016') {

                            $request->request->add(['operator' => 'Airtel']);
                            return $request;
                        }
                        //Robi
                        else if ($opCode == '018') {

                            $request->request->add(['operator' => 'Robi']);
                            return $request;
                        }
                        //Banglalink
                        else if ($opCode == '019') {

                            $request->request->add(['operator' => 'Banglalink']);
                            return $request;
                        }
                        //Uncategorized number
                        else {

                            $request->request->add(['operator' => 'Uncategorized']);
                            return $request;

                            // for common sms gateway
                            $value = $this->commonSms($mobile, $smsText);
                            $value = substr($value, 0, 4);

                            // return status
                            if ($value == '1900') {
                                return 'delivered';
                            } else {
                                return 'failed';
                            }
                        }
                    } catch (Exception $e) {
                        echo $e->getMessage();
                    }
                } else {
                    return 'Text empty';
                }
            } else {
                return 'No receiver number';
            }
        } else {
            return 'Invalid passkey';
        }
    }






    public function commonSms($mobile, $sms)
    {
        return '1900||01612363773||131559820/';

        $soapClient = new SoapClient("https://api2.onnorokomSMS.com/sendSMS.asmx?wsdl");
        $paramArray = array(

            'userName' => "01612363773",
            'userPassword' => "asd12300",
            'mobileNumber' => $mobile,
            'smsText' => $sms,
            'type' => "TEXT",
            'maskName' => '',
            'campaignName' => '',
        );
        $value = $soapClient->__call("OneToOne", array($paramArray));
        return $value->OneToOneResult;
    }
}
