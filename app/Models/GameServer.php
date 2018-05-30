<?php

namespace App\Models;

use Cache;
use Illuminate\Database\Eloquent\Model;

class GameServer extends Model
{
    protected $table = 't_gameserver';

    public $timestamps = false;
    public static $recommend = '';
    public static $serverData = [];

    // 获取服务器列表
    public static function serverList()
    {
        return Cache::remember('serverList', config('cache.expires'), function () {
            $list = Self::select('id', 'status', 'tag1', 'tag2')->orderBy('id', 'desc')->get();
            $values = [];
            foreach ($list as $server) {
                if ($server->status == 'online' && $server->tag1 == 'hot' && $server->tag2 == 'normal')
                    continue;
                if (in_array($server->status, ['online', 'maintain', 'inner'])) {
                    $values[] = $server->id;
                }
            }
            return $values;
        });
    }

    // 获取服务器信息
    public static function serverData()
    {
        return Cache::remember('serverData', config('cache.expires'), function () {
            $serverList = Self::select('id', 'status', 'tag1', 'tag2', 'configsection')->orderBy('id', 'desc')->get();
            $serverData = [];
            foreach ($serverList as $server) {
                if ($server->status == 'online' && $server->tag1 == 'new' && $server->tag2 == 'normal')
                    continue;
                $server->tag = 'normal';
                if ($server->tag2 == 'hot') {
                    $server->tag = 'hot';
                } else if ($server->tag1 == 'new') {
                    $server->tag = 'new';
                }
                $server->section = $server->configsection;
                unset($server->tag1);
                unset($server->tag2);
                unset($server->configsection);
                $serverData[$server->id] = $server;
            }
            return $serverData;
        });
    }

    public static function recommend()
    {
        return Cache::remember('recommendSid', config('cache.expires'), function () {
            $today0 = date('Y-m-d 00:00:00', time());
            $today24 = date('Y-m-d 23:59:59', time());
            $serverData = Self::select('id', 'name', 'status', 'createtime')
                ->whereIn('status', ['online', 'maintain', 'inner'])
                ->where([['createtime', '>=', $today0], ['createtime', '<=', $today24]])
                ->get()->toArray();

            $firstId = '';
            if ($serverData) {
                $serverData = array_column($serverData, 'id');
                $firstId = max($serverData);
            }

            $recommend = '';
            if ($firstId) {
                $recommend = $firstId;
            } else {
                $servers = Self::serverData();
                foreach ($servers as $server) {
                    if ($server->status == 'online') {
                        $recommend = $server->id;
                        break;
                    }
                }
            }
            return $recommend;
        });
    }
}
