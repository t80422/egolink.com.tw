<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class StockholderGift extends Entity
{
    // 開會性質
    public const MEETING_TYPE_TEMPORARY = 0;
    public const MEETING_TYPE_REGULAR = 1;

    // 市場類型
    public const MARKET_TYPE_LISTED = 0;
    public const MARKET_TYPE_OTC = 1;
    public const MARKET_TYPE_EMERGING = 2;
    public const MARKET_TYPE_PUBLIC = 3;

    // 紀念品狀態
    public const GIFT_STATUS_NONE = 0;
    public const GIFT_STATUS_UNDECIDED = 1;
    public const GIFT_STATUS_VOUCHER = 2;
    public const GIFT_STATUS_GIFT = 3;

    public const  CODE_TABLES = [
        'meetingType' => [
            self::MEETING_TYPE_TEMPORARY => '臨時',
            self::MEETING_TYPE_REGULAR => '常會'
        ],
        'marketType' => [
            self::MARKET_TYPE_LISTED => '上市',
            self::MARKET_TYPE_OTC => '上櫃',
            self::MARKET_TYPE_EMERGING => '興櫃',
            self::MARKET_TYPE_PUBLIC => '公開發行',
        ],
        'giftStatus' => [
            self::GIFT_STATUS_NONE => '無發放',
            self::GIFT_STATUS_UNDECIDED => '未決定',
            self::GIFT_STATUS_VOUCHER => '票券',
            self::GIFT_STATUS_GIFT => '禮品',
        ]
    ];

    protected $datamap = [
        'id' => 'sg_Id',
        'stockCode' => 'sg_StockCode',
        'stockName' => 'sg_StockName',
        'meetingDate' => 'sg_MeetingDate',
        'meetingType' => 'sg_MeetingType',
        'giftStatus' => 'sg_GiftStatus',
        'stockPrice' => 'sg_StockPrice',
        'priceChange' => 'sg_PriceChange',
        'lastBuyDate' => 'sg_LastBuyDate',
        'deadlineDate' => 'sg_DeadlineDate',
        'marketType' => 'sg_MarketType',
        'serviceAgent' => 'sg_ServiceAgent',
        'phone' => 'sg_Phone',
        'updatedAt' => 'sg_UpdatedAt',
        'votingDateStart' => 'sg_VotingDateStart',
        'votingDateEnd' => 'sg_VotingDateEnd'
    ];

    public function formatforOption(): array
    {
        return [
            'value' => $this->id,
            'label' => $this->stockCode . ' ' . $this->stockName
        ];
    }

    public function formatForList(array $documentCombinations = []): array
    {
        return [
            'id' => $this->id,
            'stockCode' => $this->stockCode,
            'stockName' => $this->stockName,
            'meetingDate' => $this->meetingDate,
            'meetingType' => self::CODE_TABLES['meetingType'][$this->meetingType] ?? null,
            'giftName' => $this->attributes['p_Name'] ?? null,
            'giftImage' => !empty($this->attributes['p_Image'])
                ? base_url('uploads/gifts/' . $this->attributes['p_Image'])
                : null,
            'stockPrice' => $this->stockPrice,
            'priceChange' => $this->priceChange,
            'lastBuyDate' => $this->lastBuyDate,
            'deadlineDate' => $this->deadlineDate,
            'marketType' => self::CODE_TABLES['marketType'][$this->marketType] ?? null,
            'serviceAgent' => $this->serviceAgent,
            'phone' => $this->phone,
            'votingDateStart' => $this->votingDateStart,
            'votingDateEnd' => $this->votingDateEnd,
            'updateDate' => (new \DateTime($this->updatedAt))->format('Y-m-d'),
            'documentCombinations' => $documentCombinations[$this->id] ?? []
        ];
    }
}
