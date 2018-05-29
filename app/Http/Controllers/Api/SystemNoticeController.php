<?php
namespace App\Http\Controllers\Api;

use DB;
use Cache;
use Log;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SystemNoticeController extends Controller
{

	//
	protected static $system_notice_forceupdate_pb_channel_dict = [];
	protected static $ChannelForceUpdate = ['channel_forceupdate_url2'=>[]];
	protected static $system_notice_pb = [];

    /*public function __construct()
	{
		// 强更参数
		Self::$ChannelForceUpdate = config('ChannelForceUpdate');
	}*/

	/**
	 * 弹板接口
	 * @param  forceupdate  版本号
	 * @param  channel  渠道标识
	 * @return {
            title 标题
            content 内容
            begint 开始时间
            endt 结束时间
            opt 是否可进入游戏
            url 强更地址
            servertime 服务器时间
        }
	 */
    public function show(Request $request)
    {
    	$forceupdate = $request->forceupdate;
    	$channel = $request->channel;

    	// 是否是白名单
    	$bInWhiteList = checkWhite();
        Log::info(LARAVEL_START . ' [WhiteList] ===' . $bInWhiteList);

    	// 强更基础版本
		$channel_params = [];

    	// 判断是否是强更渠道
    	if (!$this->checkChennel($channel, Self::$ChannelForceUpdate['channel_forceupdate_url2'])) {
            $channel_params = Self::$ChannelForceUpdate['channel_forceupdate_url2'][$channel] = Cache::remember(
                'ChannelForceUpdate_' . $channel,
                config('cache.expires'),
                function () use ($channel) {
                    $ret = DB::table('t_system_params')->where('key', $channel)->select('value')->first();
                    if ($ret) {
                        $tmp = explode('|', $ret->value);
                        $channel_params['ver'] = $tmp[0];
                        $channel_params['url'] = $tmp[1];
                        return $channel_params;
                    }
            });
    	}

    	$result = [];
    	// 公告或强更
    	if (!empty($channel_params) &&
    		$bInWhiteList &&
    		$forceupdate < $channel_params['ver']
    	) {
            // 强更
    		$data = $this->SystemNoticeForceUpdatePbChannel($channel);
    		if ($data) {
                return Self::responseResult('true', '强更公告', $data);
    		}
    	}

    	// 白名单跳过公告
    	if ($bInWhiteList) {
            return Self::responseResult('true', '白名单跳过', []);
    	} else {
    		return $this->SystemNoticePb();
    	}
    }

    // 强更公告
    protected function SystemNoticeForceUpdatePbChannel($channel_tag)
    {
    	if (!$this->checkChennel($channel_tag, Self::$ChannelForceUpdate['channel_forceupdate_url2'])) {
    		return ;
    	}

    	if (!$this->checkChennel($channel_tag, Self::$system_notice_forceupdate_pb_channel_dict)) {
    		$channel_params = Self::$ChannelForceUpdate['channel_forceupdate_url2'][$channel_tag];

    		if ($this->checkChennel('url', $channel_params)) {
    			$update_url = $channel_params['url'];
    		} else {
    			$update_url = $channel_params;
    		}

    		$params['title'] = '更新通知';
    		if ($update_url) {
    			$params['content'] = '尊敬的勇者大人，由于您的版本过低，请点击继续按钮通过浏览器下载更新^_^';
    		} else {
    			$params['content'] = '尊敬的勇者大人，由于您所在的渠道还未准备好,请稍后重试!';
    		}
    		$params['begint'] 	= 0;
    		$params['endt'] 	= 0;
    		$params['opt'] 		= 2;
    		$params['url'] 		= $update_url;
    		Self::$system_notice_forceupdate_pb_channel_dict[$channel_tag] = $params;
    	}
    	Self::$system_notice_forceupdate_pb_channel_dict[$channel_tag]['servertime'] = time();
    	return Self::$system_notice_forceupdate_pb_channel_dict[$channel_tag];
    }

    // 公告弹板信息
    protected function SystemNoticePb()
    {
    	if (empty(Self::$system_notice_pb)) {
    		$this->MakeSystemNoticePb();
    	}
        return Self::responseResult('true', '游戏公告', Self::$system_notice_pb);
    }

    // 生成弹板
    protected function MakeSystemNoticePb()
    {
        // 待执行的公告信息
    	$system_notice = Cache::remember(
            't_system_notice_execute_iddesc',
            config('cache.expires'),
            function () {
                return DB::table('t_system_notice')->where('status', 'execute')->orderBy('id', 'desc')->get();
        });
        $params = [];
    	if ($system_notice) {
    		$nowtime = date('Y-m-d H:i:s', time());
            // 当前展示公告
        	$target_notice = Cache::remember(
                'target_notice',
                config('cache.expires'),
                function () use ($system_notice, $nowtime) {
                    $target_notice = [];
                    foreach ($system_notice as $notice) {
                        if ($notice->start <= $nowtime && $nowtime <= $notice->end) {
                            $target_notice = $notice;
                            break;
                        }
                    }
                    return $target_notice;
            });

        	if ($target_notice) {
                $params = Cache::remember(
                    'target_notice_params',
                    config('cache.expires'),
                    function () use ($target_notice, $nowtime) {
                        $params['title']    = $target_notice->title;
                        $params['content']  = $target_notice->content;
                        $params['begint']   = strtotime($target_notice->start);
                        $params['endt']     = strtotime($target_notice->end);
                        if ($target_notice->opt == 'yes') {
                            $params['opt']  = 0;
                        } else {
                            $params['opt']  = 1;
                            if ($nowtime < $target_notice->end && !cache('bGameValid')) {
                                // 缓存秒数
                                $time = $params['endt'] - time();
                                cache(['bGameValid' => true], Carbon::now()->addSeconds($time));
                            }
                        }
                        $params['url'] = '';

                        return $params;
                });
                $params['servertime'] = time();
        	}
    	}

    	Self::$system_notice_pb = $params;
    }

    protected function checkChennel($channel_tag, $array)
    {
    	return array_key_exists($channel_tag, $array);
    }
}
