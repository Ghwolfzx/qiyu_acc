<?php

namespace App\Http\Controllers\Api;

use DB;
use Cache;
use Redis;
use GuzzleHttp\Client;
use App\Models\GameServer;
use App\Models\Verified;
use Illuminate\Http\Request;

class UserSelectGameServer extends Controller
{
    public function index(Request $request)
    {
    	$serverid = $request->sid;# 区服id
    	$session = $request->session;# 登录session
    	$uid = cache('uid' . $session);
        $channel = cache('channel_' . $session);

        if (1000001 == $serverid) {
            return $this->responseResult('false', '游客区已满，请使用正式账号进行游戏', ['errorcode' => 5]);
        }

        if (empty(cache('user_session_' . $session))) {
            // 登录超时
            return $this->responseResult('false', '异常 - 0x4213', ['errorcode' => 1]);
        }

        if (empty($channel)) {
            // 参数错误
            return $this->responseResult('false', '异常 - 0x4214', ['errorcode' => 1]);
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
            if (isset($newServer->gameid) && $newServer->gameid == $serverid) {
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

        # -----防沉迷信息同步到游戏服-----
        $loginDate = date('Y-m-d H:i:s', time());
        $channel_fix = cache('channel_fix' . $session);
        $verifiedData = Verified::find($uid);
        if (empty($verifiedData)) {
            if ($channel_fix == 'muyou') {
                $sta = 0;
            } else {
                $sta = 5;
            }
            $verifiedData = Verified::create(['uid' => $uid, 'sta' => $sta, 'stareward' => 0, 'latestlogin' => $loginDate, 'latestoffline' => date('Y-m-d H:i:s', time() - 3600), 'total_time' => 0]);
        }

        if ($verifiedData->sta < 2) {
            $old_gid = $verifiedData->connum;
            $verifiedData->connum = $serverid;
            # 顶号
            if ($verifiedData->latestlogin > $verifiedData->latestoffline && $serverid != $old_gid) {

                $verifiedData->total_time = $verifiedData->total_time + (time() - strtotime($verifiedData->latestlogin));

                if ($old_gid) {
                    $oldServerGm = Cache::remember('t_server_' . $old_gid, config('cache.expires'), function () use ($old_gid) {
                        return DB::table('t_server')->where('gameid', $old_gid)->select('ip', 'gmport')->first();
                    });

                    $oldClient = new Client([
                        'base_uri' => 'http://' . $oldServerGm->ip . ':' . $oldServerGm->gmport,
                        'timeout'  => 4.0,
                    ]);
                    try {
                        $res = $oldClient->get('/accverifiedlogin/verifiedlogin?uid=' . $uid . '&sta=1');
                        $result = json_decode($res->getBody()->getContents(), true);
                    } catch (\Exception $e) {

                        $result = false;
                        if (!$result) {
                            return $this->responseResult('false', '选服失败了，请联系客服', ['errorcode' => 1]);
                        }
                    }
                }
            } else {
                if ((time() - strtotime($verifiedData->latestoffline)) >= 18000) {
                    $verifiedData->total_time = 0;
                }
                $verifiedData->latestlogin = $loginDate;
            }
            $verifiedData->save();
            if ($verifiedData->total_time >= 10800) {
                $off_time = time() - strtotime($verifiedData->latestoffline);

                if ($off_time < 18000) {
                    $verifiedData->latestlogin = $verifiedData->latestoffline;
                    $verifiedData->save();

                    return $this->responseResult('false', '选服失败了啊，请联系客服', ['errorcode' => 2, 'time' => 18000 - $off_time]);
                }
            }
        }

        try {
            $res = $client->get('/webaccount/selectgame?uid=' . $uid . '&session=' . $session . '&server=' . $serverid .'&channel=' . $channel . '&sta=' . $verifiedData->sta . '&total_time=' . $verifiedData->total_time . '&latestlogin=' . $verifiedData->latestlogin . '&latestoffline=' . $verifiedData->latestoffline . '&stareward=' . $verifiedData->stareward);
            $result = json_decode($res->getBody()->getContents(), true);
        } catch (\Exception $e) {

            $result = false;
            if (!$result) {
                \Log::info('webaccount/selectgame url ===: ' . 'http://' . $serverGm->ip . ':' . $serverGm->gmport . '/webaccount/selectgame?uid=' . $uid . '&session=' . $session . '&server=' . $serverid .'&channel=' . $channel . '&sta=' . $verifiedData->sta . '&total_time=' . $verifiedData->total_time . '&latestlogin=' . $verifiedData->latestlogin . '&latestoffline=' . $verifiedData->latestoffline . '&stareward=' . $verifiedData->stareward);
                \Log::info('webaccount/selectgame result ===: ' . $res->getBody()->getContents());
                return $this->responseResult('false', '服务器繁忙，请稍后重试。', ['errorcode' => 3]);
            }
        }

        if (!isset($result['session'])) {
            $retry = 1;
            while (!isset($result['session']) && $retry--) {
                $res = $client->get('/webaccount/selectgame?uid=' . $uid . '&session=' . $session . '&server=' . $serverid .'&channel=' . $channel . '&sta=' . $verifiedData->sta . '&total_time=' . $verifiedData->total_time . '&latestlogin=' . $verifiedData->latestlogin . '&latestoffline=' . $verifiedData->latestoffline . '&stareward=' . $verifiedData->stareward);
                $result = json_decode($res->getBody()->getContents(), true);

            }
            if (!isset($result['session'])) {
                \Log::info('webaccount/selectgame url ===: ' . 'http://' . $serverGm->ip . ':' . $serverGm->gmport . '/webaccount/selectgame?uid=' . $uid . '&session=' . $session . '&server=' . $serverid .'&channel=' . $channel . '&sta=' . $verifiedData->sta . '&total_time=' . $verifiedData->total_time . '&latestlogin=' . $verifiedData->latestlogin . '&latestoffline=' . $verifiedData->latestoffline . '&stareward=' . $verifiedData->stareward);
                \Log::info('webaccount/selectgame result ===: ' . $res->getBody()->getContents());
                return $this->responseResult('false', '服务器繁忙，请稍后重试。。。', ['errorcode' => 3]);
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
                Redis::incr('log_count');
            } else {
                return $this->responseResult('false', '登录记录未找到', ['errorcode' => 1]);
            }

            $serverData = GameServer::serverAllData();
            $data = ['section' => $serverData[$serverid]->section, 'uuid' => $session];
            return $this->responseResult('true', '选区成功', $data);
        } else {
            return $this->responseResult('false', '选区失败，请联系客服', ['errorcode' => 1]);
        }
    }
}
