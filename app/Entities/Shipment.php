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
}
