<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SoapClient;
use Async;

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
                        } else if (substr($opCode, 0, 1) != 0) {
                            $opCode = '0' . $opCode;
                            $opCode = substr($opCode, 0, 3);
                            $mobile = $request['number'] = '0' . $mobile;
                        } else {
                            $opCode = substr($mobile, 0, 3);
                        }

                        // dd($opCode);

                        //Grameenphone
                        if ($opCode == '017' || $opCode == '013') {

                            $request->request->add(['operator' => 'Grameenphone']);
                            return $request->except('passkey');
                        }

                        //Airtel
                        else if ($opCode == '016') {



                            // for common sms gateway
                            $value = $this->commonSms($mobile, $smsText);
                            $value = substr($value, 0, 4);

                            // return status
                            if ($value == '1900') {
                                return 'delivered';
                            } else {
                                return 'failed';
                            }



                            $request->request->add(['operator' => 'Airtel']);
                            return $request->except('passkey');
                        }
                        //Robi
                        else if ($opCode == '018') {

                            $request->request->add(['operator' => 'Robi']);
                            return $request->except('passkey');
                        }
                        //Banglalink
                        else if ($opCode == '019') {

                            $request->request->add(['operator' => 'Banglalink']);
                            return $request->except('passkey');
                        }
                        //Uncategorized number
                        else {
                            // return 'delivered';

                            $request->request->add(['operator' => 'Uncategorized']);
                            // return $request->except('passkey');

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



    public function loadTest()
    {

        for ($i = 1; $i <= 10; $i++) {

            $number = '01612363773';
            $url = 'http://smsproxy.test/bulk?passkey=ABC&smsText=Hi&number=' . $number;

            // create & initialize a curl session
            $curl = curl_init();

            // set our url with curl_setopt()
            curl_setopt($curl, CURLOPT_URL, $url);

            // return the transfer as a string, also with setopt()
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            // curl_exec() executes the started curl session
            // $output contains the output string
            $output = curl_exec($curl);

            // close curl resource to free up system resources
            // (deletes the variable made by curl_init)
            curl_close($curl);

            echo  $i . ' | ' . $output . ' | ' . $number . ' | ' . date("s") . '<br>';
        }
    }



    public function asyncLoad()
    {
        Async::run(function () {

            $number = '01612363773';
            $url = 'http://smsproxy.test/bulk?passkey=ABC&smsText=Hi&number=' . $number;
            // create & initialize a curl session
            $curl = curl_init();

            // set our url with curl_setopt()
            curl_setopt($curl, CURLOPT_URL, $url);

            // return the transfer as a string, also with setopt()
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            // curl_exec() executes the started curl session
            // $output contains the output string
            $output = curl_exec($curl);

            // close curl resource to free up system resources
            // (deletes the variable made by curl_init)
            curl_close($curl);

            echo  $output . ' | ' . $number . ' | ' . date("s") . '<br>';
        });
    }

    public function asyncLoadTest()
    {
        for ($i = 0; $i < 50; $i++) {
            $url = 'http://smsproxy.test/bulk/asyncload';
            // create & initialize a curl session
            $curl = curl_init();

            // set our url with curl_setopt()
            curl_setopt($curl, CURLOPT_URL, $url);

            // return the transfer as a string, also with setopt()
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            // curl_exec() executes the started curl session
            // $output contains the output string
            $output = curl_exec($curl);

            // close curl resource to free up system resources
            // (deletes the variable made by curl_init)
            curl_close($curl);
            echo $i.'<br>';
        }
    }
}
