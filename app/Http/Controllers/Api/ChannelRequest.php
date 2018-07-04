<?php

namespace App\Http\Controllers\Api;

use GuzzleHttp\Client;
use Illuminate\Http\Request;

class ChannelRequest extends Controller
{
    public function _9377($uin, $sessionid, $nickname, $channeltag)
    {
    	$_9377 = config('ChannelParam.9377.params.' . $channeltag);

        $tmp = explode('#', $nickname);
        $token = $tmp[0];
        $time = $tmp[1];

        $tmp = $sessionid . $time . $_9377['appkey'];
        $sign = md5($tmp);

    	if ($sign == $token) {
    		return true;
    	}
        return false;
    }
}
