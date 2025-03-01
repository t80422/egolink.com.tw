<?php

namespace App\Libraries;

use App\Entities\Feature;
use App\Models\FeatureModel;
use App\Models\RoleFeatureModel;
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

    private $userModel;
    private $jwtSer;
    private $emailSer;
    private $featureModel;
    private $roleFeatureModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->jwtSer = new JWTService();
        $this->emailSer = new EmailService();
        $this->featureModel = new FeatureModel();
        $this->roleFeatureModel = new RoleFeatureModel();
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

    /**
     * E股投登入
     *
     * @param string $account
     * @param string $password
     * @return boolean
     */
    public function login_autoVote(string $account, string $password)
    {
        $user = $this->userModel->getByAccount($account);

        if (!$user || !$this->userModel->verifyPassword($password, $user->password)) {
            throw new Exception('帳號或密碼錯誤');
        }

        if (!$user->canAutoVote) {
            throw new Exception('未授權使用E股投');
        }
    }

    public function verifyEmail(string $token)
    {
        // 取得並驗證用戶資料
        $user = $this->userModel->getByEmailToken($token);

        if (!$user) {
            throw new Exception('此帳號已完成驗證,或連結已過期');
        }

        // 檢查連結是否過期
        if (strtotime($user->verifyExpores) > time()) {
            throw new Exception('連結已過期,請重新申請驗證信');
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

    public function requestPwdReset(string $email)
    {
        $user = $this->userModel->getByAccount($email);

        if (!$user) {
            throw new Exception('找不到此電子郵件帳號');
        }

        if (!$user->verified) {
            throw new Exception('此帳號尚未驗證');
        }

        // 生成重置的 token 和過期時間
        $user->verifyToken = bin2hex(random_bytes(32));
        $user->verifyExpires = date('Y-m-d H:i:s', strtotime('+1 hours'));

        $this->userModel->update($user->id, $user);

        $this->emailSer->sendResetPwdEmail($user->email, $user->verifyToken);
    }

    public function verifyResetToken(string $token)
    {
        $user = $this->userModel->validateResetToken($token);

        if (!$user) {
            throw new Exception('無效或已過期的重置連結,請重新申請');
        }
    }

    public function resetPassword(string $token, string $newPwd)
    {
        $user = $this->userModel->validateResetToken($token);

        if (!$user) {
            throw new Exception('無效或已過期的重置連結,請重新申請');
        }

        $this->userModel->resetPsw($user->id, $newPwd);
    }

    /**
     * 取得所有功能
     *
     * @return array
     */
    public function getAllFeatures(): array
    {
        $datas = $this->featureModel->findAll();

        return array_map(function (Feature $data) {
            return $data->formatForOption();
        }, $datas);
    }

    /**
     * 取得角色權限狀態
     *
     * @param integer $roleId
     * @return array
     */
    public function getRoleFeatures(int $roleId): array
    {
        $allFeatures = $this->featureModel->findAll();
        $roleFeatures = $this->featureModel->getFeaturesByRoleId($roleId);
        $roleFeatureIds = array_column($roleFeatures, 'id');

        $result = [];

        foreach ($allFeatures as $feature) {
            $result[] = [
                'id' => $feature->id,
                'authorized' => in_array($feature->id, $roleFeatureIds)
            ];
        }

        return $result;
    }

    /**
     * 更新角色的功能權限
     *
     * @param integer $roleId
     * @param array $featureIds
     * @return void
     */
    public function updateRoleFeatures(int $roleId, array $featureIds)
    {

        // 驗證功能Id
        $validFeatures = $this->featureModel->getByFeatureIds($featureIds);

        if (count($validFeatures) !== count($featureIds)) {
            throw new Exception('包含無效的功能Id');
        }

        // 更新權限
        $this->roleFeatureModel->updateRoleFeatures($roleId, $featureIds);
    }

    /**
     * 檢查角色是否有特定功能的權限
     *
     * @param integer $roleId
     * @param string $featureCode
     * @return boolean
     */
    public function hasFeaturePermission(int $roleId, string $featureCode): bool
    {
        return $this->featureModel->hasPermission($roleId, $featureCode);
    }
}
