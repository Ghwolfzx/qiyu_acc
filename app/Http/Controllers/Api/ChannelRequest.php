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
        $response = $client->get($url);

        if ($response->getStatusCode() == 200) {
        	$data = $response->getBody()->getContents();
        	if ($data == 'success') {
        		return true;
        	}
        }
        return false;
    }

    public function muyou($uin, $sessionid, $nickname, $channeltag)
    {
        $muyou = config('ChannelParam.muyou');
        $bodys = json_encode(['user_id' => $uin, 'token' => $sessionid]);
        $client = new Client([
            'headers' => [
                'Content-type' => 'application/json',
                'charset'      => 'utf-8',
            ]
        ]);

        $url = $muyou['LoginURL'];
        try {
            $response = $client->request('POST', $url, ['body' => $bodys]);

            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody()->getContents(), true);
                if ($data["status"] == 1) {
                    return [true, $data["user_id"], $data["user_account"]];
                }
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function uc($uin, $sessionid, $nickname, $channeltag)
    {
        $uc = config('ChannelParam.uc');
        $tmp = "sid=" . $sessionid . $uc['AppKey'];

        $sign = md5($tmp);
        $params = [
            'id' => (int)(LARAVEL_START * 1000000),
            'service' => 'account.verifySession',
            'data' => ['sid' => $sessionid],
            'game' => ['gameId' => $uc['AppId']],
            'sign' => $sign,
        ];
        $bodys = json_encode($params);

        $client = new Client([
            'headers' => [
                'Content-type' => 'application/json',
                'charset'      => 'utf-8',
            ]
        ]);
        $url = $uc['URLUC'];
        try {
            $response = $client->request('POST', $url, ['body' => $bodys]);

            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody()->getContents(), true);
                if ($data["state"]['code'] == 1) {
                    return [true, $data["data"]['creator'] . '_' . $data['data']['accountId'], $data["data"]['nickname']];
                }
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function _360($uin, $sessionid, $nickname, $channeltag)
    {
        $_360 = config('ChannelParam.360');
        $client = new Client([
            'headers' => [
                'Content-type' => 'application/json',
                'charset'      => 'utf-8',
            ]
        ]);
        $url = sprintf($_360['URL360'], $sessionid);
        try {
            $response = $client->request('GET', $url);

            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody()->getContents(), true);
                return [true, $data["id"], $data["name"]];
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function oppo($uin, $sessionid, $nickname, $channeltag)
    {
        $oppo = config('ChannelParam.oppo');

        $oppoTime = (int)(LARAVEL_START * 1000000);
    }
}
