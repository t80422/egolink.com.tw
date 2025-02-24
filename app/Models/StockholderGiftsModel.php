<?php

namespace App\Models;

use App\Entities\StockholderGift;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Model;

/**
 * 股東會資訊
 */
class StockholderGiftsModel extends Model
{
    protected $table = 'stockholder_gifts';
    protected $primaryKey = 'sg_Id';
    protected $returnType = StockholderGift::class;
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
        'sg_GiftStatus',
        'sg_VotingDateStart',
        'sg_VotingDateEnd',
        'sg_UpdatedAt'
    ];

    public function getList($params = [])
    {
        $builder = $this->buildBaseQuery();

        // 篩選條件
        $builder = $this->applyFilters($builder, $params);

        // 計算總筆數
        $total = $builder->countAllResults(false);

        // 處理分頁
        $page = empty($params['page']) ? 1 : (int)$params['page'];
        $limit = 20;
        $offset = ($page - 1) * $limit;

        // 取得分頁資料
        $items = $builder->limit($limit, $offset)
            ->get()
            ->getResult($this->returnType);

        return [
            'total' => $total,
            'page' => $page,
            'totalPages' => ceil($total / $limit),
            'items' => $items,
        ];
    }

    public function getOptions()
    {
        $options = [];

        foreach (StockholderGift::CODE_TABLES as $type => $codeTable) {
            $options[$type] = array_map(function ($code, $name) {
                return [
                    'value' => $code,
                    'label' => $name
                ];
            }, array_keys($codeTable), array_values($codeTable));
        }

        return $options;
    }

    /**
     * 檢查重複的股號
     *
     * @param string $stockCode
     * @return boolean
     */
    public function checkDuplicateStockCode(string $stockCode, string $meetingDate): bool
    {
        $year = date('Y', strtotime($meetingDate));

        return $this->where('sg_StockCode', $stockCode)
            ->where("YEAR(sg_MeetingDate) = $year", null, false)
            ->countAllResults() > 0;
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

    public function getByYear(int $year): array
    {
        $builder = $this->builder();
        $startDate = $year . '-01-01';
        $endDate = $year . '-12-31';
        $builder->where('sg_MeetingDate >=', $startDate)
            ->where('sg_MeetingDate <=', $endDate);

        return $builder->get()->getResult($this->returnType);
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
            $startDate = $params['year'] . '-01-01';
            $endDate = $params['year'] . '-12-31';
            $builder->where('sg_MeetingDate >=', $startDate)
                ->where('sg_MeetingDate <=', $endDate);
        }

        // 關鍵字
        if (!empty($params['keyword'])) {
            $builder->groupStart()
                ->like('sg_StockName', $params['keyword'])
                ->orlike('sg_StockCode', $params['keyword'])
                ->orlike('p_Name', $params['keyword'])
                ->groupEnd();
        }

        // 更新日期
        if (!empty($params['updateDateStart'])) {
            $builder->where('sg_UpdatedAt >=', $params['updateDateStart']);
        }

        if (!empty($params['updateDateEnd'])) {
            $builder->where('sg_UpdatedAt <=', $params['updateDateEnd']);
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
        if (isset($params['meetingType']) && $params['meetingType'] !== '') {
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
