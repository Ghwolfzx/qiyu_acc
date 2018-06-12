<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Account;
use App\Models\Acclogin;

class CheckLink extends Command
{
    protected $signature = 'qiyu:login_check';
    protected $description = '奇遇用户登录检测';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        if ($this->confirm('是否处理全部用户？')) {
            $this->info('处理所有用户');
            $users = Account::all();
        } else {
            $userId = $this->ask('输入用户 id');
            $this->info('单独处理：' . $userId);
            $users = Account::where('id', $userId)->get();
        }

        $bar = $this->output->createProgressBar(count($users));
        foreach ($users as $user) {
            $result = Acclogin::handleUserLoginLog($user);
            $this->info('用户 ' . $user->id . ' 处理结果：' . json_encode($result));
            $bar->advance();
        }
        $bar->finish();
    }
}
