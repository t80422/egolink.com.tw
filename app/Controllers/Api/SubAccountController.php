<?php

namespace App\Controllers\Api;

use App\Entities\SubAccount;
use App\Libraries\SubAccountService;
use Config\Services;
use Exception;

class SubAccountController extends BaseApiController
{
    private $saSer;
    private $authSer;

    public function __construct()
    {
        $this->saSer = new SubAccountService();
        $this->authSer = Services::auth();
    }

    // 新增
    public function create()
    {
        try {
            $data = [
                'name' => $this->request->getVar('name'),
                'idCardNum' => $this->request->getVar('idCardNum'),
                'memo' => $this->request->getVar('memo'),
                'voucherType' => $this->request->getVar('voucherType'),
                'userId' => $this->authSer->getUser()->id
            ];

            $this->saSer->createSubAccount($data);

            return $this->successResponse('新增成功');
        } catch (\Exception $e) {
            return $this->errorResponse('新增時發生錯誤', $e);
        }
    }

    // 列表
    public function index()
    {
        try {
            $params = [
                'page' => $this->request->getVar('page'),
                'keyword' => $this->request->getVar('keyword')
            ];

            $userId = $this->authSer->getUser()->id;
            $result = $this->saSer->getList($userId, $params);
            $result['items'] = array_map(function (SubAccount $item) {
                return $item->formatForList();
            }, $result['items']);

            return $this->successResponse('', $result);
        } catch (Exception $e) {
            return $this->errorResponse('取得列表時發生錯誤', $e);
        }
    }

    // 修改_前台
    public function edit($id = null)
    {
        try {
            $data = [
                'name' => $this->request->getVar('name'),
                'memo' => $this->request->getVar('memo'),
                'voucherType' => $this->request->getVar('voucherType'),
                'boov' => $this->request->getVar('boov'),
                'cdc' => $this->request->getVar('cdc')
            ];

            $userId = $this->authSer->gerUser()->id;
            $this->saSer->updateSubAccount($id, $data, $userId);

            return $this->successResponse('修改成功');
        } catch (Exception $e) {
            $this->errorResponse('修改時發生錯誤', $e);
        }
    }

    // 修改_後台
    public function edit_admin($id = null)
    {
        try {
            $request = $this->request->getJSON(true);
            $this->saSer->updateSubAccount($id, $request);

            return $this->successResponse();
        } catch (Exception $e) {
            $this->errorResponse('修改時發生錯誤', $e);
        }
    }

    // 詳細
    public function detail_Client($id)
    {
        try {
            $userId = $this->authSer->getUser()->id;
            $data = $this->saSer->getDetail($id, $userId);

            return $this->successResponse('', $data->formatForDetail_Client());
        } catch (Exception $e) {
            return $this->errorResponse('取得詳細時發生錯誤', $e);
        }
    }

    // 詳細
    public function detail_Admin($id)
    {
        try {
            $data = $this->saSer->getDetail($id);

            return $this->successResponse('', $data->formatForDetail_Admin());
        } catch (Exception $e) {
            return $this->errorResponse('取得詳細時發生錯誤', $e);
        }
    }

    // 刪除
    public function delete($id = null)
    {
        try {
            $this->saSer->deleteSubAccount($id);

            return $this->successResponse();
        } catch (Exception $e) {
            return $this->errorResponse('刪除時發生錯誤', $e);
        }
    }

    // 查看特定會員的子帳號列表
    public function getUserSubAccounts($userId = null)
    {
        try {
            $params = [
                'page' => $this->request->getVar('page'),
            ];

            $result = $this->saSer->getList($userId, $params, true);
            $result['items'] = array_map(function (SubAccount $item) {
                return $item->formatForList();
            }, $result['items']);

            return $this->successResponse('', $result);
        } catch (Exception $e) {
            return $this->errorResponse('取得列表時發生錯誤', $e);
        }
    }
}
