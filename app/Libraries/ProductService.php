<?php

namespace App\Libraries;

use App\Entities\InventoryLog;
use App\Entities\Product;
use App\Models\InventoryLogModel;
use App\Models\ProductModel;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Services;
use Exception;

class ProductService
{
    private $productModel;
    private $iLogModel;
    private $uploadSer;
    private $authSer;

    private const UPLOAD_DIR = 'gifts';

    public function __construct()
    {
        $this->productModel = new ProductModel();
        $this->iLogModel = new InventoryLogModel();
        $this->uploadSer = new UploadService();
        $this->authSer = Services::auth();
    }

    public function createProduct(array $data, ?UploadedFile $img, int $userId)
    {
        $this->productModel->db->transStart();

        try {
            // 建立實體
            $product = new Product([
                'sgId' => $data['sgId'],
                'name' => $data['name'],
                'qty' => 0,
                'createdBy' => $userId
            ]);

            // 驗證數據
            if (!$this->productModel->validate($product->toRawArray())) {
                throw new Exception(implode(', ', $this->productModel->errors()));
            }

            // 設置排序
            $product->sequence = $this->productModel->getNextSequence($product->sgId);

            // 處理圖片上傳
            $product->img = $this->handelImgUpload($img);

            // 新增
            $productId = $this->productModel->insert($product);

            $this->productModel->db->transComplete();
        } catch (Exception $e) {
            $this->productModel->db->transRollback();
            throw $e;
        }
    }

    public function getList(array $params = []): array
    {
        $result = $this->productModel->getList($params);

        $result['items'] = array_map(function ($item) {
            return [
                'id' => $item->id,
                'stock' => $item->getStockInfo()['code'] . ' ' . $item->getStockInfo()['name'],
                'name' => $item->name,
                'img' => $item->getImgUrl(),
                'qty' => $item->qty,
                'createdBy' => $item->getCreator(),
                'createdAt' => $item->createdAt,
                'updatedBy' => $item->getUpdater(),
                'updatedAt' => $item->updatedAt,
                'sequence' => $item->sequence
            ];
        }, $result['items']);

        return $result;
    }

    public function getDetail(int $id): array
    {
        $data = $this->productModel->getDetail($id);

        if (!$data) {
            throw new Exception('找不到指定資料');
        }

        return [
            'id' => $data->id,
            'sgId' => $data->sgId,
            'name' => $data->name,
            'img' => $data->getImgUrl(),
            'qty' => $data->qty,
            'creator' => $data->getCreator(),
            'createdAt' => $data->createdAt,
            'updater' => $data->getUpdater(),
            'updatedAt' => $data->updatedAt
        ];
    }

    public function updateProduct(int $id, array $data, ?UploadedFile $img, int $userId)
    {
        $this->productModel->db->transStart();

        try {
            $product = $this->productModel->find($id);

            if (!$product) {
                throw new Exception('找不到指定資料');
            }

            $updateProduct = new Product([
                'name' => $data['name'],
                'qty' => $data['qty'],
                'updatedBy' => $userId
            ]);

            // 紀錄庫存變更
            if ($updateProduct->qty != $product->qty) {
                $ilData = new InventoryLog([
                    'pId' => $id,
                    'type' => InventoryLog::TYPE_MODIFY,
                    'qty' => $updateProduct->qty,
                    'beforeQty' => $product->qty,
                    'user' => $userId,
                    'memo' => '修改數量'
                ]);

                // 驗證數據
                if (!$this->iLogModel->validate($ilData->toRawArray())) {
                    throw new Exception(implode(', ', $this->iLogModel->errors()));
                }

                $this->iLogModel->insert($ilData);
            }

            // 處理圖片更新
            if ($img && $img->isValid()) {
                $newName = $this->handelImgUpload($img);

                if ($newName) {
                    if ($product->img) {
                        $this->uploadSer->deleteFile($product->img, self::UPLOAD_DIR);
                    }

                    $updateProduct->img = $newName;
                }
            }

            if (!$this->productModel->validate($updateProduct->toRawArray())) {
                throw new Exception(implode(', ', $this->productModel->errors()));
            }

            $this->productModel->update($id, $updateProduct);

            return $this->productModel->db->transComplete();
        } catch (Exception $e) {
            $this->productModel->db->transRollback();

            throw $e;
        }
    }

    public function deleteProduct(int $id)
    {
        $this->productModel->db->transStart();

        try {
            $product = $this->productModel->find($id);

            if (!$product) {
                throw new Exception('找不到指定資料');
            }

            // 刪除資料
            $this->productModel->delete($id);

            // 刪除圖片
            if ($product->img) {
                $this->uploadSer->deleteFile($product->img, self::UPLOAD_DIR);
            }

            $this->productModel->db->transComplete();
        } catch (Exception $e) {
            $this->productModel->db->transRollback();
            throw $e;
        }
    }

    public function getInventoryLogList(array $params = []): array
    {
        $result = $this->iLogModel->getList($params);

        $result['items'] = array_map(function ($item) {
            $log = new InventoryLog((array)$item);

            return [
                'time' => $log->createdAt,
                'stock' => $log->stockCode . ' ' . $log->stockName,
                'productName' => $log->productName,
                'type' => $log->getTypeName(),
                'qty' => $log->qty,
                'beforeQty' => $log->beforeQty,
                'user' => $log->userName
            ];
        }, $result['items']);

        return $result;
    }

    public function updateInventory(int $productId, int $diffQty, string $memo = null)
    {
        $product = $this->productModel->find($productId);

        if (!$product) {
            throw new Exception('找不到指定紀念品');
        }

        $newQty = ($product->qty ?? 0) + $diffQty;

        $logData = new InventoryLog([
            'pId' => $product->id,
            'type' => InventoryLog::TYPE_IN,
            'qty' => $newQty,
            'beforeQty' => $product->qty,
            'user' => $this->authSer->getUser()->id,
            'memo' => $memo
        ]);

        $product->qty = $newQty;

        if (!$this->productModel->update($productId, $product)) {
            throw new Exception('更新庫存失敗');
        }

        if (!$this->iLogModel->insert($logData)) {
            throw new Exception('新增異動紀錄失敗');
        }
    }

    /**
     * 處理圖片上傳
     *
     * @param UploadedFile|null $file
     * @return string|null
     */
    private function handelImgUpload(?UploadedFile $file): ?string
    {
        if (!$file || !$file->isValid()) {
            log_message('debug', '上傳有問題');
            return null;
        }

        $newName = $file->getRandomName();
        $this->uploadSer->uploadFile($file, self::UPLOAD_DIR, $newName);

        return $newName;
    }
}
