<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Product extends Entity
{
    protected $datamap = [
        'id' => 'p_Id',
        'sgId' => 'p_sg_Id',
        'name' => 'p_Name',
        'img' => 'p_Image',
        'qty' => 'p_InboundQty',
        'outBoundQty' => 'p_OutboundQty',
        'sequence' => 'p_Sequence',
        'createdAt' => 'p_CreatedAt',
        'updatedAt' => 'p_UpdatedAt',
        'createdBy' => 'p_CreatedBy',
        'updatedBy' => 'p_UpdatedBy'
    ];

    /**
     * 取得圖片URL
     *
     * @return string|null
     */
    public function getImgUrl(): ?string
    {
        if (empty($this->img)) {
            return null;
        }

        return base_url('uploads/gifts/' . $this->img);
    }

    public function getStockInfo()
    {
        $code = $this->attributes['sg_StockCode'] ?? null;
        $name = $this->attributes['sg_StockName'] ?? null;

        if ($code !== null && $name !== null) {
            return [
                'code' => $code,
                'name' => $name
            ];
        }

        return null;
    }

    public function getCreator()
    {
        return $this->attributes['creatorName'] ?? null;
    }

    public function getUpdater()
    {
        return $this->attributes['updaterName'] ?? null;
    }

    public function formatForOptions()
    {
        return [
            'value' => $this->id,
            'label' => $this->name
        ];
    }

    /**
     * 取得可用庫存
     *
     * @return integer
     */
    public function getAvailableStock():int{
        return $this->qty -$this->outBoundQty;
    }
}
