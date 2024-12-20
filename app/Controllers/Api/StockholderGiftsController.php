<?php

namespace App\Controllers\Api;

use App\Models\DocumentModel;
use App\Models\StockholderGiftsModel;

/**
 * 股東會資訊
 */
class StockholderGiftsController extends BaseApiController
{
    protected $sgModel;
    protected $docModel;

    public function __construct()
    {
        $this->sgModel = new StockholderGiftsModel();
        $this->docModel = new DocumentModel();
    }

    // 列表
    public function index()
    {
        try {
            $params = [
                'page' => $this->request->getVar('page'), // 分頁
                'sortField' => $this->request->getVar('sortField'), // 排序欄位
                'sortOrder' => $this->request->getVar('sortOrder'), // 順序
                'year' => $this->request->getVar('year'), // 年分
                'keyword' => $this->request->getVar('keyword'), //關鍵字
                'updateDateStart' => $this->request->getVar('updateDateStart'), // 更新日起
                'updateDateEnd' => $this->request->getVar('updateDateEnd'), // 更新日迄
                'lastBuyDateStart' => $this->request->getVar('lastBuyDateStart'), // 最後買進日起
                'lastBuyDateEnd' => $this->request->getVar('lastBuyDateEnd'), // 最後買進日迄
                'deadlineDateStart' => $this->request->getVar('deadlineDateStart'), // 代領截止起
                'deadlineDateEnd' => $this->request->getVar('deadlineDateEnd'), // 代領截止迄
                'giftStatus' => $this->request->getVar('giftStatus'), // 紀念品狀態
                'meetingType' => $this->request->getVar('meetingType'), // 開會性質
                'marketType' => $this->request->getVar('marketType'), // 市場類別
                'documentIds' => $this->request->getVar('documentIds'), // 文件編號們
            ];

            $datas = $this->sgModel->getList($params);
            $items = $this->formatData($datas['items']);
            $result = [
                'items' => $items,
                'total' => $datas['total'],
                'page' => $datas['page'],
                'totalPages' => $datas['totalPages']
            ];

            return $this->successResponse('', $result);
        } catch (\Exception $e) {
            return $this->errorResponse('取得列表時發生錯誤', $e->getMessage());
        }
    }

    // 選項
    public function getOptions()
    {
        try {
            $options = $this->sgModel->getOptions();

            // 取得文件選項
            $options['documents'] = $this->docModel->getOptions();

            return $this->successResponse('', $options);
        } catch (\Exception $e) {
            return $this->errorResponse('取得選項時發生錯誤', $e->getMessage());
        }
    }

    // 新增
    public function create()
    {
        try {
            $this->sgModel->transStart();

            $data = $this->getRequestData();

            if ($this->sgModel->checkDuplicateStockCode($data['sg_StockCode'])) {
                return $this->errorResponse('重複的股號');
            }

            $sgId = $this->sgModel->insert($data);

            if (!$sgId) {
                throw new \Exception('新增股東會資訊失敗');
            }

            // 處理文件組合
            $combinations = json_decode($this->request->getBody(), true)['combinations'] ?? null;

            if (!empty($combinations) && is_array($combinations)) {
                $this->docModel->createCombinations($sgId, $combinations);
            }

            $this->sgModel->transComplete();

            return $this->successResponse('新增成功');
        } catch (\Exception $e) {
            $this->sgModel->transRollback();

            return $this->errorResponse('新增時發生錯誤', $e->getMessage());
        }
    }

    // 詳細
    public function detail($id)
    {
        try {
            $data = $this->sgModel->getDetail($id);

            if (!$data) {
                return $this->errorResponse('找不到對象');
            }

            $result = $this->formatData($data, true);

            return $this->successResponse('', $result);
        } catch (\Exception $e) {
            return $this->errorResponse('取得詳細時發生錯誤', $e->getMessage());
        }
    }

    // 修改
    public function edit($id = null)
    {
        try {
            $this->sgModel->transStart();

            $data = $this->getRequestData();

            $this->sgModel->update($id, $data);

            $combinations = json_decode($this->request->getBody(), true)['documentCombinations'] ?? null;

            if ($combinations !== null) {
                $this->docModel->deleteCombinations($id);

                if (!empty($combinations) && is_array($combinations)) {
                    $this->docModel->createCombinations($id, $combinations);
                }
            }

            $this->sgModel->transComplete();

            return $this->successResponse('修改成功');
        } catch (\Exception $e) {
            return $this->errorResponse('修改時發生錯誤', $e->getMessage());
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
            return $this->errorResponse('刪除時發生錯誤', $e->getMessage());
        }
    }

    // 歷年發放
    public function getHistoricalGifts($stockCode)
    {
        try {
            $datas = $this->sgModel->getHistoricalGifts($stockCode);
            $allIds = array_column($datas, 'sg_Id');
            $allCombinations = $this->docModel->getDocCombinsBySGIds($allIds);
            $items = array_map(function ($item) use ($allCombinations) {
                return [
                    'meetingDate' => $item['sg_MeetingDate'],
                    'meetingType' => StockholderGiftsModel::CODE_TABLES['meetingType'][$item['sg_MeetingType']],
                    'giftName' => $item['p_Name'] ?? null,
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
            return $this->errorResponse('取得歷年發放發生錯誤', $e->getMessage());
        }
    }
    
    private function getRequestData()
    {
        return [
            'sg_StockCode' => $this->request->getVar('stockCode'),
            'sg_StockName' => $this->request->getVar('stockName'),
            'sg_MeetingType' => $this->request->getVar('meetingType'),
            'sg_MeetingDate' => $this->request->getVar('meetingDate'),
            'sg_StockPrice' => $this->request->getVar('stockPrice'),
            'sg_PriceChange' => $this->request->getVar('priceChange'),
            'sg_LastBuyDate' => $this->request->getVar('lastBuyDate'),
            'sg_DeadlineDate' => $this->request->getVar('deadlineDate'),
            'sg_MarketType' => $this->request->getVar('marketType'),
            'sg_ServiceAgent' => $this->request->getVar('serviceAgent'),
            'sg_Phone' => $this->request->getVar('phone'),
            'sg_Year' => $this->request->getVar('year'),
            'sg_VotingDateStart' => $this->request->getVar('votingDateStart'),
            'sg_VotingDateEnd' => $this->request->getVar('votingDateEnd'),
        ];
    }

    private function formatData($items, bool $isSingle = false)
    {
        // 如果是單一紀錄,轉成陣列
        if ($isSingle) {
            $items = [$items];
        }

        $allIds = array_column($items, 'sg_Id');
        $allCombinations = $this->docModel->getDocCombinsBySGIds($allIds);
        $codeTables = \App\Models\StockholderGiftsModel::CODE_TABLES;

        $result = array_map(function ($item) use ($allCombinations, $codeTables) {
            $formattedItem = [
                'id' => $item['sg_Id'],
                'stockCode' => $item['sg_StockCode'],
                'stockName' => $item['sg_StockName'],
                'meetingDate' => $item['sg_MeetingDate'],
                'meetingType' => $codeTables['meetingType'][$item['sg_MeetingType']],
                'giftName' => $item['p_Name'] ?? null,
                'giftImage' => !empty($item['p_Image']) ? base_url('upload/gifts/' . $item['p_Image']) : null,
                'stockPrice' => $item['sg_StockPrice'],
                'priceChange' => $item['sg_PriceChange'],
                'lastBuyDate' => $item['sg_LastBuyDate'],
                'deadlineDate' => $item['sg_DeadlineDate'],
                'marketType' => $codeTables['marketType'][$item['sg_MarketType']],
                'serviceAgent' => $item['sg_ServiceAgent'],
                'phone' => $item['sg_Phone'],
                'year' => $item['sg_Year'],
                'votingDateStart' => $item['sg_VotingDateStart'],
                'votingDateEnd' => $item['sg_VotingDateEnd'],
                'updateDate' => (new \DateTime($item['sg_UpdatedAt']))->format('Y-m-d'),
                'documentCombinations' => $allCombinations[$item['sg_Id']] ?? []
            ];

            return $formattedItem;
        }, $items);

        return $isSingle ? $result[0] : $result;
    }
}
