<?php

namespace App\Models;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Model;

/**
 * 股東會資訊
 */
class StockholderGiftsModel extends Model
{
    public const CODE_TABLES = [
        'meetingType' => [
            '0' => '臨時',
            '1' => '常會'
        ],
        'marketType' => [
            '0' => '上市',
            '1' => '上櫃',
            '2' => '興櫃',
            '3' => '公開發行'
        ],
        'giftStatus' => [
            '0' => '無發放',
            '1' => '未決定',
            '2' => '票券',
            '3' => '禮品'
        ]
    ];

    protected $table = 'stockholder_gifts';
    protected $primaryKey = 'sg_Id';
    protected $allowedFields = [
        'sg_StockCode',
        'sg_StockName',
        'sg_MeetingDate',
        'sg_MeetingType',
        'sg_StockPrice',
        'sg_PriceChange',
        'sg_LastBuyDate',
        'sg_DeadlineDate',
        'sg_MarketType',
        'sg_ServiceAgent',
        'sg_Phone',
        'sg_Year',
        'sg_GiftStatus',
        'sg_VotingDateStart',
        'sg_VotingDateEnd'
    ];

    public function getList($params = [])
    {
        $builder = $this->buildBaseQuery();

        // 篩選條件
        $builder = $this->applyFilters($builder, $params);

        // 排序
        $builder = $this->applySorting($builder, $params);

        // 計算總筆數
        $total = $builder->countAllResults(false);

        // 處理分頁
        $page = $params['page'] ?? 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;

        // 取得分頁資料
        $items = $builder->limit($limit, $offset)
            ->get()
            ->getResultArray();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'totalPages' => ceil($total / $limit)
        ];
    }

    public function getOptions()
    {
        $options = [];

        foreach (self::CODE_TABLES as $type => $codeTable) {
            $options[$type] = array_map(function ($code, $name) {
                return [
                    'value' => $code,
                    'label' => $name
                ];
            }, array_keys($codeTable), array_values($codeTable));
        }

        return $options;
    }

    public function getDetail($id)
    {
        $builder = $this->buildBaseQuery();
        $builder->where('stockholder_gifts.sg_Id', $id);

        return $builder->get()->getRowArray();
    }

    /**
     * 檢查重複的股號
     *
     * @param string $stockCode
     * @return boolean
     */
    public function checkDuplicateStockCode($stockCode): bool
    {
        return $this->where('sg_StockCode', $stockCode)->countAllResults() > 0;
    }

    public function getHistoricalGifts($stockCode)
    {
        $builder = $this->builder();
        $builder->select('
                stockholder_gifts.sg_Id,
                stockholder_gifts.sg_StockCode,
                stockholder_gifts.sg_StockName,
                stockholder_gifts.sg_MeetingDate,
                stockholder_gifts.sg_MeetingType,
                products.p_Name,
                products.p_Image
            ')
            ->join('products', 'products.p_sg_Id = stockholder_gifts.sg_Id', 'left')
            ->where('stockholder_gifts.sg_StockCode', $stockCode)
            ->orderBy('stockholder_gifts.sg_MeetingDate', 'DESC');

        return $builder->get()->getResultArray();
    }

    /**
     * 套用篩選條件
     *
     * @param [type] $builder
     * @param [type] $params
     * @return BaseBuilder
     */
    private function applyFilters($builder, $params): BaseBuilder
    {
        // 年度
        if (!empty($params['year'])) {
            $builder->where('sg_Year', $params['year']);
        }

        // 關鍵字
        if (!empty($params['keyword'])) {
            $builder->groupStart()
                ->like('sg_StockName', $params['keyword'])
                ->orlike('sg_StockCode', $params['keyword'])
                ->orlike('sg_GiftName', $params['keyword'])
                ->groupEnd();
        }

        // 更新日期
        if (!empty($params['updateDateStart'])) {
            $builder->where('sg_UpdateedAt >=', $params['updateDateStart']);
        }

        if (!empty($params['updateDateEnd'])) {
            $builder->where('sg_UpdateedAt <=', $params['updateDateEnd']);
        }

        // 最後買進日
        if (!empty($params['lastBuyDateStart'])) {
            $builder->where('sg_LastBuyDate >=', $params['lastBuyDateStart']);
        }

        if (!empty($params['lastBuyDateEnd'])) {
            $builder->where('sg_LastBuyDate <=', $params['lastBuyDateEnd']);
        }

        // 代領截止日
        if (!empty($params['deadlineDateStart'])) {
            $builder->where('sg_DeadlineDate >=', $params['deadlineDateStart']);
        }

        if (!empty($params['deadlineDateEnd'])) {
            $builder->where('sg_DeadlineDate <=', $params['deadlineDateEnd']);
        }

        // 紀念品狀態
        if (!empty($params['giftStatus'])) {
            $builder->where('sg_GiftStatus', $params['giftStatus']);
        }

        // 開會性質
        if (!empty($params['meetingType'])) {
            $builder->where('sg_MeetingType', $params['meetingType']);
        }

        // 市場類別
        if (!empty($params['marketType'])) {
            $builder->where('sg_MarketType', $params['marketType']);
        }

        // 文件
        if (!empty($params['documentIds']) && is_array($params['documentIds'])) {
            $builder = $this->applyDocFilters($builder, $params['documentIds']);
        }

        return $builder;
    }

    private function applyDocFilters($builder, $docIds)
    {
        if (empty($docIds) || !is_array($docIds)) {
            return $builder;
        }

        $subQuery = $this->db->table('document_combination_details dcd')
            ->select('dc.dc_sg_Id')
            ->join('document_combinations dc', 'dc.dc_Id = dcd.dcd_dc_Id')
            ->whereIn('dcd.dcd_d_Id', $docIds)
            ->groupBy('dc.dc_sg_Id')
            ->having('COUNT(DISTINCT dcd.dcd_d_Id) = ' . count($docIds));

        return $builder->whereIn('sg_Id', $subQuery);
    }

    private function applySorting(BaseBuilder $builder, array $params): BaseBuilder
    {
        // 預設
        $sortField = $params['sortField'] ?? 'sg_UpdatedAt';
        $sortOrder = $params['sortOrder'] ?? 'DESC';

        switch ($sortField) {
            case 'meetingType':
                $builder->orderBy('sg_MeetingType', $sortOrder)
                    ->orderBy('sg_StockCode', 'ASC');
                break;
            case 'stockCode':
                $builder->orderBy('sg_StockCode', $sortOrder);
                break;
            case 'stockName':
                $builder->orderBy('sg_StockName', $sortOrder);
                break;
            default:
                $builder->orderBy($sortField, $sortOrder);
        }

        return $builder;
    }

    private function buildBaseQuery(): BaseBuilder
    {
        $builder = $this->builder();

        // 建立子查詢來獲取最新的紀念品
        $latestProduct = $this->db->table('products')
            ->select('p_sg_Id, MAX(p_Id) as latest_p_Id')
            ->groupBy('p_sg_Id');

        // 基本的 SELECT 和 JOIN
        return $builder->select('
                stockholder_gifts.*,
                p.p_Name,
                p.p_Image')
            ->join(
                "({$latestProduct->getCompiledSelect()}) as latest",
                'latest.p_sg_Id = stockholder_gifts.sg_Id',
                'left'
            )
            ->join(
                'products p',
                'p.p_sg_Id = latest.p_sg_Id AND p.p_Id = latest.latest_p_Id',
                'left'
            );
    }
}
