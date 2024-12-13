<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    // 指定資料表名稱
    protected $table = 'users';

    // 指定主鍵
    protected $primaryKey = 'u_Id';

    // 允許批量賦值欄位
    protected $allowedFields = [
        'u_Name',
        'u_Password',
        'u_r_Id',
        'u_Account',
        'u_Verified',
        'u_VerifyToken',
        'u_VerifyExpires',
        'u_l_Id',
        'u_Phone',
        'u_PostalCode',
        'u_Address'
    ];

    // 新增前動作
    protected $beforeInsert = ['hashPassword', 'generateVerifyToken'];  // 新增這一行在類別屬性中

    /**
     * 雜湊密碼
     *
     * @param array $data
     * @return void
     */
    protected function hashPassword(array $data)
    {
        $data['data']['u_Password'] = password_hash(
            $data['data']['u_Password'],
            PASSWORD_BCRYPT
        );

        return $data;
    }

    protected function generateVerifyToken(array $data)
    {
        $verifyData = $this->generateNewVerification();
        $data['data'] = array_merge($data['data'], $verifyData);

        return $data;
    }

    /**
     * 生成新的驗證資訊
     *
     * @return array
     */
    public function generateNewVerification(): array
    {
        return [
            'u_VerifyToken' => bin2hex(random_bytes(32)),
            'u_VerifyExpires' => date('Y-m-d H:i:s', strtotime('+1 hours')),
            'u_Verified' => 0
        ];
    }

    /**
     * 檢查帳號是否存在
     *
     * @param string $account
     * @return boolean
     */
    public function isAccountExist(string $account): bool
    {
        return $this->where('u_Account', $account)->countAllResults() > 0;
    }

    /**
     * 驗證密碼
     *
     * @param string $password
     * @param string $hash
     * @return boolean
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public function getUser($userId)
    {
        return $this->select('users.*,roles.r_Name')
            ->join('roles', 'roles.r_Id=users.u_r_Id')
            ->find($userId);
    }

    public function getByEmailToken(string $token)
    {
        return $this->where('u_VerifyToken', $token)
            ->where('u_VerifyExpires >', date('Y-m-d H:i:s'))
            ->first();
    }

    public function getByAccountWithoutVerify(string $account)
    {
        return $this->where('u_Account', $account)
            ->where('u_Verified', 0)
            ->first();
    }

    public function clearupVerificationTokens(): bool
    {
        // 開始交易
        $this->db->transStart();

        // 清理已過期的驗證碼
        $this->where('u_VerifyExpires <', date('Y-m-d H:i:s'))
            ->where('u_Verified', 0)
            ->set([
                'u_VerifyToken' => null,
                'u_VerifyExpires' => null
            ])
            ->update();

        // 清理已驗證用戶的驗證資訊
        $this->where('u_Verified', 1)
            ->set([
                'u_VerifyToken' => null,
                'u_VerifyExpires' => null
            ])
            ->update();

        // 完成交易
        $this->db->transComplete();

        log_message('info', '驗證碼清理完成: {time}', [
            'time' => date('Y-m-d H:i:s')
        ]);

        return $this->db->transStatus();
    }

    public function getByAccount(string $account)
    {
        return $this->where('u_Account', $account)
            ->select('users.*,roles.r_Name')
            ->join('roles', 'roles.r_Id = users.u_r_Id')
            ->first();
    }
}
