<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Purchase extends Entity
{
    protected $datamap = [
        'id' => 'pu_Id',
        'date' => 'pu_Date',
        'memo' => 'pu_Memo'
    ];

    /**
     * 取得進貨明細
     *
     * @return array|null
     */
    public function getDetails(): ?array
    {
        return $this->attributes['details'] ?? null;
    }
}
