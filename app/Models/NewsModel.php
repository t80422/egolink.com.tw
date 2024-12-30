<?php

namespace App\Models;

use CodeIgniter\Model;

class NewsModel extends Model
{
    protected $table            = 'news';
    protected $primaryKey       = 'n_Id';
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'n_Title',
        'n_Content',
        'n_Date'
    ];

    public function getList(){
        $datas=$this->orderBy('n_Date','DESC')->findAll();

        return $datas;
    }
}
