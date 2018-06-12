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
        if ($this->confirm('是否处理全部用户？', 'y', 'n')) {
            $this->info('处理所有用户');
            $users = Account::orderBy('id', 'desc')->limit(10000);
            if (cache('deal_user')) {
                $users->where('id', '<', Cache('deal_user'));
            }
            $users = $users->pluck('id')->toArray();
        } else {
            $userId = $this->ask('输入用户 id');
            $this->info('单独处理：' . $userId);
            $users = Account::where('id', $userId)->pluck('id');
        }

        $count = count($users);
        $bar = $this->output->createProgressBar($count);
        foreach ($users as $user) {
            $result = Acclogin::handleUserLoginLog($user);
            $bar->advance();
            $this->info('用户 ' . $user . ' 处理结果：' . json_encode($result));
        }
        if ($count > 0) {
            $last = end($users);
            cache(['deal_user' => $last], 5);
        }
        $bar->finish();
    }
}
