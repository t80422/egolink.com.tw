<?php

namespace App\Controllers\Api;

use App\Entities\StockholderGift;
use App\Libraries\PurchaseService;
use App\Models\PurchaseDetailModel;
use App\Models\PurchaseModel;
use Exception;

class PurchaseController extends BaseApiController
{
    protected $purchaseModel;
    protected $pdModel;
    private $purchaseSer;

    public function __construct()
    {
        $this->purchaseModel = new PurchaseModel();
        $this->pdModel = new PurchaseDetailModel();
        $this->purchaseSer = new PurchaseService();
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

            if (empty($requestData['details']) || !is_array($requestData['details'])) {
                throw new Exception('列表不能為空');
            }

            $purchaseId = $this->purchaseModel->insert($data);

            if (!$purchaseId) {
                throw new Exception('新增失敗');
            }

            $details = [];

            foreach ($requestData['details'] as $detail) {
                $details[] = [
                    'pd_p_Id' => $detail['productId'],
                    'pd_pu_Id' => $purchaseId,
                    'pd_Qty' => $detail['qty']
                ];
            }

            if ($this->pdModel->insertBatch($details)) {
                throw new Exception('新增明細失敗');
            };
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
                return [
                    'id' => $item['id'],
                    'date' => $item['date'],
                    'memo' => $item['memo']
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

    // 取得股票選單
    public function getSGOptions()
    {
        try {
            $result = $this->purchaseSer->getSGOptions();

            return $this->successResponse('', $result);
        } catch (Exception $e) {
            return $this->errorResponse('取得初始資料時發生錯誤', $e);
        }
    }

    // 取得紀念品
    public function getProductOptions()
    {
        try {
            $sgId = $this->request->getVar('sgId');
            $result = $this->purchaseSer->getProducts($sgId);

            return $this->successResponse('', $result);
        } catch (Exception $e) {
            return $this->errorResponse('取得紀念品時發生錯誤', $e);
        }
    }

    private function validateRequest($data)
    {
        if (empty($data['pu_Date'])) {
            throw new Exception('日期為必填');
        }
    }
}
