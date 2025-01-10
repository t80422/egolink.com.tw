<?php

namespace App\Models;

use App\Entities\Product;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table = 'products';
    protected $primaryKey = 'p_Id';
    protected $allowedFields = [
        'p_sg_Id',
        'p_Name',
        'p_Image',
        'p_Qty',
        'p_Sequence',
        'p_CreatedBy',
        'p_UpdatedBy'
    ];
    protected $returnType = Product::class;
    protected $validationRules = [
        'p_Name' => 'required|min_length[1]',
        'p_sg_Id' => 'required',
        'p_Qty' => 'required|is_natural'
    ];
    protected $validationMessages = [
        'p_Name' => [
            'required' => '名稱為必填',
            'min_length' => '名稱至少需要1個字元'
        ],
        'p_sg_Id' => [
            'required' => '股東會Id為必填'
        ],
        'p_Qty' => [
            'required' => '數量為必填',
            'is_natural' => '數量必須為正整數'
        ]
    ];


    /**
     * 取得指定股東會紀念品列表
     *
     * @param integer $sgId 股東會Id
     * @return array
     */
    public function getProductsBySGId(int $sgId): array
    {
        return $this->where('p_sg_Id', $sgId)
            ->orderBy('p_Sequence', 'ASC')
            ->findAll();
    }

    /**
     * 取得紀念品新順序
     *
     * @param integer $sgId
     * @return integer
     */
    public function getNextSequence(int $sgId): int
    {
        $result = $this->where('p_sg_Id', $sgId)
            ->selectMax('p_Sequence')
            ->first();

        return ($result->sequence ?? 0) + 1;
    }

    public function getList(array $params = []): array
    {
        $builder = $this->baseBuilder();
        $this->applySearchFilters($builder, $params);
        $this->applySorting($builder, $params);

        $total = $builder->countAllResults(false);
        $page = $params['page'] ?? 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        $items = $builder->limit($limit, $offset)->get()->getResult($this->returnType);

        return [
            'total' => $total,
            'page' => $page,
            'totalPage' => ceil($total / $limit),
            'items' => $items
        ];
    }

    public function getDetail(int $id): ?Product
    {
        return $this->baseBuilder()
            ->where('p.p_Id', $id)
            ->get()
            ->getResult($this->returnType)[0] ?? null;
    }

    private function baseBuilder()
    {
        return $this->builder('products p')
            ->select('
            p.*,
            sg.sg_Id,
            sg.sg_StockCode,
            sg.sg_StockName,
            creator.u_Name as creatorName,
            updater.u_Name as updaterName
        ')
            ->join('stockholder_gifts sg', 'sg.sg_Id = p.p_sg_Id')
            ->join('users as creator', 'creator.u_Id = p.p_CreatedBy', 'left')
            ->join('users as updater', 'updater.u_Id = p.p_UpdatedBy', 'left');
    }

    private function applySearchFilters(BaseBuilder $builder, array $params)
    {
        // 關鍵字
        if (!empty($params['keyword'])) {
            $keyword = $params['keyword'];
            $builder->groupStart()
                ->like('sg_StockName', $keyword)
                ->orLike('sg_StockCode', $keyword)
                ->orLike('p_Name', $keyword)
                ->orLike('creator.u_Name', $keyword)
                ->orLike('updater.u_Name', $keyword)
                ->groupEnd();
        }

        // 建立日期
        if (!empty($params['startDate'])) {
            $builder->where('p_CreatedAt >=', $params['startDate']);
        }

        if (!empty($params['endDate'])) {
            $builder->where('p_CreatedAt <=', $params['endDate']);
        }
    }

    private function applySorting(BaseBuilder $builder, array $params)
    {
        $sortField = $params['sortField'] ?? 'p_CreatedAt';
        $sortOrder = $params['sortOrder'] ?? 'DESC';

        $sortableFields = [
            'stockName' => 'sg_StockName',
            'stockCode' => 'sg_StockCode',
            'createdBy' => 'creator.u_Name',
            'updatedBy' => 'updater.u_Name',
            'createdAt' => 'p_CreatedAt',
            'updatedAt' => 'p_UpdatedAt'
        ];

        if (isset($sortableFields[$sortField])) {
            $builder->orderBy($sortableFields[$sortField], $sortOrder);
        }
    }
}
