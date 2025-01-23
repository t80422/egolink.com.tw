<?php

namespace App\Models;

use App\Entities\SubAccount;
use CodeIgniter\Model;

class SubAccountModel extends Model
{
    protected $table            = 'sub_accounts';
    protected $primaryKey       = 'sa_Id';
    protected $returnType       = SubAccount::class;
    protected $allowedFields    = [
        'sa_Name',
        'sa_Memo',
        'sa_IdCard',
        'sa_DrivingLicense',
        'sa_HIC',
        'sa_HRT',
        'sa_HC',
        'sa_BOOV',
        'sa_CDC',
        'sa_u_Id',
        'sa_VoucherType'
    ];

    protected $validationRules = [
        'sa_Name' => 'required',
        'sa_IdCardNum' => 'required|exact_length[10]|regex_match[/^[A-Z][1-2][0-9]{8}$/]',
        'sa_u_Id' => 'required|is_natural_no_zero'
    ];

    protected $validationMessages = [
        'sa_Name' => [
            'required' => '姓名為必填',
        ],
        'sa_IdCardNum' => [
            'required' => '身份證字號為必填',
            'exact_length' => '身份證字號必須為10碼',
            'regex_match' => '身分證字號格式不正確'
        ],
        'sa_u_Id' => [
            'required' => '所屬用戶為必填',
            'is_natural_no_zero' => '所屬用戶ID必須為正整數'
        ]
    ];

    public function getList(int $userId, $params = [])
    {
        $builder = $this->builder('sub_accounts sa');

        $builder->select('
            sa.*,
            users.u_Name as ownerName
        ')
            ->join('users', 'users.u_Id = sa.sa_u_Id')
            ->where('sa.sa_u_Id', $userId)
            ->orderBy('sa.sa_Name');

        // 搜尋
        if (!empty($params['keyword'])) {
            $keyword = $params['keyword'];
            $builder->groupStart()
                ->like('sa_Name', $keyword)
                ->orLike('sa_IdCardNum', $keyword)
                ->groupEnd();
        }

        $total = $builder->countAllResults(false);

        // 分頁
        $page = $params['page'] ?? 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        $items = $builder->limit($limit, $offset)
            ->get()
            ->getResult($this->returnType);

        return [
            'total' => $total,
            'page' => $page,
            'totalPages' => ceil($total / $limit),
            'items' => $items
        ];
    }

    public function isIdCardNumExists(string $idCardNum): bool
    {
        $builder = $this->where('sa_IdCardNum', $idCardNum);

        return $builder->countAllResults() > 0;
    }

    public function getIdsByUserId(int $userId): array
    {
        return $this->where('sa_u_Id', $userId)
            ->select('sa_Id')
            ->findAll();
    }
}
