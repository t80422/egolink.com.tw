<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Shipment extends Entity
{
    protected $datamap = [
        'id' => 's_Id',
        'number' => 's_Number',
        'date' => 's_Date',
        'memo' => 's_Memo',
        'createdAt' => 's_CreatedAt'
    ];

    public function formatForList()
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'date' => $this->date,
            'items'=>$this->attributes['items'],
            'total'=>$this->attributes['total'],
            'status'=>$this->getStatusName(),
            'memo' => $this->memo
        ];
    }

    private function getStatusName():string{
        $status=$this->attributes['status'];

        return Order::STATUS_NAMES[$status];
    }
}
