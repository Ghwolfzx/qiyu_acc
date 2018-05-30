<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/', function () { return 'hellp';});
Route::group([
	'namespace' => 'Api',
    'middleware' => ['checkparams', 'web'],
], function() {
	// 系统弹板，公告及强更
	Route::get('systemNotice', 'SystemNoticeController@show');

	// 测试账号自动登录
	Route::get('userTestLogin', 'UserChannelLogin@index');

	// 账号登录
	Route::get('userChannelLogin', 'UserChannelLogin@login');

	// 服务器选择
	Route::get('userSelectGameServer', 'UserSelectGameServer@index');

	// 新增服务器
	Route::get('refreshServer', 'RefreshCache@refresh');
});
