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
        $result['documentCombinations'][] = $allCombinations[$id] ?? null;

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

    public function updateStockholderGift(int $id, array $data, ?array $combinations = null): bool
    {
        $this->sgModel->transStart();

        try {
            $sgData = $this->sgModel->find($id);

            if (!$sgData) {
                throw new Exception('找不到對象');
            }

            $sgData->fill($data);
            $sgData->updatedAt = date('Y-m-d H:i:s');

            if (!$this->sgModel->update($id, $sgData)) {
                $errors = $this->sgModel->errors();
                throw new Exception('更新股東會資訊失敗: ' . implode(', ', $errors));
            }

            // 如果提供了文件組合資料，則處理文件組合
            if ($combinations !== null) {
                // 刪除舊的文件組合
                $this->docModel->deleteCombinations($id);

                // 如果有新的組合，創建它們
                if (!empty($combinations) && is_array($combinations)) {
                    $this->docModel->createCombinations($id, $combinations);
                }
            }

            $this->sgModel->transComplete();
            return true;
        } catch (Exception $e) {
            $this->sgModel->transRollback();
            throw $e;
        }
    }
}
