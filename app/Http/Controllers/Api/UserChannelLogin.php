<?php

namespace App\Http\Controllers\Api;

use DB;
use Cache;
use App\Models\Account;
use App\Models\Device;
use App\Models\GameServer;
use Illuminate\Http\Request;
// use App\Http\Controllers\Controller;

class UserChannelLogin extends Controller
{

    public static $reviewServerId = 999999;
    public static $reviewServerIdIos = 1000000;

    /**
     * 渠道登录游戏
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function login(Request $request)
    {
    	// 白名单
        $bInWhiteList = checkWhite();

        // 判断公告是否允许进入游戏
    	if (cache('bGameValid') && !$bInWhiteList) {
    		return Self::responseResult('false', '暂时无法进入游戏', ['errorcode' => 1]);
    	}

        // 渠道参数，需优化
    	$loginChannelHandle = config('ChannelParam.xipu.params');

    	$uin = $request->uin; #相当于account
	    $sessionid = $request->sessionid;
	    $nickname  = $request->nickname;
	    $uuid = $request->uuid;
	    $deviceType = $request->device;
	    $os = $request->os;
	    $version = $request->version;
	    $channelname = $request->channelname;
        $channelid   = $request->channelid;
	    $submit  = $request->submit ;// 评审版本

        // 评审用户处理
        $bReviewUser = false;
        if (in_array($submit, Self::systemParams('review_version_lst'))) {
            $bReviewUser = true;
        }

	    $result2 = false;

        // 渠道用户校验, 奇遇暂时不校验
	    if (array_key_exists($channelname, $loginChannelHandle)) {
            $result2 = app(ChannelRequest::class)->xipu($uin, $sessionid, $nickname, $channelname);
	    }

        if ($result2) {
        	if (!$uin) {
        		return Self::responseResult('false', '账号不存在', ['errorcode' => 1]);
        	}

            // 获取设备id
            $device = Device::getDeviceId($uuid, $deviceType, $os, $version);

            // 渠道标识后缀
        	$channelname_fix = $channelname;
        	if (array_key_exists($channelname, $loginChannelHandle)) {
        		$channelname_fix = 'xipu';
        	}

            // 账号查询
        	$account = $uin . '@' . $channelname_fix;
            $user = Account::getUserInfoByAccount($account, $channelname, $channelname_fix, $uin, $device);

            $sessionid = getSession($user->id, $sessionid);
            // session 无法使用，改为 cache 存储
            cache(['user_session_' . $sessionid => $sessionid], config('cache.session_expires'));
            cache(['uid' . $sessionid => $user->id], config('cache.session_expires'));
            cache(['channel_' . $sessionid => $channelname], config('cache.session_expires'));

            $data = [];
            $data['user'] = $nickname;
            $data['uid'] = $user->id;
            $data['session'] = $sessionid;
            $data['first'] = false;
            $data['defaultTag'] = 'hot';

            // 服务器数据
            $serverlist = GameServer::serverList();

        	// 评审版本固定
        	if ($bReviewUser) {
                $reviewServerId = ($os == 'ios' ? Self::$reviewServerIdIos : Self::$reviewServerId);
                $data['recentlist'][$reviewServerId]['id'] = $reviewServerId;
                $serverData = DB::table('t_gameserver')->select('id')->get();
                foreach ($serverData as $server) {
                    if ($server->id == $reviewServerId) {
                        continue;
                    }
                    $data['serverlist'][$server->id]['id'] = $server->id;
                    $data['serverlist'][$server->id]['status'] = 'offline';
                    $data['serverlist'][$server->id]['tag'] = 'new';
                }
        	} else {

        		if (!cache('bGameValid') || $bInWhiteList) {
        			$channelRedlist = Self::systemParams('channel_redlist');

                    // 最近访问区服
                    $visited = [];
        			if (empty($channelRedlist) || (is_array($channelRedlist) && !in_array($channelname, $channelRedlist))) {
        				$visited = Cache::remember('visited_' . $user->id, config('cache.expires'), function () use ($user) {
                            return DB::table('t_accountgame_link')->where('a_id', $user->id)->orderBy('g_time', 'desc')->pluck('g_id')->toArray();
                        });
        			}

                    // 是否是第一次登录
                    if (empty($visited)) {
                        $data['first'] = true;
                    }

                    // 最近登录列表
                    $data['recentlist'] = Cache::remember('visited_recentlist_' . $user->id, config('cache.expires'), function () use ($visited) {
                        $recentlist = [];
                        foreach ($visited as $vistid) {
                            $recentlist[]['id'] = $vistid;
                        }
                        return $recentlist;
                    });

                    $serverData = GameServer::serverData();
                    $recommendSid = GameServer::recommend();

                    if ($recommendSid && !in_array($recommendSid, $visited)) {
                        $data['recentlist'][]['id'] = $recommendSid;
                    } else if ($serverlist && !in_array($serverlist[0], $visited)) {
                        $data['recentlist'][]['id'] = $serverlist[0];
                    }

                    // 服务器列表
                    $data['serverlist'] = Cache::remember('visited_serverlist_' . $bInWhiteList, config('cache.expires'), function () use ($serverData, $bInWhiteList) {
                        $serverlist = [];
                        if ($serverData) {
                            foreach ($serverData as $server) {
                                if (!$bInWhiteList && $server->status == 'inner') {
                                    $serverlist[$server->id]['id'] = $server->id;
                                    $serverlist[$server->id]['status'] = 'offline';
                                    $serverlist[$server->id]['tag'] = $server->tag;
                                    continue;
                                }
                                $serverlist[$server->id]['id'] = $server->id;
                                if ($bInWhiteList && in_array($server->status, ['maintain', 'inner'])) {
                                    $serverlist[$server->id]['status'] = 'maintain2';
                                } else {
                                    $serverlist[$server->id]['status'] = $server->status;
                                }
                                $serverlist[$server->id]['tag'] = $server->tag;
                            }
                        }
                        return $serverlist;
                    });
        		} else {
                    return Self::responseResult('false', '游戏进入失败', ['errorcode' => 3]);
                }
        	}
            return Self::responseResult('true', '游戏进入成功', $data);
        } else {
            // 渠道验证失败 0x2132
        	return Self::responseResult('false', '重试 - 0x2132', ['errorcode' => 2]);
        }
    }
}
