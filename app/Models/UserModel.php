<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\User;
use CodeIgniter\Database\BaseBuilder;

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
        'u_Address',
        'u_ParentId'
    ];

    // 新增前動作
    protected $beforeInsert = ['hashPassword', 'generateVerifyToken'];  // 新增這一行在類別屬性中

    protected $returnType = User::class;

    public const ROLE_ADMIN = 1;
    public const ROLE_LOCATION = 2;
    public const ROLE_GROUP = 3;
    public const ROLE_NOMAL = 4;

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

    public function getByAccount(string $account)
    {
        return $this->where('u_Account', $account)
            ->select('users.*,roles.r_Name')
            ->join('roles', 'roles.r_Id = users.u_r_Id')
            ->first();
    }

    public function getList(array $params = []): array
    {
        $builder = $this->createBaseBuilder();
        $this->applyFilters($builder, $params);
        $builder->orderBy('u_Name', 'ASC');

        $total = $builder->countAllResults(false);
        $page = $params['page'] ?? 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $users = $builder->limit($limit, $offset)->get()->getResult($this->returnType);

        return [
            'page' => $page,
            'totalPages' => ceil($total / $limit),
            'total' => $total,
            'items' => array_map(fn(User $user) => $user->formatForList(), $users)
        ];
    }

    public function markEmailAsVerified($userId)
    {
        $this->update($userId, [
            'u_Verified' => 1,
            'u_VerifyToken' => null,
            'u_VerifyExpires' => null
        ]);
    }

    public function getGroupAccountOptionsByLocationId($locationId)
    {
        return $this->select('
            u.*,
            l.l_Name as locationName,
        ')
            ->from('users u')
            ->join('locations l', 'l.l_Id = u.u_l_id')
            ->where([
                'u.u_l_Id' => $locationId,
                'u.u_r_Id' => self::ROLE_GROUP,
                'u.u_Verified' => 1
            ])
            ->orderBy('u.u_Name', 'ASC')
            ->findAll();
    }

    public function getDetail($userId):?User
    {
        $builder = $this->createBaseBuilder();
        return $builder->where('u.u_Id', $userId)
            ->get()
            ->getFirstRow($this->returnType);
    }

    private function createBaseBuilder(): BaseBuilder
    {
        return $this->builder('users u')
            ->select('
            u.*,
            r.r_Name as roleName,
            l.l_Name as locationName,
            parent.u_Name as parentName
        ')
            ->join('roles r', 'r.r_Id = u.u_r_Id')
            ->join('locations l', 'l.l_Id = u.u_l_Id', 'left')
            ->join('users as parent', 'parent.u_Id = u.u_ParentId', 'left');
    }

    private function applyFilters(BaseBuilder $builder, array $params)
    {
        // 角色
        if (!empty($params['roleId'])) {
            $builder->where('u.u_r_Id', $params['roleId']);
        }

        // 據點
        if (!empty($params['locationId'])) {
            $builder->where('u.u_l_Id', $params['locationId']);
        }

        // 關鍵字
        if (!empty($params['keyword'])) {
            $builder->groupStart()
                ->like('u.u_Name', $params['keyword'])
                ->orLike('u.u_Phone', $params['keyword'])
                ->groupEnd();
        }
    }
}
