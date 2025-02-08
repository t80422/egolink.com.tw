<?php

namespace App\Controllers\Api;

use App\Entities\StockholderGift;
use App\Libraries\OrderService;
use App\Models\OrderModel;
use App\Models\SubAccountModel;
use Config\Services;
use Exception;

// 委託
class OrderController extends BaseApiController
{
    protected $ordModel;
    protected $saModel;
    protected $authSer;
    private $orderSer;

    public function __construct()
    {
        $this->ordModel = new OrderModel();
        $this->saModel = new SubAccountModel();
        $this->authSer = Services::auth();
        $this->orderSer = new OrderService();
    }

    // 建立批次委託
    public function batchCreate()
    {
        try {
            $sgIds = $this->request->getVar('stockIds');

            if (empty($sgIds) || !is_array($sgIds)) {
                return $this->errorResponse('請選擇對象');
            }

            $userId = $this->authSer->getUser()->id;
            $subAccs = $this->saModel->where('sa_u_Id', $userId)->findAll();

            if (empty($subAccs)) {
                return $this->errorResponse('沒有可用的子帳號');
            }

            $subAccIds = array_column($subAccs, 'id');
            $this->ordModel->batchCreate($sgIds, $subAccIds);

            return $this->successResponse();
        } catch (Exception $e) {
            return $this->errorResponse('建立批次委託時發生錯誤', $e);
        }
    }

    // 列表
    public function index()
    {
        try {
            $params = $this->request->getGet();
            $userId = $this->authSer->getUser()->id;
            $subAccIds = $this->saModel->getIdsByUserId($userId);

            $params['subAccountIds'] = array_column($subAccIds, 'id');
            $result = $this->ordModel->getList($params);

            return $this->successResponse('', $result);
        } catch (Exception $e) {
            return $this->errorResponse('取得列表時發生錯誤', $e);
        }
    }

    // 詳細
    public function detail()
    {
        try {
            $id = $this->request->getGet('id');
            $memo = $this->ordModel->getDetail($id);

            return $this->successResponse('', [
                'id' => $id,
                'memo' => $memo
            ]);
        } catch (Exception $e) {
            $this->errorResponse('取得詳細時發生錯誤', $e);
        }
    }

    // 修改
    public function edit($id = null)
    {
        try {
            $order = $this->ordModel->find($id);

            if (!$order) {
                return $this->errorResponse('找不到指定委託');
            }

            $data = [
                'o_Memo' => $this->request->getVar('memo')
            ];

            $this->ordModel->update($id, $data);

            return $this->successResponse();
        } catch (Exception $e) {
            return $this->errorResponse('修改時發生錯誤', $e);
        }
    }

    // 刪除
    public function delete($id = null)
    {
        try {
            $order = $this->ordModel->find($id);

            if (!$order) {
                return $this->errorResponse('找不到指定委託');
            }

            $this->ordModel->delete($id);

            return $this->successResponse();
        } catch (Exception $e) {
            return $this->errorResponse('刪除時發生錯誤', $e);
        }
    }

    // 取得更多條件
    public function getMoreConditions()
    {
        try {
            $options = [];
            $codeTables = ['meetingType', 'marketType'];

            // 只取需要的選項
            foreach ($codeTables as $type) {
                $options[$type] = array_map(function ($code, $name) {
                    return [
                        'value' => $code,
                        'label' => $name
                    ];
                }, array_keys(StockholderGift::CODE_TABLES[$type]), array_values(StockholderGift::CODE_TABLES[$type]));
            }

            return $this->successResponse('', $options);
        } catch (Exception $e) {
            return $this->errorResponse('取得更多條件錯誤', $e);
        }
    }

    // 投票圖片上傳
    public function uploadVoteImg($id = null)
    {
        try {
            // 檢查是否有上傳圖片
            $img = $this->request->getFile('img');

            if (!$img || !$img->isValid()) {
                return $this->errorResponse('請選擇要上傳的圖片');
            }

            // 處理圖片上傳
            $this->orderSer->handleVoteImgUpload((int)$id, $img);

            return $this->successResponse();
        } catch (Exception $e) {
            return $this->errorResponse('投票圖片上傳錯誤', $e);
        }
    }
}
