<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class StockholderGift extends Entity
{
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
        'year' => 'sg_Year',
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
}
