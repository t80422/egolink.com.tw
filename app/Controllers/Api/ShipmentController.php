<?php

namespace App\Controllers\Api;

use App\Libraries\ShipmentService;
use Exception;

class ShipmentController extends BaseApiController
{
    private $shipmentSer;

    public function __construct()
    {
        $this->shipmentSer = new ShipmentService();
    }

    // 新增
    public function create()
    {
        try {
            $data = $this->request->getJSON(true);

            $this->shipmentSer->createShipment($data);

            return $this->successResponse();
        } catch (Exception $e) {
            return $this->errorResponse('新增時發生錯誤', $e);
        }
    }

    // 列表
    public function index()
    {
        try {
            $params = $this->request->getGet();
            $result = $this->shipmentSer->getList($params);

            return $this->successResponse('', $result);
        } catch (Exception $e) {
            return $this->errorResponse('取得列表時發生錯誤', $e);
        }
    }

    // 詳細
    public function detail($id = null)
    {
        try {
            $data = $this->shipmentSer->getDetail((int)$id);

            return $this->successResponse('', $data);
        } catch (Exception $e) {
            return $this->errorResponse('取得詳細時發生錯誤', $e);
        }
    }

    // 編輯
    public function edit($id = null)
    {
        try {
            $data = $this->request->getJSON(true);
            $this->shipmentSer->updateShipment($id, $data);

            return $this->successResponse();
        } catch (Exception $e) {
            return $this->errorResponse('編輯時發生錯誤', $e);
        }
    }

    // 刪除
    public function delete($id = null)
    {
        try {
            $this->shipmentSer->deleteShipment($id);

            return $this->successResponse();
        } catch (Exception $e) {
            return $this->errorResponse('刪除時發生錯誤', $e);
        }
    }

    // 取得可出貨會員列表
    public function getShippableUsers()
    {
        try {
            $params = $this->request->getGet();
            $result = $this->shipmentSer->getShippableUsers($params);

            return $this->successResponse('', $result);
        } catch (Exception $e) {
            return $this->errorResponse('取得可出貨會員列表時發生錯誤', $e);
        }
    }

    // 取得用戶的委託紀念品統計
    public function getOrderSummary($userId = null)
    {
        try {
            $result = $this->shipmentSer->getOrderSummary($userId);

            return $this->successResponse('', $result);
        } catch (Exception $e) {
            return $this->errorResponse('取得用戶的委託紀念品統計錯誤', $e);
        }
    }
}
