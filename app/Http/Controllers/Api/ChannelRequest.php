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

    public function muyouios($uin, $sessionid, $nickname, $channeltag)
    {
        $muyou = config('ChannelParam.muyouios');
        $bodys = json_encode(['user_id' => $uin, 'token' => $sessionid]);

        $url = $muyou['LoginURL'];
        try {
            $response = Self::$client->request('POST', $url, ['body' => $bodys]);

            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody()->getContents(), true);
                if ($data["status"] == 1) {
                    if ($data["is_chujian"]) {
                        return [true, $data["chujian_id"], $data["user_account"], 'chujiani', $data['user_id']];
                    }
                    return [true, $data["user_id"], $data["user_account"]];
                }
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
}
