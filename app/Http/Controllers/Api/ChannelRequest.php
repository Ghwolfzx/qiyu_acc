<?php

namespace App\Http\Controllers\Api;

use GuzzleHttp\Client;
use Illuminate\Http\Request;

class ChannelRequest extends Controller
{
    public function shenyou($uin, $sessionid, $nickname, $channeltag)
    {
    	$sytx = config('ChannelParam.sytx');

    	$sytx_appid = $sytx['params'][$channeltag]['appid'];
        $sytx_appkey = $sytx['params'][$channeltag]['appkey'];

        $tmp = explode(';', $nickname);
        $time = $tmp[0];
        $token = $tmp[1];

        $tmp = $sytx_appid . $uin . $sessionid . $token . $time . $sytx_appkey;
        $sign = md5($tmp);

        $client = new Client([
            'headers' => [
		        'Content-type' => 'application/json',
		        'charset'      => 'utf-8',
		        'Connection'   => 'close',
		    ]
        ]);
        $bodys = "?appid=" . $sytx_appid . "&uid=" . $uin . "&token=" . $token . "&time=" . $time . "&sessid=" . $sessionid . "&sign=" . $sign;

        $url = $sytx['LoginURL'] . $bodys;
        $response = $client->get($url);

        if ($response->getStatusCode() == 200) {
        	$data = $response->getBody()->getContents();
        	if ($data == 'success') {
        		return true;
        	}
        }
        return false;
    }
}
