<?php

namespace App\Models;

use App\Entities\InventoryLog;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Model;

class InventoryLogModel extends Model
{
    protected $table            = 'inventory_logs';
    protected $primaryKey       = 'il_Id';
    protected $returnType       = InventoryLog::class;
    protected $allowedFields    = [
        'il_p_Id',
        'il_Type',
        'il_Qty',
        'il_BeforeQty',
        'il_CreatedBy',
        'il_Memo'
    ];

    protected $validationRules = [
        'il_Qty' => 'required|is_natural',
        'il_p_Id' => 'required'
    ];

    protected $validationMessages = [
        'il_Qty' => [
            'required' => '數量為必填',
            'is_natural' => '數量必須為正整數'
        ],
        'il_p_Id' => [
            'required' => '紀念品編號為必填'
        ]
    ];

    public function getList(array $params = []): array
    {
        $builder = $this->createBaseBuilder();
        $this->applyFilter($builder, $params);

        $total = $builder->countAllResults(false);

        $page = empty($params['page']) ? 1 : $params['page'];
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $items = $builder->limit($limit, $offset)->get()->getResult();

        return [
            'page' => $page,
            'total' => $total,
            'totalPages' => ceil($total / $limit),
            'items' => $items
        ];
    }

    private function createBaseBuilder()
    {
        return $this->builder('inventory_logs il')->select('
            il.*,
            p_Name as productName,
            sg_StockCode as stockCode,
            sg_StockName as stockName,
            u_Name as userName
        ')
            ->join('products p', 'p.p_Id = il.il_p_Id')
            ->join('stockholder_gifts sg', 'sg.sg_Id = p.p_sg_Id')
            ->join('users u', 'u.u_Id = il.il_CreatedBy');
    }

    private function applyFilter(BaseBuilder $builder, array $params)
    {
        // 異動類型
        if (!empty($params['type'])) {
            $builder->where('il_Type', $params['type']);
        }

        // 關鍵字
        if (!empty($params['keyword'])) {
            $keyword = $params['keyword'];
            $builder->groupStart()
                ->like('u_Name', $keyword)
                ->orLike('sg_StockCode', $keyword)
                ->orLike('sg_StockName', $keyword)
                ->orLike('p_Name', $keyword)
                ->groupEnd();
        }

        // 異動時間
        if (!empty($params['startDate'])) {
            $builder->where('il_CreatedAt >=', $params['startDate']);
        }

        if (!empty($params['endDate'])) {
            $builder->where('il_CreatedAt <=', $params['endDate']);
        }
    }
}
