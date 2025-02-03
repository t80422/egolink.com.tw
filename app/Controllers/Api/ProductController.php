<?php

namespace App\Controllers\Api;

use App\Libraries\ProductService;
use Config\Services;
use Exception;

// 倉庫管理
class ProductController extends BaseApiController
{
    private $productSer;

    public function __construct()
    {
        $this->productSer = new ProductService();
    }

    // 新增
    public function create()
    {
        try {
            $data = [
                'sgId' => $this->request->getVar('sgId'),
                'name' => $this->request->getVar('name')
            ];
            $userId = Services::auth()->getUser()->id;

            $this->productSer->createProduct($data, $this->request->getFile('img'), $userId);

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
            $result = $this->productSer->getList($params);

            return $this->successResponse('', $result);
        } catch (Exception $e) {
            return $this->errorResponse('取得列表時發生錯誤', $e);
        }
    }

    // 詳細
    public function detail($id = null)
    {
        try {
            $result = $this->productSer->getDetail($id);

            return $this->successResponse('', $result);
        } catch (Exception $e) {
            return $this->errorResponse('取得詳細時發生錯誤', $e);
        }
    }

    // 修改
    public function edit($id = null)
    {
        try {
            $data = [
                'name' => $this->request->getVar('name'),
                'qty' => $this->request->getVar('qty')
            ];

            $this->productSer->updateProduct(
                $id,
                $data,
                $this->request->getFile('img'),
                Services::auth()->getUser()->id
            );

            return $this->successResponse();
        } catch (Exception $e) {
            return $this->errorResponse('修改時發生錯誤', $e);
        }
    }

    // 刪除
    public function delete($id = null)
    {
        try {
            $this->productSer->deleteProduct($id);

            return $this->successResponse();
        } catch (Exception $e) {
            return $this->errorResponse('刪除時發生錯誤', $e);
        }
    }

    // 異動列表
    public function inventoryLogs()
    {
        try {
            $params = $this->request->getJSON(true);
            $result = $this->productSer->getInventoryLogList($params);

            return $this->successResponse('', $result);
        } catch (Exception $e) {
            return $this->errorResponse('取得異動列表時發生錯誤', $e);
        }
    }
}
