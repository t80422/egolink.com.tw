<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Order extends Entity
{
    protected $datamap = [
        'id' => 'o_Id',
        'sgId' => 'o_sg_Id',
        'saId' => 'o_sa_Id',
        'status' => 'o_Status',
        'accountNum' => 'o_AccountNum',
        'memo' => 'o_Memo',
        'date' => 'o_Date',
        'sId' => 'o_sh_Id'
    ];

    public const STATUS_INCOMPLETE = 0;
    public const STATUS_OVERDUE = 1;
    public const STATUS_PENDING = 2;
    public const STATUS_SHIPPED = 3;

    public const STATUS_NAMES = [
        self::STATUS_INCOMPLETE => '缺件',
        self::STATUS_OVERDUE => '逾期',
        self::STATUS_PENDING => '待出貨',
        self::STATUS_SHIPPED => '已出貨'
    ];

    /**
     * 取得狀態名稱
     *
     * @return string
     */
    public function getStatusName(): string
    {
        return self::STATUS_NAMES[$this->status] ?? '未知';
    }

    /**
     * 檢查是否可出貨
     *
     * @return boolean
     */
    public function isShippable(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
