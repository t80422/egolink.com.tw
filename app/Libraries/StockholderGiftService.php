<?php

namespace App\Libraries;

use App\Entities\StockholderGift;
use App\Models\DocumentModel;
use App\Models\StockholderGiftsModel;

class StockholderGiftService
{
    private $sgModel;
    private $docModel;

    public function __construct()
    {
        $this->sgModel = new StockholderGiftsModel();
        $this->docModel = new DocumentModel();
    }

    public function getList(array $params = [])
    {
        $datas = $this->sgModel->getList($params);

        if (empty($datas['items'])) {
            return $datas;
        }

        $allIds = array_column($datas['items'], 'sg_Id');
        $documentCombinations = $this->docModel->getDocCombinsBySGIds($allIds);
        $formatItems = array_map(
            fn(StockholderGift $item) => $item->formatForList($documentCombinations),
            $datas['items']
        );

        $datas['items'] = $formatItems;

        return $datas;
    }
}
