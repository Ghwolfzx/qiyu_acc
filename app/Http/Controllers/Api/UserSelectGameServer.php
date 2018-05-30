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

        if (empty(cache('user_session_' . $session))) {
            return $this->responseResult('false', '登录超时', ['errorcode' => 1]);
        }

        if (empty($channel)) {
            return $this->responseResult('false', '参数错误', ['errorcode' => 1]);
        }

        $serverGm = Cache::remember('t_server_' . $serverid, config('cache.expires'), function () use ($serverid) {
            return DB::table('t_server')->where('gameid', $serverid)->select('ip', 'gmport')->first();
        });

        $client = new Client([
            'base_uri' => 'http://' . $serverGm->ip . ':' . $serverGm->gmport,
            'timeout'  => 4.0,
        ]);

        // 新服人数判断
        if ($serverid < 9999) {
            $newServer = Cache::remember('t_server_new_', config('cache.expires'), function () use ($serverid) {
                return DB::table('t_server')->where([['gameid', '<', 9999], 'status' => 'online'])->select('gameid')->orderBy('gameid', 'desc')->first();
            });
            if ($newServer->gameid == $serverid) {
                $online = Cache::remember('server_new_online_', \Carbon\Carbon::now()->addSeconds(5), function () {
                    try {
                        $online = $client->get('/gm/online');
                        $onlineRet = json_decode($online->getBody()->getContents(), true);
                    } catch (\Exception $e) {
                        $onlineRet = false;
                    }
                    return $onlineRet;
                });

                if (isset($onlineRet['online']) && $onlineRet['online'] >= 1000) {
                    return $this->responseResult('false', '服务器爆满', ['errorcode' => 3]);
                }
            }
        }

        try {
            $res = $client->get('/webaccount/selectgame?uid=' . $uid . '&session=' . $session . '&server=' . $serverid .'&channel=' . $channel);
            $result = json_decode($res->getBody()->getContents(), true);
        } catch (\Exception $e) {

            $result = false;
            if (!$result) {
                return $this->responseResult('false', '选服失败，请联系客服', ['errorcode' => 1]);
            }
        }

        if (!isset($result['session'])) {
            $retry = 1;
            while (!isset($result['session']) && $retry--) {
                $res = $client->get('/webaccount/selectgame?uid=' . $uid . '&session=' . $session . '&server=' . $serverid .'&channel=' . $channel);
                $result = json_decode($res->getBody()->getContents(), true);
            }
            if (!isset($result['session'])) {
                return $this->responseResult('false', '选择区服失败，请联系客服', ['errorcode' => 1]);
            }
        }

        if ($res->getStatusCode() == 200 && $result['session'] == $session) {
            $loginaccount = cache('t_log_acclogin' . $uid);
            if ($loginaccount) {
                DB::table('t_log_acclogin')->where('id', $loginaccount)->update(['sid' => $session, 'g_id' => $serverid, 'gametime' => date('Y-m-d H:i:s', time())]);
                $result = DB::table('t_accountgame_link')->where(['a_id' => $uid, 'g_id' => $serverid])->first();
                if (empty($result)) {
                    DB::table('t_accountgame_link')->insert(['a_id' => $uid, 'g_id' => $serverid, 'g_time' => date('Y-m-d H:i:s', time())]);
                } else {
                    DB::table('t_accountgame_link')->where(['a_id' => $uid, 'g_id' => $serverid])->update(['g_time' => date('Y-m-d H:i:s', time())]);
                }
                $visited = cache('visited_' . $uid);
                if (empty($visited) || $visited[0] != $serverid) {
                    Cache::forget('visited_' . $uid);
                    Cache::forget('visited_recentlist_' . $uid);
                    $visited = Cache::remember('visited_' . $uid, config('cache.expires'), function () use ($uid) {
                            return DB::table('t_accountgame_link')->where('a_id', $uid)->orderBy('g_time', 'desc')->pluck('g_id')->toArray();
                        });
                    Cache::remember('visited_recentlist_' . $uid, config('cache.expires'), function () use ($visited) {
                        $recentlist = [];
                        foreach ($visited as $vistid) {
                            $recentlist[]['id'] = $vistid;
                        }
                        return $recentlist;
                    });
                }
            } else {
                return $this->responseResult('false', '登录记录未找到', ['errorcode' => 1]);
            }

            $serverData = GameServer::serverData();
            $data = ['section' => $serverData[$serverid]->section, 'uuid' => $session];
            return $this->responseResult('true', '选区成功', $data);
        } else {
            return $this->responseResult('false', '选区失败，请联系客服', ['errorcode' => 1]);
        }
    }
}
