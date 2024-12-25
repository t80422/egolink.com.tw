<?php

namespace App\Controllers\Api;

use App\Libraries\EmailService;
use App\Models\UserModel;
use App\Libraries\JWTService;

/**
 * 認證
 */
class AuthController extends BaseApiController
{
    protected $userModel;
    protected $jwtService;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->jwtService = new JWTService;
    }

    // 登入
    public function login()
    {
        try {
            $account = $this->request->getVar('email');
            $password = $this->request->getVar('password');
            $user = $this->userModel->getByAccount($account);

            // 驗證使用者存在和密碼
            if (!$user || !$this->userModel->verifyPassword($password, $user['u_Password'])) {
                return $this->errorResponse('帳號或密碼錯誤', null,);
            }

            // 檢查帳號是否已驗證
            if (!$user['u_Verified']) {
                return $this->errorResponse('信箱尚未驗證,請重新申請驗證信');
            }

            // 生成 JWT Token
            $token = $this->jwtService->createToken($user);

            $data = [
                'token' => $token,
                'user' => [
                    'id' => $user['u_Id'],
                    'name' => $user['u_Name'],
                    'roleId' => $user['u_r_Id'],
                    'email' => $user['u_Account']
                ]
            ];

            return $this->successResponse('登入成功', $data);
        } catch (\Exception $e) {
            return $this->errorResponse('登入時發生錯誤', $e);
        }
    }

    // 驗證電子郵件
    public function verifyEmail($token)
    {
        try {
            $user = $this->userModel->getByEmailToken($token);

            if (!$user) {
                return $this->errorResponse('無效或已過期的驗證連結,請重新申請驗證信');
            }

            // 更新使用者狀態
            $this->userModel->update(
                $user['u_Id'],
                [
                    'u_Verified' => 1,
                    'u_VerifyToken' => null,
                    'u_VerifyExpires' => null
                ]
            );

            return $this->successResponse('電子郵件驗證成功');
        } catch (\Exception $e) {

            return $this->errorResponse('驗證郵件時發生錯誤', $e);
        }
    }

    // 重新發送郵件
    public function resendVerification()
    {
        try {
            $account = $this->request->getVar('email');
            $user = $this->userModel->getByAccountWithoutVerify($account);

            if (!$user) {
                return $this->errorResponse('找不到未驗證帳號');
            }

            // 更新驗證資訊
            $verifyData = $this->userModel->generateNewVerification();
            $this->userModel->update($user['u_Id'], $verifyData);

            // 發送新的驗證郵件
            $emailSer = new EmailService;
            $emailSer->sendVerificationEmail($user['u_Account'], $verifyData['u_VerifyToken']);

            return $this->successResponse('驗證郵件已重新發送,請檢查您的信箱');
        } catch (\Exception $e) {
            log_message('error', '[重新發送郵件錯誤] {error}', [
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse('重新發送郵件時發生錯誤', $e);
        }
    }
}
