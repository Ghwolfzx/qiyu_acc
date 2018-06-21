<?php

namespace App\Http\Controllers\Api;

use GuzzleHttp\Client;
use Illuminate\Http\Request;

class ChannelRequest extends Controller
{
    public function tanwan($uin, $sessionid, $nickname, $channeltag)
    {
    	$tanwan = config('ChannelParam.tanwan.params.' . $channeltag);


        $sign = md5($tanwan['appid'] . $uin . $sessionid . $tanwan['loginkey']);
        $client = new Client();

        $url = config('ChannelParam.tanwan.LoginURL') . '?appid=' . $tanwan['appid'] . '&uid=' . $uin . '&state=' . $sessionid . '&flag=' . $sign;
        try {
            $response = $client->request('GET', $url);

            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody()->getContents(), true);
                \Log::info('tanwan ====' . $response->getBody()->getContents());
                if ($data['code'] == 1) {
                    return true;
                }
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
}
