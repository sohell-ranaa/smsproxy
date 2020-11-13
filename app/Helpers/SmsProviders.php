<?php

namespace App\Helpers;

use GuzzleHttp\Client;

class SmsProviders
{

    public static function commonSms($mobile, $sms)
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

    public static function vfSms($data)
    {
        return 'guid=kkbbg130788050b130011c-3g3A2ITRANSHT&errorcode=0&seqno=88016123637736';
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
