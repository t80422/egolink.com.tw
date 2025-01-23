<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class PurchaseDetail extends Entity
{
    protected $datamap = [
        'id' => 'pd_Id',
        'productId' => 'pd_p_Id',
        'purchaseId' => 'pd_pu_Id',
        'qty' => 'pd_Qty'
    ];
}
