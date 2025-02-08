<?php

namespace App\Models;

use App\Entities\Order;
use App\Entities\StockholderGift;
use CodeIgniter\Model;
use Exception;

class OrderModel extends Model
{
    protected $table            = 'orders';
    protected $primaryKey       = 'o_Id';
    protected $returnType       = Order::class;
    protected $allowedFields    = [
        'o_sg_Id',
        'o_sa_Id',
        'o_Status',
        'o_AccountNum',
        'o_Memo',
        'o_s_Id',
        'o_Date',
        'o_VoteImg',
        'o_VoteImgUploadTime'
    ];

    public const STATUS = [
        0 => '缺件',
        1 => '逾期',
        2 => '待出貨',
        3 => '出貨'
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
            $exitOrders = $this->builder()
                ->whereIn('o_sg_Id', $sgIds)
                ->whereIn('o_sa_Id', $saIds)
                ->select('o_sg_Id, o_sa_Id')
                ->get()
                ->getResultArray();

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
                            'o_Status' => Order::STATUS_INCOMPLETE
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
            orders.o_Status,
            orders.o_AccountNum,
            orders.o_Memo
        ')
            ->join('stockholder_gifts', 'stockholder_gifts.sg_Id = orders.o_sg_Id')
            ->join('sub_accounts', 'sub_accounts.sa_Id = orders.o_sa_Id');

        // 年分
        if (!empty($params['year'])) {
            $startDate = $params['year'] . '-01-01';
            $endDate = $params['year'] . '-12-31';
            $builder->where('sg_MeetingDate >=', $startDate)
                ->where('sg_MeetingDate <=', $endDate);
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

        // 分頁
        $page = empty($params['page']) ? 1 : $params['page'];
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
                'o_Id' => $order['o_Id'],
                'stockCode' => $order['sg_StockCode'],
                'stockName' => $order['sg_StockName'],
                'meetingType' => StockholderGift::CODE_TABLES['meetingType'][$order['sg_MeetingType']],
                'name' => $order['sa_Name'],
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
            'total' => $total,
            'page' => $page,
            'totalPages' => ceil($total / $limit),
            'items' => $processOrders
        ];
    }

    public function getDetail(int $id): string
    {
        $data = $this->select('o_Memo')
            ->where('o_Id', $id)
            ->first();

        return $data->memo ?? '';
    }

    /**
     * 更新訂單為已出貨
     *
     * @param array $orderIds
     * @param integer $shipmentId
     * @return void
     */
    public function updatedToShipped(array $orderIds, int $shipmentId): bool
    {
        if (empty($orderIds)) {
            throw new Exception('沒有可出貨的訂單');
        }

        return $this->whereIn('o_Id', $orderIds)
            ->set([
                'o_Status' => 3,
                'o_s_Id' => $shipmentId
            ])
            ->update();
    }

    /**
     * 取得指定用戶可出貨訂單
     *
     * @param integer $userId
     * @return array 可出貨訂單ID陣列
     */
    public function getShippableOrderIdsByUserId(int $userId): array
    {
        if (!$userId) {
            throw new Exception('未提供 UserId');
        }

        return $this->select('orders.o_Id')
            ->join('sub_accounts sa', 'sa.sa_Id = orders.o_sa_Id')
            ->where([
                'sa.sa_u_Id' => $userId,
                'orders.o_Status' => Order::STATUS_PENDING
            ])
            ->where('orders.o_s_Id IS NULL')
            ->orderBy('orders.o_Date', 'ASC')
            ->findColumn('o_Id') ?? [];
    }

    /**
     * 將取消出貨的委託轉回待出貨
     *
     * @param string $shipmentId 出貨編號
     * @return void
     */
    public function backToPending($shipmentId)
    {
        if (empty($shipmentId)) {
            throw new Exception('未提供 shipmentId');
        }

        $this->where('o_s_Id', $shipmentId)
            ->set('o_Status', Order::STATUS_PENDING)
            ->update();
    }

    public function getShippableUsers(array $params = []): array
    {
        $builder = $this->db->table('users u')
            ->select('DISTINCT u.u_Id, u.u_Name, u.u_Phone, l.l_Name', false)
            ->join('sub_accounts sa', 'sa.sa_u_Id = u.u_Id')
            ->join('orders o', 'o.o_sa_Id = sa.sa_Id')
            ->join('stockholder_gifts sg', 'sg.sg_Id = o.o_sg_Id')
            ->join('products p', 'p.p_sg_Id = sg.sg_Id')
            ->join('locations l', 'l.l_Id = u.u_l_Id', 'left')
            ->where('o.o_Status', Order::STATUS_PENDING)
            ->where('(p.p_InboundQty - p.p_OutboundQty) >', 0);

        // 關鍵字
        if (!empty($params['keyword'])) {
            $keyword = $params['keyword'];
            $builder->groupStart()
                ->like('u.u_Name', $keyword)
                ->orLike('sg.sg_StockCode', $keyword)
                ->orLike('sg.sg_StockName', $keyword)
                ->groupEnd();
        }

        // 日期
        if (!empty($params['startDate'])) {
            $builder->where('o.o_Date >=', $params['startDate']);
        }

        if (!empty($params['endDate'])) {
            $builder->where('o.o_Date <=', $params['endDate']);
        }

        $total = $builder->countAllResults(false);
        $page = empty($params['page']) ? 1 : (int)$params['page'];
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

    public function getProductSummaryByUserId(int $userId): array
    {
        return $this->builder('orders o')
            ->select('
            sg.sg_StockCode,
            sg.sg_StockName,
            p.p_Name as productName,
            COUNT(*) as qty
        ')
            ->join('sub_accounts sa', 'sa.sa_Id = o.o_sa_Id')
            ->join('stockholder_gifts sg', 'sg.sg_Id = o.o_sg_Id')
            ->join('products p', 'p.p_sg_Id = sg.sg_Id')
            ->where([
                'sa.sa_u_Id' => $userId,
                'o.o_Status' => 2
            ])
            ->groupBy('sg.sg_Id, p.p_Id')
            ->orderBy('sg.sg_StockCode')
            ->get()
            ->getResultArray();
    }
}
