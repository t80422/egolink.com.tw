<?php

namespace App\Models;

use CodeIgniter\Model;
use Exception;

class OrderModel extends Model
{
    protected $table            = 'orders';
    protected $primaryKey       = 'o_Id';
    protected $allowedFields    = [
        'o_sg_Id',
        'o_sa_Id',
        'o_StockShares',
        'o_Status',
        'o_AccountNum',
        'o_Memo'
    ];

    public const STATUS = [
        0 => '缺件',
        1 => '補件',
        2 => '逾期',
        3 => '收件',
        4 => '待出貨',
        5 => '出貨',
        6 => '複領'
    ];

    /**
     * 批次新增
     *
     * @param array $sgIds 股東會資訊編號們
     * @param array $saIds 子帳號編號們
     * @return boolean
     */
    public function batchCreate(array $sgIds, array $saIds)
    {
        $this->db->transStart();

        try {
            // 找出已存在的訂單組合
            $exitOrders = $this->whereIn('o_sg_Id', $sgIds)
                ->whereIn('o_sa_Id', $saIds)
                ->select('o_sg_Id, o_sa_Id')
                ->findAll();

            // 建立已存在組合的查找表,用於快速查詢
            $exitCombine = [];

            foreach ($exitOrders as $order) {
                $key = $order['o_sg_Id'] . '-' . $order['o_sa_Id'];
                $exitCombine[$key] = true;
            }

            // 取得需要新增的訂單資訊
            $batchData = [];

            foreach ($sgIds as $sgId) {
                foreach ($saIds as $saId) {
                    $key = $sgId . '-' . $saId;

                    if (!isset($exitCombine[$key])) {
                        $batchData[] = [
                            'o_sg_Id' => $sgId,
                            'o_sa_Id' => $saId,
                            'o_Status' => self::STATUS[0]
                        ];
                    }
                }
            }

            if (!empty($batchData)) {
                $this->insertBatch($batchData);
            }

            $this->db->transCommit();
        } catch (Exception $e) {
            $this->db->transRollback();
            throw new Exception($e);
        }
    }

    /**
     * 取得列表
     *
     * @param array $params
     * @return array
     */
    public function getList($params = []): array
    {
        $builder = $this->builder();
        $builder->select('
            orders.o_Id,
            orders.o_sg_Id,
            stockholder_gifts.sg_StockCode,
            stockholder_gifts.sg_StockName,
            stockholder_gifts.sg_MeetingDate,
            stockholder_gifts.sg_MeetingType,
            sub_accounts.sa_Name,
            orders.o_StockShares,
            orders.o_Status,
            orders.o_AccountNum,
            orders.o_Memo
        ')
            ->join('stockholder_gifts', 'stockholder_gifts.sg_Id = orders.o_sg_Id')
            ->join('sub_accounts', 'sub_accounts.sa_Id = orders.o_sa_Id');

        // 年分
        if (!empty($params['year'])) {
            $builder->where('stockholder_gifts.sg_Year', $params['year']);
        }

        // 狀態
        if (isset($params['status'])) {
            $builder->where('orders.o_Status', $params['status']);
        }

        // 繳交
        if (!empty($params['documentIds']) && is_array($params['documentIds'])) {
            $builder->join('document_combinations', 'document_combinations.dc_sg_Id = stockholder_gifts.sg_Id', 'left')
                ->join('documents', 'documents.d_Id = document_combinations.dc_d_Id', 'left')
                ->whereIn('documents.d_Id', $params['documentIds']);
        }

        // 代領截止日
        if (!empty($params['deadlineDateStart'])) {
            $builder->where('stockholder_gifts.sg_DeadlineDate >=', $params['deadlineDateStart']);
        }

        if (!empty($params['deadlineDateEnd'])) {
            $builder->where('stockholder_gifts.sg_DeadlineDate <=', $params['deadlineDateEnd']);
        }

        // 開會性質
        if (isset($params['meetingType'])) {
            $builder->where('stockholder_gifts.sg_MeetingType', $params['meetingType']);
        }

        // 市場別
        if (isset($params['marketType'])) {
            $builder->where('stockholder_gifts.sg_MarketType', $params['marketType']);
        }

        // 關鍵字
        if (!empty($params['keyword'])) {
            $builder->groupStart()
                ->like('stockholder_gifts.sg_StockName', $params['keyword'])
                ->orLike('stockholder_gifts.sg_StockCode', $params['keyword'])
                ->groupEnd();
        }

        // 排序
        $sortField = $params['sortField'] ?? 'o_Id';
        $sortOrder = $params['sortOrder'] ?? 'DESC';

        // 排序欄位定義
        $sortFieldMap = [
            'deadlineDate' => 'stockholder_gifts.sg_DeadlineDate',
            'status' => 'orders.o_Status',
            'stockCode' => 'stockholder_gifts.sg_StockCode',
            'accountName' => 'sub_accounts.sa_Name'
        ];

        if (isset($sortFieldMap[$sortField])) {
            $builder->orderBy($sortFieldMap[$sortField], $sortOrder);
        }

        // 分頁
        $page = $params['page'] ?? 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $builder->limit($limit, $offset);
        $total = $builder->countAllResults(false);
        $orders = $builder->get()->getResultArray();

        // 如果沒有訂單,直接返回空結果
        if (empty($orders)) {
            return [
                'items' => [],
                'total' => 0,
                'page' => $params['page'] ?? 1,
                'totalPages' => 0
            ];
        }

        $sgIds = array_unique(array_column($orders, 'o_sg_Id'));

        // 取得文件組合
        $docBuilder = $this->db->table('document_combinations')
            ->select('
            document_combinations.dc_sg_Id,
            document_combinations.dc_Sequence,
            documents.d_Name
        ')
            ->join('documents', 'documents.d_Id = document_combinations.dc_d_Id')
            ->whereIn('document_combinations.dc_sg_Id', $sgIds)
            ->orderBy('document_combinations.dc_Sequence', 'ASC');

        $docResults = $docBuilder->get()->getResultArray();

        // 整理文件資料
        $docMap = [];

        foreach ($docResults as $doc) {
            $sgId = $doc['dc_sg_Id'];
            $sequence = $doc['dc_Sequence'];

            if (!isset($docMap[$sgId][$sequence])) {
                $docMap[$sgId][$sequence] = [
                    'seq' => $sequence,
                    'docs' => []
                ];
            }
            $docMap[$sgId][$sequence]['docs'][] = $doc['d_Name'];
        }

        // 組合全部資料
        $processOrders = [];

        foreach ($orders as $order) {
            $sgId = $order['o_sg_Id'];
            $orderData = [
                'stockCode' => $order['sg_StockCode'],
                'stockName' => $order['sg_StockName'],
                'meetingType' => StockholderGiftsModel::CODE_TABLES['meetingType'][$order['sg_MeetingType']],
                'name' => $order['sa_Name'],
                'stockShares' => $order['o_StockShares'],
                'status' => self::STATUS[$order['o_Status']],
                'accountNum' => $order['o_AccountNum'],
                'memo' => $order['o_Memo'],
                'docCombine' => isset($docMap[$sgId])
                    ? array_values($docMap[$sgId])
                    : []
            ];
            $processOrders[] = $orderData;
        }

        return [
            'items' => $processOrders,
            'total' => $total,
            'page' => $page,
            'totalPages' => ceil($total / $limit)
        ];
    }

    public function getDetail(int $id): string
    {
        $data = $this->select('o_Memo')
            ->where('o_Id', $id)
            ->first();

        return $data['o_Memo'] ?? '';
    }
}
