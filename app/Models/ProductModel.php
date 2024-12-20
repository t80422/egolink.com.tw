<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table = 'product';
    protected $primaryKey = 'p_Id ';
    protected $allowedFields = [
        'p_sg_Id',
        'p_Name',
        'p_Image'
    ];

    public function getProductsBySGId(int $sgId)
    {
        return $this->where('p_sg_Id', $sgId)->findAll();
    }
}
