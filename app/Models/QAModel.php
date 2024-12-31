<?php

namespace App\Models;

use CodeIgniter\Model;

class QAModel extends Model
{
    protected $table            = 'qa';
    protected $primaryKey       = 'q_Id';
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'q_Title',
        'q_Content'
    ];

    public function getList():array{
        return $this->orderBy('q_Date','DESC')->findAll();
    }
}
