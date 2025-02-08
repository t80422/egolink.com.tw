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
        'sId' => 'o_sh_Id',
        'voteImg' => 'o_VoteImg', // 投票圖片
        'voteImgUploadTime' => 'o_VoteImgUploadTime' // 投票圖片上傳時間
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

    public function formatShippableUser(): array
    {
        return [
            'userId' => $this->attributes['u_Id'],
            'name' => $this->attributes['u_Name'],
            'phone' => $this->attributes['u_Phone'],
            'location' => $this->attributes['l_Name'],
        ];
    }

    /**
     * 檢查是否可以上傳投票圖片
     * 只有缺件狀態的委託才能上傳
     *
     * @return boolean
     */
    public function canUploadVoteImg(): bool
    {
        return $this->status === self::STATUS_INCOMPLETE;
    }

    public function setVoteImg(string $imgName)
    {
        $this->voteImg = $imgName;
        $this->voteImgUploadTime = date('Y-m-d H:i:s');
    }

    /**
     * 更新為待出貨狀態
     * 當符合文件方案時呼叫此方法
     *
     * @return void
     */
    public function markAsPending()
    {
        $this->status = self::STATUS_PENDING;
    }
}
