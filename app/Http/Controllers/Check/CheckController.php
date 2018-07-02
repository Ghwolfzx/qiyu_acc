<?php

namespace App\Http\Controllers\Check;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CheckController extends Controller
{
    public function check(Request $request)
    {
    	$channel = $request->channel;
    	$packagesign = $request->packagesign;
    	$package 	= $request->package;
    	$appname 	= $request->appname;
    	$deviceid 	= $request->deviceid;
    	$version 	= $request->version;
    	\Log::info('check_params ==== ' . json_encode($request->all()));
        if ($channel == 'sytxABWZX30108' &&

            ($package != 'com.my.sytx.bwzx' ||

            ($appname != '霸王之心' && $appname != '作妖记')

            ))
        {
            return Self::responseResult('false', '禁止进入');
        }
    	// if ($package && $deviceid && $version) {
			DB::table('zyj_valid')->insert(['channel' => $channel, 'packagesign' => $packagesign, 'package' => $package, 'appname' => $appname, 'deviceid' => $deviceid, 'version' => $version]);
    	// }

    	return Self::responseResult('true', 'success');
    }

    public static function responseResult($success, $content = '失败', $result = [])
	{
		return ['success' => $success, 'msg' => $content, 'result' => $result];
	}
}
