<?php

namespace App\Libraries;

use App\Entities\StockholderGift;
use App\Models\DocumentModel;
use App\Models\StockholderGiftsModel;
use Exception;

class StockholderGiftService
{
    private $sgModel;
    private $docModel;
    private $docSer;

    public function __construct()
    {
        $this->sgModel = new StockholderGiftsModel();
        $this->docModel = new DocumentModel();
        $this->docSer = new DocumentService();
    }

    public function getList(array $params = [])
    {
        $datas = $this->sgModel->getList($params);

        if (empty($datas['items'])) {
            return $datas;
        }

        $allIds = array_column($datas['items'], 'id');
        $documentCombinations = $this->docModel->getDocCombinsBySGIds($allIds);
        $formatItems = array_map(
            fn(StockholderGift $item) => $item->formatForList($documentCombinations),
            $datas['items']
        );

        $datas['items'] = $formatItems;

        return $datas;
    }

    public function getDetail(int $id): array
    {
        $data = $this->sgModel->find($id);

        if (!$data) {
            throw new Exception('找不到對象');
        }

        $allCombinations = $this->docModel->getDocCombinsBySGIds([$id]);
        $result = $data->formatForDetail();
        $result['documentCombinations'][] = $allCombinations[$id];

        return $result;
    }

    /**
     * 取得股東會資訊選單
     *
     * @return array
     */
    public function getSGOptions(): array
    {
        $datas = $this->sgModel->getByYear(date("Y"));

        return array_map(function (StockholderGift $data) {
            return $data->formatforOption();
        }, $datas);
    }

    public function getDocumentOptions(): array
    {
        return $this->docSer->getOptions();
    }
}
