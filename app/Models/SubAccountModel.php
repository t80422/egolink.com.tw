<?php

namespace App\Models;

use CodeIgniter\Model;

class SubAccountModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'sub_accounts';
    protected $primaryKey       = 'sa_Id';
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'sa_Id',
        'sa_IdCardNum',
        'sa_Name',
        'sa_Memo',
        'sa_IdCardImg_F',
        'sa_IdCardImg_B',
        'sa_DLImg',
        'sa_HICImg',
        'sa_IdCard',
        'sa_DrvingLicense',
        'sa_HIC',
        'sa_HRT',
        'sa_HC',
        'sa_BOOV',
        'sa_CDC',
        'sa_u_Id',
        'sa_VoucherType'
    ];

    public function getList(int $userId, $params = [])
    {
        $builder = $this->builder();

        $builder->select('
            sub_accounts.*,
            users.u_Name
        ')
            ->join('users', 'users.u_Id = sub_accounts.sa_u_Id')
            ->where('sub_accounts.sa_u_Id', $userId);
        $builder->where('sa_u_Id', $userId);

        // 搜尋
        if (!empty($params['keyword'])) {
            $keyword = $params['keyword'];

            $builder->groupStart()
                ->like('sa_Name', $keyword)
                ->orLike('sa_IdCardNum', $keyword)
                ->groupEnd();
        }

        // 排序
        $sortField = $params['sortField'] ?? 'sa_Id';
        $sortOrder = $params['sortOrder'] ?? 'DESC';

        // 允許排序的欄位
        $allowedSortFields = [
            'name' => 'sa_Name',
            'idCardNum' => 'sa_IdCardNum',
            'id' => 'sa_Id'
        ];

        if (isset($allowedSortFields[$sortField])) {
            $builder->orderBy($allowedSortFields[$sortField], $sortOrder);
        }

        $total = $builder->countAllResults(false);

        // 分頁
        $page = empty($params['page']) ? 1 : (int)$params['page'];
        $limit = 20;
        $offset = ($page - 1) * $limit;
        $items = $builder->limit($limit, $offset)
            ->get()
            ->getResultArray();

        return [
            'total' => $total,
            'page' => $page,
            'totalPages' => ceil($total / $limit),
            'items' => $items
        ];
    }
}
