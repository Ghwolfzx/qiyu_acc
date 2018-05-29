<?php

namespace App\Http\Controllers\Api;

use DB;
use Cache;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as BaseController;

class Controller extends BaseController
{
	public static function systemParams($channel)
	{
		$systemParams = Cache::remember(
			'systemParams_' . $channel,
            config('cache.expires'),
            function() use ($channel) {
				$systemParams = DB::table('t_system_params')->where('key', $channel)->select('value')->first();
				if (empty($systemParams)) return '';
				return explode(',', $systemParams->value);
		});
		return $systemParams;
	}

	// response 格式
	public static function responseResult($success, $content = '失败', $result = [])
	{
		return ['success' => $success, 'msg' => $content, 'result' => $result];
	}
}
