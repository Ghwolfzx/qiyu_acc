<?php

namespace App\Models;

use Cache;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $table = 't_device';

    public $timestamps = false;

    // 返回设备id
    public static function getDeviceId ($uuid, $deviceType, $os, $version)
    {
    	$device = Cache::remember('t_device_' . $uuid, config('cache.expires'), function () use ($uuid, $deviceType, $os, $version) {
        	$device = Self::where('deviceid', $uuid)->first();
        	if (!$device) {
	    		$device = new Self;
	    		$device->deviceid = $uuid;
	    		$device->devicetype = $deviceType;
	    		$device->ostype = $os;
	    		$device->osversion = $version;
	    		$device->save();
	    	}
            return $device;
        });
		if (empty($device->devicetype) && $deviceType) {
			Self::where('id', $device->id)->update(['devicetype' => $deviceType]);
		}
		return $device->id;
    }
}
