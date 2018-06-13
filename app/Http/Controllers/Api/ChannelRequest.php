<?php

namespace App\Http\Controllers\Api;

use GuzzleHttp\Client;
use Illuminate\Http\Request;

class ChannelRequest extends Controller
{
    protected static $client;

    public function __construct()
    {
        Self::$client = new Client([
            'headers' => [
                'Content-type' => 'application/json',
                'charset'      => 'utf-8',
            ]
        ]);
    }

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

        $url = $muyou['LoginURL'];
        try {
            $response = Self::$client->request('POST', $url, ['body' => $bodys]);

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

    public function dangle($uin, $sessionid, $nickname, $channeltag)
    {
        $dangle = config('ChannelParam.dangle');

        $sign = md5($dangle['LoginURL_dangle'] . '|' . $dangle['AppKey_dangle'] . '|' . $sessionid . '|' . $uin);

        if (strpos($sessionid, 'ZB_') === false) {
            $loginURL = $dangle['LoginURL_dangle_lst'][0];
        } else {
            $loginURL = $dangle['LoginURL_dangle_lst'][1];
        }

        $url = $loginURL . sprintf('?appid=%s&token=%s&umid=%s&sig=%s', $dangle['AppId_dangle'], $sessionid, $uin, $sign);
        try {
            $response = Self::$client->request('GET', $url);

            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody()->getContents(), true);

                if ($data["valid"] == 1 && $data['msg_code'] == 2000) {
                    return [true, $data["data"]['creator'] . '_' . $data['data']['accountId'], $data["data"]['nickname']];
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

        $url = $uc['URLUC'];
        try {
            $response = Self::$client->request('POST', $url, ['body' => $bodys]);

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
        $url = sprintf($_360['URL360'], $sessionid);
        try {
            $response = Self::$client->request('GET', $url);

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
        $oppoRandom = random_int(0, 999999999);

        $baseStr = sprintf('oauthConsumerKey=%s&oauthToken=%s&oauthSignatureMethod=HMAC-SHA1&oauthTimestamp=%s&oauthNonce=%d&oauthVersion=1.0&',
                    $oppo['AppKey_oppo'], urlencode($sessionid), $oppoTime, $oppoRandom);

        $oauthSignature = urlencode(base64_encode(hash_hmac('sha1', $baseStr, $oppo['AppSecret_oppo'] . '&', true)));
        $url = $oppo['LoginURL_oppo'] . sprintf('?fileId=%s&token=%s', $uin, urlencode($sessionid));
        try {
            $response = Self::$client->request('GET', $url, [
                'headers' => [
                    'charset'      => 'utf-8',
                    'params'       => $baseStr,
                    'oauthSignature'=> $oauthSignature,
                ]
            ]);

            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody()->getContents(), true);

                if ($data['resultCode'] == 200 && $data['ssoid'] == $uin) {
                    return [true, $data['ssoid'], $data['userName']];
                }
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    // 有问题
    public function huawei($uin, $sessionid, $nickname, $channeltag)
    {
        $huawei = config('ChannelParam.huawein');

        $url = $huawei['LoginURL_huawei'] . sprintf('?nsp_svc=OpenUP.User.getInfo&nsp_ts=%d&access_token=%s', time(), urlencode($sessionid));
        try {
            $response = Self::$client->request('GET', $url, [
                'headers' => [
                    'charset'      => 'utf-8',
                    'Content-type' => "application/x-www-form-urlencoded",
                ]
            ]);
            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody()->getContents(), true);

                if (!isset($data['error'])) {
                    return [true, $data['userID'], $data['userName']];
                }
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function lenovo($uin, $sessionid, $nickname, $channeltag)
    {
        $lenovo = config('ChannelParam.lenovo');

        $url = $lenovo['LoginURL_lenovo'] . sprintf('?lpsust=%s&realm=%s', $sessionid, $lenovo['AppId_lenovo']);
        try {
            $response = Self::$client->request('GET', $url, [
                'headers' => [
                    'charset'      => 'utf-8',
                    'Content-type' => "application/x-www-form-urlencoded",
                ]
            ]);
            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody()->getContents(), true);
                dd($data);
                if (!isset($data['error'])) {
                    return [true, $data['userID'], $data['userName']];
                }
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }

    }
}
