<?php

namespace App\Libraries;

use Exception;

class EmailService
{
    private $email;
    private $frontendBaseUrl;

    public function __construct()
    {
        $this->email = \Config\Services::email();
        $this->frontendBaseUrl=getenv('frontend.baseURL');

        if(empty($this->frontendBaseUrl)){
            throw new Exception('未設置前端網址');
        }

        // 配置郵件設定
        $this->email->initialize([
            'protocol' => getenv('email.protocol'),
            'SMTPHost' => getenv('email.SMTPHost'),
            'SMTPUser' => getenv('email.SMTPUser'),
            'SMTPPass' => getenv('email.SMTPPass'),
            'SMTPPort' => (int)getenv('email.SMTPPort'),
            'SMTPCrypto' => getenv('email.SMTPCrypto'),
            'mailType' => 'html',
            'charset' => 'utf-8',
            'wordWrap' => true
        ]);
    }

    public function sendVerificationEmail($to, $token)
    {
        $verifyLink = rtrim($this->frontendBaseUrl,'/') . "/api/verify-email/{$token}";

        $message = "
            <h2>電子郵件驗證</h2>
            <p>請點擊下方連結驗證您電子郵件:</p>
            <p><a href='{$verifyLink}'>{$verifyLink}</a></p>
            <p>此連結將在1小時候失效</p>
        ";

        try {
            $this->email->setFrom(
                getenv('email.SMTPUser'),
                getenv('email.fromName')
            );
            $this->email->setTo($to);
            $this->email->setSubject('電子郵件驗證');
            $this->email->setMessage($message);

            return $this->email->send();
        } catch (\Exception $e) {
            log_message('error', '郵件發送異常: {error}', [
                'error' => $e->getMessage()
            ]);
        }
    }

    public function sendResetPwdEmail($to, $token)
    {
        $resetLink = site_url("/auth/reset-password/{$token}");
        $message = "
            <h2>重置密碼</h2>
            <p>請點擊下方連結重置您的密碼:</p>
            <p><a href='{$resetLink}'>{$resetLink}</a></p>
            <p>此連結將在1小時候失效</p>
        ";

        try {
            $this->email->setFrom(
                getenv('email.SMTPUser'),
                getenv('email.formName')
            );
            $this->email->setTo($to);
            $this->email->setSubject('重置密碼');
            $this->email->setMessage($message);

            return $this->email->send();
        } catch (Exception $e) {
            log_message('error', '郵件發送異常:{error}', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
