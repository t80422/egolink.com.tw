<?php

namespace App\Libraries;

use App\Entities\Product;
use App\Entities\Purchase;
use App\Entities\PurchaseDetail;
use App\Entities\StockholderGift;
use App\Models\ProductModel;
use App\Models\PurchaseDetailModel;
use App\Models\PurchaseModel;
use App\Models\StockholderGiftsModel;
use CodeIgniter\Validation\Exceptions\ValidationException;
use Config\Services;
use Exception;
use RuntimeException;

class PurchaseService
{
    private $sgModel;
    private $productModel;
    private $purchaseModel;
    private $pdModel;
    private $productSer;

    public function __construct()
    {
        $this->sgModel = new StockholderGiftsModel();
        $this->productModel = new ProductModel();
        $this->purchaseModel = new PurchaseModel();
        $this->pdModel = new PurchaseDetailModel();
        $this->productSer = new ProductService();
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

    public function getProducts(int $sgId): array
    {

        $datas = $this->productModel->getBySGId($sgId);

        return array_map(function (Product $data) {
            return $data->formatForOptions();
        }, $datas);
    }

    public function createPurchase(array $requestData)
    {
        $this->purchaseModel->db->transStart();

        try {
            // 先驗證產品存在性和庫存
            $this->validateProducts($requestData['details']);

            $purchase = new Purchase($requestData);

            if (!$this->purchaseModel->validate($purchase)) {
                throw new ValidationException(implode($this->purchaseModel->errors()));
            }

            $purchaseId = $this->purchaseModel->insert($purchase);

            if (!$purchaseId) {
                throw new Exception('新增進貨單失敗');
            }

            $details = $this->preparePurchaseDetails($purchaseId, $requestData['details']);

            if (!$this->pdModel->insertBatch($details)) {
                throw new Exception('新增明細失敗');
            }

            foreach ($details as $detail) {
                $this->productSer->updateInventory(
                    $detail->productId,
                    $detail->qty,
                    '進貨入庫'
                );
            }

            $this->purchaseModel->db->transComplete();
        } catch (Exception $e) {
            $this->purchaseModel->db->transRollback();
            throw $e;
        }
    }

    public function updatePurchase(int $id, array $requestData)
    {
        $this->purchaseModel->db->transStart();

        try {
            // 檢查進貨單是否存在
            $purchase = $this->purchaseModel->find($id);

            if (!$purchase) {
                throw new Exception('找不到指定進貨單');
            }

            // 取得原有明細資料
            $orgDetails = $this->pdModel->where('pd_pu_Id', $id)->findAll();

            // 驗證並處理明細資料
            $this->validateAndPreparePurchaseDetails($requestData['details']);

            // 更新進貨單
            $purchase->fill($requestData);
            $purchase->updateAt=date('Y-m-d H:i:s');

            if (!$this->purchaseModel->update($id, $purchase)) {
                throw new Exception('修改失敗');
            };

            // 刪除原有明細
            $this->pdModel->where('pd_pu_Id', $id)->delete();

            // 新增新的明細
            $newDetails = $this->preparePurchaseDetails($id, $requestData['details']);

            if (!$this->pdModel->insertBatch($newDetails)) {
                throw new Exception('明細修改失敗');
            };

            // 處理庫存異動
            $this->handleInventoryChanges($orgDetails, $requestData['details']);

            $this->purchaseModel->db->transComplete();
        } catch (Exception $e) {
            $this->purchaseModel->db->transRollback();
            throw $e;
        }
    }

    private function preparePurchaseDetails(int $purchaseId, array $details): array
    {
        return array_map(function ($detail) use ($purchaseId) {
            return new PurchaseDetail([
                'productId' => $detail['productId'],
                'purchaseId' => $purchaseId,
                'qty' => $detail['qty']
            ]);
        }, $details);
    }

    /**
     * 驗證產品資料
     * 
     * @param array $details 進貨明細
     * @throws Exception 當產品不存在時
     */
    private function validateProducts(array $details): void
    {
        foreach ($details as $detail) {
            /** @var Product|null $product */
            $product = $this->productModel->find($detail['productId']);

            if (!$product) {
                throw new Exception("產品 ID {$detail['productId']} 不存在");
            }
        }
    }

    private function validateAndPreparePurchaseDetails(array $details)
    {
        if (empty($details)) {
            throw new ValidationException('至少需要一筆明細');
        }

        $productIds = array_column($details, 'productId');

        if (count($productIds) !== count(array_unique($productIds))) {
            throw new ValidationException('明細中有重複的產品');
        }
    }

    private function handleInventoryChanges(array $orgDetails, array $newDetails)
    {
        // 建立對照表,方便查詢
        $orgQtyMap = [];

        foreach ($orgDetails as $detail) {
            $orgQtyMap[$detail['pd_p_Id']] = $detail['pd_Qty'];
        }

        foreach ($newDetails as $detail) {
            $productId = $detail['productId'];
            $newQty = $detail['qty'];
            $orgQty = $orgQtyMap[$productId] ?? 0;

            // 計算差異數量
            $qtyDiff = $newQty - $orgQty;

            if ($qtyDiff != 0) {
                $this->productSer->updateInventory($productId, $qtyDiff, '進貨單修改');
            };
        }
    }
}
