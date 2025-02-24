<?php

namespace App\Models;

use App\Entities\Shipment;
use CodeIgniter\I18n\Time;
use CodeIgniter\Model;

class ShipmentModel extends Model
{
    protected $table            = 'shipments';
    protected $primaryKey       = 's_Id';
    protected $returnType       = Shipment::class;
    protected $allowedFields    = [
        's_Number',
        's_Date',
        's_Memo'
    ];

    protected $validationRules      = [
        's_Date' => 'required|valid_date'
    ];
    protected $validationMessages   = [
        's_Date' => [
            'required' => '出貨日期為必填',
            'valid_date' => '出貨日期格式不正確'
        ]
    ];

    /**
     * 生成出貨單號 (YYYYMMDD + 3位數流水號)
     *
     * @return string
     */
    public function generateShipmentNum(): string
    {
        $prefix = Time::now()->format('Ymd');
        $lastNum = $this->where('s_Number LIKE', $prefix . '%')
            ->orderBy('s_Number', 'DESC')
            ->first();

        $sequence = 1;

        if ($lastNum) {
            $sequence = (int)substr($lastNum->number, -4) + 1;
        }

        return $prefix . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }

    public function getList(array $params = []): array
    {
        $builder = $this->builder('shipments s')
            ->select('
            s.*,
            MAX(u.u_Name) as userName,
            MAX(u.u_Phone) as phone
        ')
            ->join('orders o', 'o.o_s_Id = s.s_Id')
            ->join('sub_accounts sa', 'sa.sa_Id = o.o_sa_Id')
            ->join('users u', 'u.u_Id = sa.sa_u_Id')
            ->groupBy('s.s_Id')
            ->orderBy('s.s_Date', 'DESC');

        // 關鍵字
        if (!empty($params['keyword'])) {
            $keyword = $params['keyword'];
            $builder->groupStart()
                ->like('s_Number', $keyword)
                ->orLike('u_Name', $keyword)
                ->orLike('u_Phone', $keyword)
                ->groupEnd();
        }

        // 日期
        if (!empty($params['startDate'])) {
            $builder->where('s_Date >=', $params['startDate']);
        }

        if (!empty($params['endDate'])) {
            $builder->where('s_Date <=', $params['endDate']);
        }

        // 總筆數
        $total = $builder->countAllResults(false);

        // 分頁
        $page = empty($params['page']) ? 1 : (int)$params['page'];
        $limit = 20;
        $offset = ($page - 1) * $limit;
        $items = $builder->limit($limit, $offset)
            ->get()
            ->getResult($this->returnType);

        return [
            'page' => $page,
            'totalPages' => ceil($total / $limit),
            'total' => $total,
            'items' => $items
        ];
    }

    public function getUserInfo(int $shipmentId): ?array
    {
        return $this->builder('orders o')
            ->select('
            DISTINCT u.u_Name as userName,
            u.u_Phone as phone
        ', false)
            ->join('sub_accounts sa', 'sa.sa_Id = o.o_sa_Id')
            ->join('users u', 'u.u_Id = sa.sa_u_Id')
            ->where('o.o_s_Id', $shipmentId)
            ->get()
            ->getRowArray();
    }

    /**
     * 取得出貨單紀念品統計資訊
     *
     * @param integer $shipmentId
     * @return array
     */
    public function getProductDetails(int $shipmentId): array
    {
        $items = $this->builder('orders o')
            ->select('
            p.p_Name as productName,
            COUNT(*) as qty,
            sg.sg_StockCode as stockCode,
            sg.sg_StockName as stockName
        ')
            ->join('stockholder_gifts sg', 'sg.sg_Id = o.o_sg_Id')
            ->join('products p', 'p.p_sg_Id = o.o_sg_Id')
            ->where('o.o_s_Id', $shipmentId)
            ->groupBy('p.p_Id')
            ->get()
            ->getResultArray();

        return array_map(function ($item) {
            return [
                'productName' => $item['productName'],
                'qty' => (int)$item['qty'],
                'stock' => sprintf('%s %s', $item['stockCode'], $item['stockName'])
            ];
        }, $items);
    }

    public function getShipmentList(int $userId, array $params): array
    {
        $subQueryBuilder = $this->db->table('shipments s')
            ->select('
            s.s_Id,
            s.s_Number,
            s.s_Date,
            MAX(o.o_Status) as status,
            COUNT(DISTINCT o.o_sg_Id) as items,
            COUNT(o.o_Id) as total
        ')
            ->join('orders o', 'o.o_s_Id=s.s_Id')
            ->join('sub_accounts sa', 'sa.sa_Id=o.o_sa_Id')
            ->join('users u', 'u.u_Id=sa.sa_u_Id')
            ->where('u.u_Id', $userId)
            ->groupBy('s.s_Id, s.s_Number, s.s_Date');

        // 關鍵字
        if (!empty($params['keyword'])) {
            $keyword = $params['keyword'];
            $subQueryBuilder->groupStart()
                ->like('s.s_Number', $keyword)
                ->orLike('u.u_Name', $keyword)
                ->orLike('u.u_Phone', $keyword)
                ->groupEnd();
        }

        // 年分
        if (!empty($params['year'])) {
            $subQueryBuilder->where('YEAR(s_Date)', $params['year']);
        }

        // 建立主查詢，使用子查詢結果
        $subQuery = $subQueryBuilder->getCompiledSelect();
        $builder = $this->db->table("({$subQuery}) as shipment_summary");

        // 計算總筆數
        $total = $builder->countAllResults(false);

        // 分頁
        $page = empty($params['page']) ? 1 : (int)$params['page'];
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $items = $builder->limit($limit, $offset)
            ->get()
            ->getResult($this->returnType);

        return [
            'page' => $page,
            'totalPages' => ceil($total / $limit),
            'total' => $total,
            'items' => $items
        ];
    }
}
