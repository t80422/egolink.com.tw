<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Document extends Entity
{
    protected $datamap = [
        'id' => 'd_Id',
        'name' => 'd_Name'
    ];

    public function formatForOption(): array
    {
        return [
            'value' => $this->id,
            'label' => $this->name
        ];
    }
}
