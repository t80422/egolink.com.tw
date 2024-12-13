<?php

namespace App\Commands;

use App\Models\UserModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CleanupVerification extends BaseCommand
{
    // 命令的群組名稱
    protected $group='Maintenance';

    // 命令名稱
    protected $name='verification::cleanup';

    // 命令的簡短描述
    protected $description='清理過期和已驗證用戶的驗證碼';

    // 命令的使用說明
    protected $usage='verification::cleanup';

    // 命令的參數列表
    protected $arguments=[];

    // 命令的選項列表
    protected $options=[];

    public function run(array $params)
    {
        $userModel=new UserModel();

        CLI::write('開始清理驗證碼...','yellow');

        if($userModel->cleanupVerificationTokens()){
            CLI::write('清理完成!','green');
        }else{
            CLI::write('清理過程中發生錯誤','red');
        }
    }
}
