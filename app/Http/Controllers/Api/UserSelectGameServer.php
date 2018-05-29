<?php

namespace App\Http\Controllers\Api;

use DB;
use Cache;
use GuzzleHttp\Client;
use App\Models\GameServer;
use Illuminate\Http\Request;

class UserSelectGameServer extends Controller
{
    public function index(Request $request)
    {
    	$serverid = $request->sid;# 区服id
    	$session = $request->session;# 登录session
    	$uid = cache('uid' . $session);
        $channel = cache('channel_' . $session);

        if ($session.$uid !== cache('user_session_' . $session)) {
            return $this->responseResult('false', 'session 错误', ['errorcode' => 1]);
        }

        if (empty($channel)) {
            return $this->responseResult('false', 'channel 错误', ['errorcode' => 1]);
        }

        $serverGm = Cache::remember('t_server_' . $serverid, config('cache.expires'), function () use ($serverid) {
            return DB::table('t_server')->where('gameid', $serverid)->select('ip', 'gmport')->first();
        });

    	$client = new Client([
            'base_uri' => 'http://' . $serverGm->ip . ':' . $serverGm->gmport,
            'timeout'  => 2.0,
        ]);
    	$res = $client->get('/webaccount/selectgame?uid=' . $uid . '&session=' . $session . '&server=' . $serverid .'&channel=' . $channel);
    	$result = json_decode($res->getBody()->getContents(), true);
        // dd($result);
        if ($res->getStatusCode() == 200 && $result['session'] == $session) {
            $serverData = GameServer::serverData();
            $data = ['section' => $serverData[$serverid]->section, 'uuid' => $session];
            return $this->responseResult('true', '选区成功', $data);
        } else {
            return $this->responseResult('false', '选区失败，请联系客服');
        }
    }
}
