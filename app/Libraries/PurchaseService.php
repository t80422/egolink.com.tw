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
use Exception;
use Firebase\JWT\ExpiredException;

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
}
