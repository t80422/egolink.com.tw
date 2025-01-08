<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table = 'products';
    protected $primaryKey = 'p_Id ';
    protected $allowedFields = [
        'p_sg_Id',
        'p_Name',
        'p_Image',
        'p_TotalQuantity',
        'p_Sequence',
        'p_CreatedBy',
        'p_UpdatedBy'
    ];

    /**
     * 取得指定股東會紀念品列表
     *
     * @param integer $sgId 股東會Id
     * @return array
     */
    public function getProductsBySGId(int $sgId):array
    {
        return $this->where('p_sg_Id', $sgId)
            ->orderBy('p_Sequence', 'ASC')
            ->findAll();
    }

    public function getDetail(int $productId):?array{
        $product=$this->find($productId);

        if(!$product){
            return null;
        }


    }
}
