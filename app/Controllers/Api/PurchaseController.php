<?php

namespace App\Controllers\Api;

use App\Models\PurchaseDetailModel;
use App\Models\PurchaseModel;
use Exception;

class PurchaseController extends BaseApiController
{
    protected $purchaseModel;
    protected $pdModel;

    public function __construct()
    {
        $this->purchaseModel = new PurchaseModel();
        $this->pdModel = new PurchaseDetailModel();
    }

    // 新增
    public function create()
    {
        try {
            $this->purchaseModel->db->transStart();
            $requestData = $this->request->getJSON(true);

            $data = [
                'pu_Date' => $requestData['date'],
                'pu_Memo' => $requestData['memo']
            ];

            $this->validateRequest($data);

            $purchaseId = $this->purchaseModel->insert($data);
            $details = [];

            foreach ($requestData['details'] as $detail) {
                $details[] = [
                    'pd_p_Id' => $detail['product'],
                    'pd_pu_Id' => $purchaseId,
                    'pd_Qty' => $detail['qty']
                ];
            }

            $this->pdModel->insertBatch($details);
            $this->purchaseModel->db->transComplete();

            return $this->successResponse();
        } catch (Exception $e) {
            $this->purchaseModel->db->transRollback();
            return $this->errorResponse('新增時發生錯誤', $e);
        }
    }

    // 列表
    public function index()
    {
        try {
            // 搜尋條件
            $params = [
                'page' => (int) $this->request->getVar('page'),
                'startDate' => $this->request->getVar('startDate'),
                'endDate' => $this->request->getVar('endDate'),
                'keyword' => $this->request->getVar('keyword')
            ];

            // 取得資料
            $datas = $this->purchaseModel->getList($params);

            // 格式化
            $formatItems = array_map(function ($item) {
                $details = $item['details'];
                $stocks = [];

                foreach ($details as $detail) {
                    $stockKey = $detail['sg_StockCode'] . '-' . $detail['sg_StockName'];

                    if (!isset($stocks[$stockKey])) {
                        $stocks[$stockKey] = [
                            'code' => $detail['sg_StockCode'],
                            'name' => $detail['sg_StockName'],
                            'details' => []
                        ];
                    }

                    $stocks[$stockKey]['details'][] = [
                        'name' => $detail['p_Id'],
                        'qty' => $detail['pd_Qty']
                    ];
                }

                return [
                    'id' => $item['id'],
                    'date' => $item['date'],
                    'memo' => $item['memo'],
                    'details' => array_values($stocks)
                ];
            }, $datas['items']);

            $response = [
                'page' => $datas['page'],
                'totalPages' => $datas['totalPages'],
                'total' => $datas['total'],
                'items' => $formatItems
            ];
            return $this->successResponse('', $response);
        } catch (Exception $e) {
            return $this->errorResponse('取得列表時發生錯誤', $e);
        }
    }

    // 詳細
    public function detail($id = null)
    {
        try {
            $data = $this->purchaseModel->getDetail($id);

            if (!$data) {
                return $this->errorResponse('找不到指定對象');
            }

            return $this->successResponse('', $data);
        } catch (Exception $e) {
            return $this->errorResponse('取得詳細時發生錯誤', $e);
        }
    }

    // 修改
    public function edit($id = null)
    {
        try {
            if (!$this->purchaseModel->find($id)) {
                return $this->errorResponse('找不到指定資料');
            }

            $requestData = $this->request->getJson(true);

            $this->validateRequest($requestData);

            $data = [
                'pu_Date' => $requestData['date'],
                'memo' => $requestData['memo'] ?? ''
            ];


            $this->purchaseModel->updatePurchase($id, $data, $requestData['details']);

            return $this->successResponse();
        } catch (Exception $e) {
            return $this->errorResponse('修改時發生錯誤', $e);
        }
    }

    // 刪除
    public function delete($id = null)
    {
        try {
            if (!$this->purchaseModel->find($id)) {
                return $this->errorResponse('找不到指定資料');
            }

            $this->purchaseModel->deletePurchase($id);

            return $this->successResponse();
        } catch (Exception $e) {
            return $this->errorResponse('刪除時發生錯誤', $e);
        }
    }

    private function validateRequest($data)
    {
        if (empty($data['date'])) {
            throw new Exception('日期為必填');
        }

        if (empty($data['details']) || !is_array($data['details'])) {
            throw new Exception('列表不能為空');
        }

        foreach ($data['details'] as $detail) {
            if (empty($detail['productId']) || !isset($detail['qty']) || $detail['qty'] <= 0) {
                throw new Exception('明細資料錯誤');
            }
        }
    }
}
