<?php

namespace App\Http\Controllers\Api;

use GuzzleHttp\Client;
use Illuminate\Http\Request;

class ChannelRequest extends Controller
{
    public function xipu($uin, $sessionid, $nickname, $channeltag)
    {
    	$xipu = config('ChannelParam.xipu.params.' . $channeltag);

        $sign = md5($uin . '&' . $nickname . '&' . $xipu['SERVERKEY']);

        if ($sign == $sessionid) {
            return true;
        } else {
            return false;
        }
    }
}
