<?php

namespace App\Models;

use CodeIgniter\Model;

class PurchaseDetailModel extends Model
{
    protected $table            = 'purchase_details';
    protected $primaryKey       = 'pd_Id';
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'pd_p_Id',
        'pd_pu_Id',
        'pd_Qty'
    ];

    protected $validationRules = [
        'pd_p_Id' => 'required',
        'pd_pu_Id' => 'required',
        'pd_Qty' => 'required|is_natural_no_zero'
    ];

    protected $validationMessages = [
        'pd_p_Id' => [
            'required' => '紀念品編號為必填',
        ],
        'pd_pu_Id' => [
            'required' => '進貨編號為必填',
        ],
        'pd_Qty'=>[
            'required' => '數量為必填',
            'is_natural_no_zero'=>'數量必須為正整數'
        ]
    ];

    public function getStockGiftDetails(array $puIds): array
    {
        if (empty($puIds)) {
            return [];
        }

        $builder = $this->builder('purchase_details pd')
            ->select('
            pd.*,
            sg.sg_Id,
            sg.sg_StockCode,
            sg.sg_StockName,
            p.p_Name,
            p.p_sg_Id,
            p.p_Id
        ')
            ->join('products p', 'p.p_Id = pd.pd_p_Id')
            ->join('stockholder_gifts sg', 'sg.sg_Id = p.p_sg_Id')
            ->whereIn('pd.pd_pu_Id', $puIds);

        $results = $builder->get()->getResultArray();

        return $this->formatDetails($results);
    }

    public function findPurchaseIdsByKeyword(string $keyword): array
    {
        return $this->builder('purchase_details pd')
            ->select('pd.pd_pu_Id')
            ->join('products p', 'p.p_Id = pd.pd_p_Id')
            ->join('stockholder_gifts sg', 'sg.sg_Id = p.p_sg_Id')
            ->groupStart()
            ->like('sg.sg_StockCode', $keyword)
            ->orLike('sg.sg_StockName', $keyword)
            ->orLike('p.p_Name', $keyword)
            ->groupEnd()
            ->get()
            ->getResultArray();
    }

    private function formatDetails(array $results): array
    {
        $details = [];

        foreach ($results as $row) {
            $puId = $row['pd_pu_Id'];

            if (!isset($details[$puId])) {
                $details[$puId] = [];
            }

            $details[$puId][] = [
                'stockId' => $row['sg_Id'],
                'productId' => $row['p_Id'],
                'qty' => $row['pd_Qty']
            ];
        }

        return $details;
    }
}
