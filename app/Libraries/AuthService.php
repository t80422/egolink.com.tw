<?php

namespace App\Libraries;

use App\Models\UserModel;
use Exception;

class AuthService
{
    /**
     * 當前認證的用戶
     *
     * @var object|null
     */
    private $currentUser;

    protected $userModel;
    protected $jwtSer;
    protected $emailSer;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->jwtSer = new JWTService();
        $this->emailSer = new EmailService();
    }

    public function setUser(object $user)
    {
        $this->currentUser = $user;
    }

    public function getUser(): ?object
    {
        return $this->currentUser;
    }

    public function login(string $account, string $password): array
    {
        $user = $this->userModel->getByAccount($account);

        if (!$user || !$this->userModel->verifyPassword($password, $user->password)) {
            throw new Exception('帳號或密碼錯誤');
        }

        if (!$user->verified) {
            throw new Exception('信箱尚未驗證');
        }

        // 生成 JWT Token
        $token = $this->jwtSer->createToken([
            'id' => $user->id,
            'roleId' => $user->roleId
        ]);

        return [
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'roleId' => $user->roleId,
                'email' => $user->email
            ]
        ];
    }

    public function verifyEmail(string $token)
    {
        $user = $this->userModel->getByEmailToken($token);

        if (!$user) {
            throw new Exception('無效或已過期的驗證連結,請重新申請驗證信');
        }

        $this->userModel->markEmailAsVerified($user->id);
    }

    public function resendVerification(string $email)
    {
        $user = $this->userModel->getByAccountWithoutVerify($email);

        if (!$user) {
            throw new Exception('找不到該電子郵件');
        }

        // 更新驗證資訊
        $verifyData = $this->userModel->generateNewVerification();
        $this->userModel->update($user->id, $verifyData);

        // 發送新的驗證郵件
        $this->emailSer->sendVerificationEmail($user->email, $verifyData['u_VerifyToken']);
    }

    public function validateToken(string $token): object
    {
        $payload = $this->jwtSer->validateToken($token);

        $user = $this->userModel->find($payload->id);

        if (!$user) {
            throw new Exception('找不到用戶');
        }

        if (!$user->verified) {
            throw new Exception('用戶尚未驗證');
        }

        $userObj = (object)[
            'id' => $payload->id,
            'role' => $payload->role
        ];

        $this->setUser($userObj);

        return $userObj;
    }
}
