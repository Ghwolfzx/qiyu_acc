<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;

class Acclogin extends Model
{
    protected $table = 't_log_acclogin';

    public $timestamps = false;

    public static function handleUserLoginLog($user)
    {
    	$loginData = Self::where('acc_id', $user)->where('g_id', '>', '0')->select('g_id')->groupBy('g_id')->get();
    	$links = [];
    	foreach ($loginData as $login) {
    		$link = DB::table('t_accountgame_link')->where(['a_id' => $user, 'g_id' => $login->g_id])->first();
    		if (empty($link)) {
    			$links[] = DB::table('t_accountgame_link')->insertGetId(['a_id' => $user, 'g_id' => $login->g_id, 'g_time' => date('Y-m-d H:i:s', time())]);
    		}
    	}
    	return $links;
    }
}
