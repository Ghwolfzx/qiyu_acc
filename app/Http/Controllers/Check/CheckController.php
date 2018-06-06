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

    	if ($package && $deviceid && $version) {
			DB::table('zyj_valid')->insert(['channel' => $channel,'package' => $package, 'deviceid' => $deviceid, 'version' => $version]);
    	}

    	return Self::responseResult('true', 'success');
    }

    public static function responseResult($success, $content = '失败', $result = [])
	{
		return ['success' => $success, 'msg' => $content, 'result' => $result];
	}
}
