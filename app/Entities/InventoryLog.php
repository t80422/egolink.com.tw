<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class InventoryLog extends Entity
{
    protected $datamap = [
        'id' => 'il_Id',
        'pId' => 'il_p_Id',
        'type' => 'il_Type',
        'qty' => 'il_Qty',
        'beforeQty' => 'il_BeforeQty',
        'user' => 'il_CreatedBy',
        'memo' => 'il_Memo',
        'createdAt' => 'il_CreatedAt'
    ];

    /**
     * 進貨
     */
    public const TYPE_IN = 1;
    /**
     * 出庫
     */
    public const TYPE_OUT = 2;
    /**
     * 修改
     */
    public const TYPE_MODIFY = 3;

    private const TYPE_NAMES = [
        self::TYPE_IN => '入庫',
        self::TYPE_OUT => '出庫',
        self::TYPE_MODIFY => '直接修改'
    ];

    /**
     * 取得異動類型名稱
     *
     * @return void
     */
    public function getTypeName(): string
    {
        return self::TYPE_NAMES[$this->type] ?? '未知';
    }
}
