<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SoapClient;
use Async;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;

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
                        } //Airtel
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
                        } //Robi
                        else if ($opCode == '018') {

                            $request->request->add(['operator' => 'Robi']);
                            return $request->except('passkey');
                        } //Banglalink
                        else if ($opCode == '019') {

                            $request->request->add(['operator' => 'Banglalink']);
                            return $request->except('passkey');
                        } //Uncategorized number
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


    public function index_recode(Request $request)
    {
        //passkey for client identification
        $passkey = [
            'client 1' => 'ABC', //For client 1
            'client 2' => 'DEF', //For Client 2
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

            if(!strpos($request->smsText, 'আপনার কোড')){
            	return 'Template not matched';
            }

            $smsText = (int) filter_var($request->smsText, FILTER_SANITIZE_NUMBER_INT);

            if (empty($smsText)) {
            	return 'No OTP code found';
            }

            $smsText = $smsText.' আপনার কোড EKSHP';

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
            // return $smsText;

            //Grameenphone
            if ($opCode == '017' || $opCode == '013') {


            } //Airtel
            else if ($opCode == '016') {
                $delivery = $this->sendSms($mobile, $smsText);
                return $delivery;

            } //Robi
            else if ($opCode == '018') {

            } //Banglalink
            else if ($opCode == '019') {

            } //Uncategorized number
            else {

            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return;
    }

    public function sendSms($mobile, $sms)
    {

    	return $this->vfSms($mobile, $sms);

        $delivery = $this->vfSms($mobile, $sms);
        if ($delivery != 'Sent.') {
            $delivery = $this->commonSms($mobile, $sms);
            $delivery = substr($delivery, 0, 4);
            if ($delivery == '1900') {
                return 'delivered.';
            } else {
                return 'failed';
            }
        }
        return 'delivered.';
    }



    public function commonSms($mobile, $sms)
    {

    	return 'failed';
        $url = 'https://api2.onnorokomsms.com/HttpSendSms.ashx?op=OneToOne&type=TEXT&mobile='.$mobile.'&smsText='.$sms.'&username=01612363773&password=asd12300&maskName=&campaignName=';
        $client = new Client();
        $promise1 = $client->getAsync($url)->then(
            function ($response) {
                return $response->getBody();
            }, function ($exception) {
            return $exception->getMessage();
        }
        );
        $response1 = $promise1->wait();
        return $response1;
    }

    public function vfSms($mobile, $sms)
    {
        // return 'ff';
        $url = 'https://http.myvfirst.com/smpp/sendsms?username=acessinfohtpint&password=Ekshop@4321&coding=3&to=88' . $mobile . '&from=8804445600182&text=' . $sms;
        // return $url;

        $client = new Client();
        $promise1 = $client->getAsync($url)->then(
            function ($response) {
                return $response->getBody();
            }, function ($exception) {
            return $exception->getMessage();
        }
        );
        $response1 = $promise1->wait();
        return $response1;
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

            echo $i . ' | ' . $output . ' | ' . $number . ' | ' . date("s") . '<br>';
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

            echo $output . ' | ' . $number . ' | ' . date("s") . '<br>';
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
            echo $i . '<br>';
        }
    }


    public function smsNotification(Request $request)
    {
        // return $request;

        //passkey for client identification
        $passkey = [
            'client 1' => 'ABC', //For client 1
            'client 2' => 'DEF', //For Client 2
        ];


        $smsArray = [
            'sms1' => 'Put me down. I am off limits!',
            'sms2' => 'We are watching you. Go back to bed',
            'sms3' => 'I am your phone. I have covid too',
            'sms4' => 'Working too hard is a source of infection ',
        ];

        $numberArr = [
            'number1' => '01612363773',
            'number2' => '01760966119',
            'number3' => '01821778364'
        ];
        try {
            $mobile = $request->number;

            if ($request->flag == 1) {
                $smsText = $smsArray['sms1'];
            } else if ($request->flag == 2) {

                $smsText = $smsArray['sms2'];
            } else if ($request->flag == 3) {

                $smsText = $smsArray['sms3'];
            } else if ($request->flag == 4) {

                $smsText = $smsArray['sms4'];
            }

            // return $smsText;


            $smsText = $smsText . '. ekshop';
            urlencode($smsText);

            foreach ($numberArr as $mobile) {

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

                // for common sms gateway
                $value = $this->commonSms($mobile, $smsText);
                $value = substr($value, 0, 4);

                // return status
                if ($value == '1900') {
                    echo 'delivered to ' . $mobile . '<br>';
                } else {
                    echo 'failed for ' . $mobile . '';
                }
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }


}
