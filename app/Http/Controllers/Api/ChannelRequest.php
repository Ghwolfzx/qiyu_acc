<?php

namespace App\Http\Controllers\Api;

use GuzzleHttp\Client;
use Illuminate\Http\Request;

class ChannelRequest extends Controller
{
    public function qiyu($uin, $sessionid, $nickname, $channeltag)
    {
    	$qiyu = config('ChannelParam.qiyu');

    	$qiyu_appid = $qiyu['params'][$channeltag]['appid'];
        $qiyu_appkey = $qiyu['params'][$channeltag]['appkey'];

        $qiyu_uid = $uin;
        $qiyu_token = $sessionid;
        $tmp = explode(';', $nickname);
        $qiyu_time = $tmp[0];
        $qiyu_sessid = $tmp[1];

        $tmp = $qiyu_appid . $qiyu_uid . $qiyu_token . $qiyu_sessid . $qiyu_time . $qiyu_appkey;
        $sign = md5($tmp);

        $client = new Client([
            'headers' => [
		        'Content-type' => 'application/json',
		        'charset'      => 'utf-8',
		        'Connection'   => 'close',
		    ]
        ]);
        $bodys = "?appid=" . $qiyu_appid . "&uid=" . $qiyu_uid . "&token=" . $qiyu_token . "&time=" . $qiyu_time . "&sessid=" . $qiyu_sessid . "&sign=" . $sign;

        $url = $qiyu['LoginURL'] . $bodys;
        try {
            $response = $client->get($url);

            if ($response->getStatusCode() == 200) {
            	$data = $response->getBody()->getContents();
            	if ($data == 'success') {
            		return true;
            	}
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    // 旌旗
    public function kingcheer($uin, $sessionid, $nickname, $channeltag)
    {
        $kingcheer = config('ChannelParam.kingcheer');

        $appId = $kingcheer['params'][$channeltag]['appid'];
        $appKey = $kingcheer['params'][$channeltag]['appkey'];
        $kingcheer_uid = $uin;
        $token = $sessionid;

        $tmp = "appId" . $appId . "token" . $token . "uid" . $kingcheer_uid . $appKey;
        $sign = md5($tmp);

        $client = new Client([
            'headers' => [
                'Content-type' => 'application/json',
                'charset'      => 'utf-8',
            ],
            'timeout'  => 10.0,
        ]);
        $bodys = json_encode(['appId' => $appId, 'uid' => $kingcheer_uid, 'token' => $token, 'sign' => $sign]);
        $url = $kingcheer['LoginURL'];
        try {
            $response = $client->request('POST', $url, ['body' => $bodys]);

            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody()->getContents(), true);
                if ($data["head"]["responseCode"] == '00000' && $data["head"]["responseMsg"] == 'success') {
                    return true;
                }
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
}
