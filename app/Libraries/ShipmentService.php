<?php

namespace App\Libraries;

use App\Entities\Order;
use App\Entities\Shipment;
use App\Models\OrderModel;
use App\Models\ShipmentModel;
use Exception;

class ShipmentService
{
    protected $shipmentModel;
    protected $orderModel;

    public function __construct()
    {
        $this->shipmentModel = new ShipmentModel();
        $this->orderModel = new OrderModel();
    }

    public function createShipment(array $data)
    {
        $this->shipmentModel->db->transStart();

        try {
            // 新增出貨單
            $shipment = new Shipment([
                'number' => $this->shipmentModel->generateShipmentNum(),
                'date' => $data['date'],
                'memo' => $data['memo']
            ]);

            $shipmentId = $this->shipmentModel->insert($shipment);

            if (!$shipmentId) {
                throw new Exception('新增失敗');
            }

            // 取得用戶的子帳號
            $orderIds = $this->orderModel->getShippableOrderIdsByUserId($data['userId']);

            // 更新訂單狀態
            if (!$this->orderModel->updatedToShipped($orderIds, $shipmentId)) {
                throw new Exception('更新訂單狀態失敗');
            }

            $this->shipmentModel->db->transComplete();
        } catch (Exception $e) {
            $this->shipmentModel->db->transRollback();
            throw $e;
        }
    }

    public function getList($params = []): ?array
    {
        return $this->shipmentModel->getList($params);
    }

    public function getDetail(int $id)
    {
        // 取得出貨單資訊
        $shipment = $this->shipmentModel->find($id);

        if (!$shipment) {
            throw new Exception('找不到指定資料');
        }

        // 取得用戶資訊
        $userInfo = $this->shipmentModel->getUserInfo($id);

        // 取得紀念品明細
        $items = $this->shipmentModel->getProductDetails($id);

        return [
            'id' => $shipment->id,
            'number' => $shipment->number,
            'date' => $shipment->date,
            'memo' => $shipment->memo,
            'createdAt' => $shipment->createdAt,
            'userName' => $userInfo['userName'],
            'phone' => $userInfo['phone'],
            'items' => $items
        ];
    }

    public function updateShipment(int $id, array $data)
    {
        $shipment = $this->shipmentModel->find($id);

        if (!$shipment) {
            throw new Exception('找不到指定資料');
        }

        $shipment->fill([
            'date' => $data['date'] ?? $shipment->date,
            'memo' => $data['memo'] ?? $shipment->memo
        ]);

        $this->shipmentModel->update($id, $shipment);
    }

    public function deleteShipment($id)
    {
        $this->shipmentModel->db->transStart();

        try {
            // 將委託單狀態改回待出貨
            $this->orderModel->backToPending($id);

            // 刪除出貨單
            $this->shipmentModel->delete($id);

            $this->shipmentModel->db->transComplete();
        } catch (Exception $e) {
            $this->shipmentModel->db->transRollback();
            throw $e;
        }
    }

    public function getShippableUsers(array $params = []): array
    {
        $result = $this->orderModel->getShippableUsers($params);
        $result['items'] = array_map(function (Order $item) {
            return $item->formatShippableUser();
        }, $result['items']);

        return $result;
    }

    public function getOrderSummary(int $userId): array
    {
        $items = $this->orderModel->getProductSummaryByUserId($userId);

        return array_map(function ($item) {
            return [
                'stock' => $item['sg_StockCode'] . ' ' . $item['sg_StockName'],
                'productName' => $item['productName'],
                'qty' => (int)$item['qty']
            ];
        }, $items);
    }
}
