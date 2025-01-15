<?php

namespace App\Libraries;

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

    public function getDetail($id)
    {
        $shipment = $this->shipmentModel->find($id);

        if (!$shipment) {
            throw new Exception('找不到指定資料');
        }

        return $shipment;
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
}
