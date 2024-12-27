<?php

namespace App\Controllers\Api;

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

    public function __construct()
    {
        $this->ordModel = new OrderModel();
        $this->saModel = new SubAccountModel();
        $this->authSer = Services::auth();
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

            $subAccIds = array_column($subAccs, 'sa_Id');

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
            $params = [
                'page' => $this->request->getVar('page'),
                'sortField' => $this->request->getVar('sortField'),
                'sortOrder' => $this->request->getVar('sortOrder'),
                'year' => $this->request->getVar('year'),
                'status' => $this->request->getVar('status'),
                'documentIds' => $this->request->getVar('documentIds'),
                'deadlineStart' => $this->request->getVar('deadlineStart'),
                'deadlineEnd' => $this->request->getVar('deadlineEnd'),
                'meetingType' => $this->request->getVar('meetingType'),
                'marketType' => $this->request->getVar('marketType'),
                'keyword' => $this->request->getVar('keyword')
            ];

            $userId = $this->authSer->getUser()->id;
            $subAccIds = $this->saModel->where('sa_u_Id', $userId)
                ->select('sa_Id')
                ->findAll();

            $params['subAccountIds'] = array_column($subAccIds, 'sa_Id');
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
}
