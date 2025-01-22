<?php

namespace App\Controllers\Api;

use App\Libraries\AuthService;
use Exception;

/**
 * 認證
 */
class AuthController extends BaseApiController
{
    private $authSer;

    public function __construct()
    {
        $this->authSer = new AuthService();
    }

    // 登入
    public function login()
    {
        try {
            $account = $this->request->getVar('email');
            $password = $this->request->getVar('password');

            if (empty($account) || empty($password)) {
                return $this->errorResponse('請輸入帳號和密碼');
            }

            $result = $this->authSer->login($account, $password);

            return $this->successResponse('', $result);
        } catch (\Exception $e) {
            return $this->errorResponse('登入時發生錯誤', $e);
        }
    }

    // 驗證電子郵件
    public function verifyEmail($token)
    {
        try {
            if (empty($token)) {
                return $this->errorResponse('無效的驗證碼');
            }

            $this->authSer->verifyEmail($token);

            return $this->successResponse();
        } catch (\Exception $e) {

            return $this->errorResponse('驗證郵件時發生錯誤', $e);
        }
    }

    // 重新發送郵件
    public function resendVerification()
    {
        try {
            $email = $this->request->getVar('email');

            if (empty($email)) {
                return $this->errorResponse('請提供電子郵件');
            }

            $this->authSer->resendVerification($email);

            return $this->successResponse('驗證郵件已重新發送,請檢查您的信箱');
        } catch (\Exception $e) {
            return $this->errorResponse('重新發送郵件時發生錯誤', $e);
        }
    }

    // 請求重置密碼
    public function requestPasswordReset()
    {
        try {
            $email = $this->request->getVar('email');

            if (empty($email)) {
                return $this->errorResponse('請提供電子郵件');
            }

            $this->authSer->requestPwdReset($email);

            return $this->successResponse();
        } catch (Exception $e) {
            return $this->errorResponse('請求重置密碼時發生錯誤', $e);
        }
    }

    // 驗證重置令牌
    public function verifyResetToken($token)
    {
        try {
            if (empty($token)) {
                return $this->errorResponse('未提供token');
            }

            $this->authSer->verifyResetToken($token);

            return $this->successResponse();
        } catch (Exception $e) {
            return $this->errorResponse('驗證重置令牌時發生錯誤', $e);
        }
    }

    // 重置密碼
    public function resetPassword($token)
    {
        try {
            $password = $this->request->getVar('password');

            if (empty($password)) {
                return $this->errorResponse('請提供密碼');
            }

            $this->authSer->resetPassword($token, $password);

            return $this->successResponse();
        } catch (Exception $e) {
            return $this->errorResponse('重置密碼時發生錯誤');
        }
    }
}
