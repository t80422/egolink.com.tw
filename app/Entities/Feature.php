<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Feature extends Entity
{
    protected $datamap = [
        'id'=>'f_Id',
        'name'=>'f_Name',
        'code'=>'f_Code'
    ];

    public function formatForOption():array{
        return[
            'value'=>$this->id,
            'label'=>$this->name
        ];
    }
}
