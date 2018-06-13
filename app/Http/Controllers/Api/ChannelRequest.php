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

        $sign = md5($dangle['AppId_dangle'] . '|' . $dangle['AppKey_dangle'] . '|' . $sessionid . '|' . $uin);

        if (strpos($sessionid, 'ZB_') === false) {
            $loginURL = $dangle['LoginURL_dangle_lst'][0];
        } else {
            $loginURL = $dangle['LoginURL_dangle_lst'][1];
        }

        $url = $loginURL . sprintf('?appid=%s&token=%s&umid=%s&sig=%s', $dangle['AppId_dangle'], $sessionid, $uin, $sign);
        try {
            $response = Self::$client->request('GET', $url, [
                'headers' => [
                    'charset'      => 'utf-8',
                    'Content-type' => "application/x-www-form-urlencoded",
                ]
            ]);

            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody()->getContents(), true);
                if ($data["valid"] == 1 && $data['msg_code'] == 2000) {
                    return true;
                }
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    // 未完成
    public function x7syn($uin, $sessionid, $nickname, $channeltag)
    {
        $x7syn = config('ChannelParam.x7syn');

        $sign = md5($x7syn['appkey_x7sy'] . $sessionid);

        $url = $x7syn['LoginURL_x7sy'] . '?tokenkey=' . urlencode($sessionid) . '&sign=' . $sign;
        try {
            $response = Self::$client->request('POST', $url, [
                'headers' => [
                    'charset'      => 'utf-8',
                    'Content-type' => "application/x-www-form-urlencoded",
                ],
            ]);

            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody()->getContents(), true);
                if ($data['errorno'] == 0) {
                    return [true, $data['data']['guid'], $data['data']['username']];
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

    public function oppo($uin, $sessionid, $nickname, $channeltag)
    {
        $oppo = config('ChannelParam.oppo');

        $time = microtime(true);
        $dataParams['oauthConsumerKey']     = $oppo['AppKey_oppo'];
        $dataParams['oauthToken']           = ($sessionid);
        $dataParams['oauthSignatureMethod'] = "HMAC-SHA1";
        $dataParams['oauthTimestamp']       = intval($time*1000);
        $dataParams['oauthNonce']           = intval($time) + rand(0,9);
        $dataParams['oauthVersion']         = "1.0";
        $requestString                      = $this->_assemblyParameters($dataParams);

        $oauthSignature = $oppo['AppSecret_oppo']."&";
        $sign = $this->_signatureNew($oauthSignature,$requestString);

        $url = $oppo['LoginURL_oppo'] . sprintf('?fileId=%s&token=%s', $uin, ($sessionid));
        try {
            $response = Self::$client->request('GET', $url, [
                'headers' => [
                    'param'       => $requestString,
                    'oauthsignature'=> $sign,
                ]
            ]);

            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody()->getContents(), true);
                dd($data);
                if ($data['resultCode'] == 200 && $data['ssoid'] == $uin) {
                    return [true, $data['ssoid'], $data['userName']];
                }
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function vivon($uin, $sessionid, $nickname, $channeltag)
    {
        $vivon = config('ChannelParam.vivon');

        $url = $vivon['LoginURL_vivo'] . sprintf('?authtoken=%s&from=52muyou', $sessionid);
        try {
            $response = Self::$client->request('POST', $url, [
                'headers' => [
                    'charset'      => 'utf-8',
                    'Content-type' => "application/x-www-form-urlencoded",
                ]
            ]);
            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody()->getContents(), true);
                if ($data['retcode'] == 0) {
                    return [true, $data['data']['openid'], ''];
                }
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function gionee($uin, $sessionid, $nickname, $channeltag)
    {
        $gionee = config('ChannelParam.gionee');

        $ts = time();
        $noce = getSession($uin, $sessionid);
        $signature_str = sprintf("%s\n%s\n%s\n%s\n%s\n%s\n\n", $ts, $noce, "POST", "/account/verify.do", "id.gionee.com", "443");

        $signature = base64_encode(hash_hmac('sha1', $signature_str, $gionee['SecretKey_gionee'], true));
        $Authorization = sprintf('MAC id="%s",ts="%s",nonce="%s",mac="%s"', $gionee['APIKey_gionee'], $ts, $noce, $signature);

        $url = $gionee['LoginURL_gionee'];
        try {
            $response = Self::$client->request('GET', $url, [
                'headers' => [
                    'charset'      => 'utf-8',
                    'Content-type' => "application/x-www-form-urlencoded",
                    'Authorization'=> $Authorization,
                ],
                'body' => $sessionid,
            ]);
            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody()->getContents(), true);
                \Log::info('gionee ====' . $response->getBody()->getContents());
                if (!isset($data['r']) || $data['r'] == 0) {
                    return true;
                }
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function OauthPostExecuteNew($sign,$requestString,$request_serverUrl){
        $opt = array(
                "http"=>array(
                        "method"=>"GET",
                        'header'=>array("param:".$requestString, "oauthsignature:".$sign),
                )
        );
        $res=file_get_contents($request_serverUrl,null,stream_context_create($opt));
        return $res;
    }

    private function _assemblyParameters($dataParams){
       $requestString               = "";
        foreach($dataParams as $key=>$value){
            $requestString = $requestString . $key . "=" . $value . "&";
        }
        return $requestString;
    }

    private function _signatureNew($oauthSignature,$requestString){
        return urlencode(base64_encode( hash_hmac( 'sha1', $requestString,$oauthSignature,true) ));
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
