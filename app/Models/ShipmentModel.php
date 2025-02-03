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
        log_message('debug',$page);
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
