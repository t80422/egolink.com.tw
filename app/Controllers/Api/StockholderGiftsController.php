<?php

namespace App\Controllers\Api;

use App\Entities\StockholderGift;
use App\Libraries\StockholderGiftService;
use App\Models\DocumentModel;
use App\Models\StockholderGiftsModel;

/**
 * 股東會資訊
 */
class StockholderGiftsController extends BaseApiController
{
    protected $sgModel;
    protected $docModel;
    private $sgSer;

    public function __construct()
    {
        $this->sgModel = new StockholderGiftsModel();
        $this->docModel = new DocumentModel();
        $this->sgSer = new StockholderGiftService();
    }

    // 列表
    public function index()
    {
        try {
            $params = $this->request->getGet();
            $datas = $this->sgSer->getList($params);

            return $this->successResponse('', $datas);
        } catch (\Exception $e) {
            return $this->errorResponse('取得列表時發生錯誤', $e);
        }
    }

    // 選項
    public function getOptions()
    {
        try {
            $options = $this->sgModel->getOptions();

            $options['documents'] = $this->sgSer->getDocumentOptions();

            return $this->successResponse('', $options);
        } catch (\Exception $e) {
            return $this->errorResponse('取得選項時發生錯誤', $e);
        }
    }

    // 新增
    public function create()
    {
        try {
            $this->sgModel->transStart();

            $data = $this->request->getJSON(true);
            $stock = new StockholderGift();
            $stock->fill($data);

            if ($this->sgModel->checkDuplicateStockCode($stock->stockCode, $stock->meetingDate)) {
                return $this->errorResponse('重複的股號');
            }

            $sgId = $this->sgModel->insert($stock);

            if (!$sgId) {
                throw new \Exception('新增股東會資訊失敗');
            }

            // 處理文件組合
            if (!empty($data['combinations']) && is_array($data['combinations'])) {
                $this->docModel->createCombinations($sgId, $data['combinations']);
            }

            $this->sgModel->transComplete();

            return $this->successResponse('新增成功');
        } catch (\Exception $e) {
            $this->sgModel->transRollback();

            return $this->errorResponse('新增時發生錯誤', $e);
        }
    }

    // 詳細
    public function detail($id)
    {
        try {
            $result = $this->sgSer->getDetail((int)$id);

            return $this->successResponse('', $result);
        } catch (\Exception $e) {
            return $this->errorResponse('取得詳細時發生錯誤', $e);
        }
    }

    // 修改
    public function edit($id = null)
    {
        try {
            // 獲取主要數據
            $requestData = $this->request->getJSON(true);

            // 檢查是否包含文件組合數據
            $combinations = $requestData['combinations'] ?? null;

            // 移除不屬於 StockholderGift 模型的數據
            if (isset($requestData['combinations'])) {
                unset($requestData['combinations']);
            }

            // 調用服務層方法進行更新
            $this->sgSer->updateStockholderGift($id, $requestData, $combinations);

            return $this->successResponse('修改成功');
        } catch (\Exception $e) {
            return $this->errorResponse('修改時發生錯誤', $e);
        }
    }

    // 刪除
    public function delete($id = null)
    {
        try {
            $data = $this->sgModel->find($id);

            if (!$data) {
                return $this->errorResponse('刪除失敗,找不到對象');
            }
            $this->sgModel->delete($id);
            return $this->successResponse('刪除成功');
        } catch (\Exception $e) {
            return $this->errorResponse('刪除時發生錯誤', $e);
        }
    }

    // 歷年發放
    public function getHistoricalGifts()
    {
        try {
            $stockCode = $this->request->getGet('stockCode');
            $datas = $this->sgModel->getHistoricalGifts($stockCode);
            $allIds = array_column($datas, 'sg_Id');
            $allCombinations = $this->docModel->getDocCombinsBySGIds($allIds);
            $items = array_map(function ($item) use ($allCombinations) {
                return [
                    'meetingDate' => $item['sg_MeetingDate'],
                    'meetingType' => StockholderGift::CODE_TABLES['meetingType'][$item['sg_MeetingType']],
                    'giftName' => $item['p_Name'] ?? null,
                    'giftImg' => !empty($item['p_Image']) ? base_url('upload/gifts/' . $item['p_Image']) : null,
                    'documentCombinations' => $allCombinations[$item['sg_Id']] ?? []
                ];
            }, $datas);

            $result = [
                'stockCode' => $stockCode,
                'stockName' => $datas[0]['sg_StockName'] ?? '',
                'items' => $items
            ];

            return $this->successResponse('', $result);
        } catch (\Exception $e) {
            return $this->errorResponse('取得歷年發放發生錯誤', $e);
        }
    }

    // 取得股票選單
    public function getSGOptions()
    {
        try {
            $result = $this->sgSer->getSGOptions();

            return $this->successResponse('', $result);
        } catch (\Exception $e) {
            return $this->errorResponse('取得初始資料時發生錯誤', $e);
        }
    }


    private function getRequestData()
    {
        return [
            'stockCode' => $this->request->getVar('stockCode'),
            'stockName' => $this->request->getVar('stockName'),
            'meetingType' => $this->request->getVar('meetingType'),
            'meetingDate' => $this->request->getVar('meetingDate'),
            'stockPrice' => $this->request->getVar('stockPrice'),
            'priceChange' => $this->request->getVar('priceChange'),
            'lastBuyDate' => $this->request->getVar('lastBuyDate'),
            'deadlineDate' => $this->request->getVar('deadlineDate'),
            'marketType' => $this->request->getVar('marketType'),
            'serviceAgent' => $this->request->getVar('serviceAgent'),
            'phone' => $this->request->getVar('phone'),
            'year' => $this->request->getVar('year'),
            'votingDateStart' => $this->request->getVar('votingDateStart'),
            'votingDateEnd' => $this->request->getVar('votingDateEnd'),
        ];
    }
}
