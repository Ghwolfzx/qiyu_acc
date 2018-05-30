<?php

namespace App\Http\Controllers\Api;

use DB;
use Cache;
use Redis;
use App\Models\GameServer;
use Illuminate\Http\Request;

class RefreshCache extends Controller
{
    public function refresh(Request $request)
    {
    	Cache::forget('serverList');
    	GameServer::serverList();

    	Cache::forget('visited_serverlist_');
    	Cache::forget('visited_serverlist_1');

    	Cache::forget('serverData');
    	GameServer::serverData();

    	Cache::forget('serverAllData');
    	GameServer::serverAllData();

    	Cache::forget('recommendSid');
    	GameServer::recommend();

    	Cache::forget('t_server_new_');
    	$newServer = Cache::remember('t_server_new_', config('cache.expires'), function () {
            return DB::table('t_server')->where([['gameid', '<', 9999], 'status' => 'online'])->select('gameid')->orderBy('gameid', 'desc')->first();
        });

    	$serverid = $request->serverid;
    	if ($serverid) {
    		Cache::forget('t_server_' . $serverid);
	        Cache::remember('t_server_' . $serverid, config('cache.expires'), function () use ($serverid) {
	            return DB::table('t_server')->where('gameid', $serverid)->select('ip', 'gmport')->first();
	        });

    	}

        return ['success' => 'true'];
    }

    public function notice()
    {
        Cache::forget('t_system_notice_execute_iddesc');
        Cache::forget('target_notice');
        Cache::forget('target_notice_params');
        Redis::del('ChannelForceUpdate_*');

        return ['success' => 'true'];
    }
}
