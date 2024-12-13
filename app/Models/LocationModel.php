<?php

namespace App\Models;

use CodeIgniter\Model;

class LocationModel extends Model
{
    protected $table='locations';
    protected $primaryKey='l_Id';
    protected $allowedFields=[
        'l_Name',
        'l_Image',
        'l_Phone',
        'l_Address',
        'l_LineLink'
    ];

    // 設定返回類型
    protected $returnType='array';
}
