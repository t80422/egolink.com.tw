<?php

namespace App\Models;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Model;
use Exception;

class PurchaseModel extends Model
{
    protected $table            = 'purchases';
    protected $primaryKey       = 'pu_Id';
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'pu_Date',
        'pu_Memo'
    ];

    protected $pdModel;

    public function __construct()
    {
        parent::__construct();
        $this->pdModel = new PurchaseDetailModel();
    }

    public function getList(array $params = []): array
    {
        $builder = $this->applyFilters($params);

        // 取得總筆數
        $total = $builder->countAllResults(false);

        // 處理分頁
        $page = $params['page'] ?? 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        $items = $builder->orderBy('pu_Date', 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();

        // 取得進貨明細資訊
        $puIds = array_column($items, 'pu_Id');
        $details = $this->pdModel->getStockGiftDetails($puIds);

        return [
            'page' => $page,
            'totalPages' => ceil($total / $limit),
            'total' => $total,
            'items' => $this->formatList($items, $details)
        ];
    }

    public function getDetail($id): ?array
    {
        $purchase = $this->find($id);

        if (!$purchase) {
            return null;
        }

        $details = $this->pdModel->getStockGiftDetails([$id]);

        return [
            'id' => $id,
            'date' => $purchase['pu_Date'],
            'memo' => $purchase['pu_Memo'],
            'details' => $details[$id] ?? []
        ];
    }

    public function updatePurchase(int $id, array $data, array $details): void
    {
        $this->db->transStart();

        try {
            $this->update($id, [
                'pu_Date' => $data['date'],
                'pu_Memo' => $data['memo']
            ]);

            // 刪除原有明細
            $this->pdModel->where('pd_pu_Id', $id)->delete();

            // 新增新的明細
            $detailsData = array_map(function ($detail) use ($id) {
                return [
                    'pd_p_Id' => $detail['productId'],
                    'pd_pu_Id' => $id,
                    'pd_Qty' => $detail['qty']
                ];
            }, $details);

            $this->pdModel->insertBatch($detailsData);

            $this->db->transComplete();
        } catch (Exception $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    public function deletePurchase(int $id)
    {
        $this->db->transStart();

        try {
            $this->pdModel->where('pd_pu_Id', $id)->delete();
            $this->delete($id);
            $this->db->transComplete();
        } catch (Exception $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    /**
     * 篩選條件
     *
     * @param array $params
     * @return BaseBuilder
     */
    private function applyFilters(array $params): BaseBuilder
    {
        $builder = $this->builder();

        // 日期區間
        if (!empty($params['startDate'])) {
            $builder->where('pu_Date >=', $params['startDate']);
        }

        if (!empty($params['endDate'])) {
            $builder->where('pu_Date <=', $params['endDate']);
        }

        return $builder;
    }

    private function formatList(array $items, array $details): array
    {
        return array_map(function ($item) use ($details) {
            $puId = $item['pu_Id'];

            return [
                'id' => $puId,
                'date' => $item['pu_Date'],
                'memo' => $item['pu_Memo'],
                'details' => $details[$puId] ?? []
            ];
        }, $items);
    }
}
