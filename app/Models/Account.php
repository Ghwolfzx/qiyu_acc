<?php

namespace App\Models;

use DB;
use Cache;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $table = 't_account';

    public $timestamps = false;

    public static function getUserInfoByAccount ($account, $channelname, $channelname_fix, $uin, $device)
    {
    	$user = Cache::remember(
            'account_userTag_' . $account,
            config('cache.expires'),
            function () use ($account, $channelname, $channelname_fix, $uin) {
                $user = Account::where('account', $account)->select('id', 'account')->first();
                if (empty($user)) {
                    $user = new Self;
                    $user->account = $account;
                    $user->save();
                    DB::table('t_anysdk_account')->insert(['id'=>$user->id, 'channel' => $channelname, 'user_sdk' => $channelname_fix, 'uid' => $uin]);
                }
                return $user;
        });
        // 登录日志表，需优化
        DB::table('t_log_acclogin')->insert(['acc_id'=>$user->id, 'd_id' => $device, 'logintime' => date('Y-m-d H:i:s', time())]);

        return $user;
    }
}
