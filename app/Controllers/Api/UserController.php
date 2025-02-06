<?php

namespace App\Controllers\Api;

use App\Entities\User;
use App\Libraries\EmailService;
use App\Models\LocationModel;
use App\Models\UserModel;
use App\Models\RoleModel;
use Config\Services;
use Exception;

//使用者管理
class UserController extends BaseApiController
{
    protected $userModel;
    protected $roleModel;
    protected $locationModel;
    protected $authService;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->roleModel = new RoleModel();
        $this->locationModel = new LocationModel();
        $this->authService = Services::auth();
    }

    // 取得角色選單
    public function getOptions()
    {
        try {
            $currentUser = $this->authService->getUser();
            $roleOptions = [
                ['value' => 2, 'label' => '據點帳號'],
                ['value' => 3, 'label' => '群組帳號'],
                ['value' => 4, 'label' => '一般帳號']
            ];

            if ($currentUser->role === "1") {
                array_unshift($roleOptions, ['value' => 1, 'label' => '總管理者']);
            }

            return $this->successResponse('', $roleOptions);
        } catch (\Exception $e) {
            return $this->errorResponse('取得選單時發生錯誤', $e);
        }
    }

    // 檢查帳號重複
    public function checkAccount()
    {
        try {
            $account = $this->request->getVar('email');
            $exist = $this->userModel->isAccountExist($account);

            $message = $exist ? "帳號重複" : "帳號可用";

            return $this->successResponse($message, [
                'isExist' => $exist
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('檢查帳號重複時發生錯誤', $e);
        }
    }

    // 新增
    public function create()
    {
        try {
            $data = new User([
                'email' => $this->request->getVar('email'),
                'password' => $this->request->getVar('password'),
                'name' => $this->request->getVar('name'),
                'locationId' => $this->request->getVar('locationId'),
                'phone' => $this->request->getVar('phone'),
                'postalCode' => $this->request->getVar('postalCode'),
                'address' => $this->request->getVar('address'),
                'parentId' => $this->request->getVar('groupId'),
                'canAutoVote' => $this->request->getVar('canAutoVote'),
                'roleId' => $this->request->getVar('roleId')
            ]);

            $roleId = $data->roleId;
            $data->roleId = $roleId ?? UserModel::ROLE_NOMAL;
            $userId = $this->userModel->insert($data);

            if (!$userId) {
                return $this->errorResponse('建立帳號失敗');
            }

            // 取得使用者資料
            $user = $this->userModel->find($userId);

            // 發送驗證郵件
            $emailService = new EmailService;
            $emailService->sendVerificationEmail($user->email, $user->verifyToken);

            return $this->successResponse('註冊成功,請檢查您的信箱進行驗證');
        } catch (\Exception $e) {
            return $this->errorResponse('建立帳號時發生錯誤', $e);
        }
    }

    // 修改
    public function edit($id = null)
    {
        try {
            $user = $this->userModel->find($id);

            if (!$user) {
                return $this->errorResponse('找不到使用者');
            }

            $user->name = $this->request->getVar('name');
            $user->roleId = $this->request->getVar('roleId');
            $user->locationId = $this->request->getVar('locationId');
            $user->phone = $this->request->getVar('phone');
            $user->postalCode = $this->request->getVar('postalCode');
            $user->address = $this->request->getVar('address');
            $user->parentId = $this->request->getVar('groupId');

            $this->userModel->update($id, $user);

            return $this->successResponse();
        } catch (\Exception $e) {
            return $this->errorResponse('更新使用者時發生錯誤', $e);
        }
    }

    // 詳細
    public function detail($id = null)
    {
        try {
            $user = $this->userModel->getDetail($id);

            if (!$user) {
                return $this->errorResponse('找不到指定使用者');
            }

            return $this->successResponse('', $user->formatForDetail());
        } catch (\Exception $e) {
            return $this->errorResponse('取得使用者詳細發生錯誤', $e);
        }
    }

    // 刪除
    public function delete($id = null)
    {
        try {
            $user = $this->userModel->find($id);

            if (!$user) {
                return $this->errorResponse('找不到指定使用者');
            }

            if (!$this->userModel->delete($id)) {
                return $this->errorResponse('刪除失敗');
            }

            return $this->successResponse('刪除成功');
        } catch (\Exception $e) {
            return $this->errorResponse('刪除使用者時發生錯誤', $e);
        }
    }

    // 列表
    public function index()
    {
        try {
            $params = $this->request->getGet();
            $datas = $this->userModel->getList($params);

            return $this->successResponse('', $datas);
        } catch (\Exception $e) {
            return $this->errorResponse('取得使用者列表時發生錯誤', $e);
        }
    }

    // 取得個人資料
    public function getProfile()
    {
        try {
            $currentUser = $this->authService->getUser();
            $user = $this->userModel->find($currentUser->id);

            if (!$user) {
                return $this->errorResponse('無法取得個人資料');
            }

            $data = [
                'name' => $user['u_Name'],
                'phone' => $user['u_Phone'],
                'postalCode' => $user['u_PostalCode'],
                'address' => $user['u_Address'],
                'locationId' => $user['u_l_Id']
            ];

            return $this->successResponse('', $data);
        } catch (\Exception $e) {
            return $this->errorResponse('取得個人資料時發生錯誤', $e);
        }
    }

    // 修改個人資料
    public function editProfile()
    {
        try {
            $currentUser = $this->authService->getUser();

            $data = [
                'u_Name' => $this->request->getVar('name'),
                'u_Phone' => $this->request->getVar('phone'),
                'u_PostalCode' => $this->request->getVar('postalCode'),
                'u_Address' => $this->request->getVar('address'),
                'u_l_Id' => $this->request->getVar('locationId'),
            ];

            $this->userModel->update($currentUser->id, $data);

            return $this->successResponse('更新成功');
        } catch (\Exception $e) {
            return $this->errorResponse('更新個人資料時發生錯誤', $e);
        }
    }

    // 更改密碼
    public function changePassword()
    {
        try {
            // 取得請求
            $oldPassword = $this->request->getVar('oldPassword');
            $newPassword = $this->request->getVar('newPassword');

            // 取得用戶資料
            $currentUser = $this->authService->getUser();
            $user = $this->userModel->find($currentUser->id);

            if (!$user) {
                return $this->errorResponse('找不到用戶資料');
            }

            // 驗證舊密碼
            if (!$this->userModel->verifyPassword($oldPassword, $user['u_Password'])) {
                return $this->errorResponse('舊密碼不正確');
            }

            // 確保新密碼與舊密碼不同
            if ($oldPassword === $newPassword) {
                return $this->errorResponse('新密碼不能與舊密碼相同');
            }

            $data = [
                'u_Password' => password_hash($newPassword, PASSWORD_BCRYPT)
            ];

            $this->userModel->update($currentUser->id, $data);

            return $this->successResponse('修改成功');
        } catch (\Exception $e) {
            return $this->errorResponse('更改密碼時發生錯誤', $e);
        }
    }

    // 取得群組帳號
    public function getGroupAccountOptions()
    {
        try {
            $locationId = $this->request->getVar('locationId');

            if (!$locationId) {
                return $this->errorResponse('未選擇據點編號');
            }

            $groups = $this->userModel->getGroupAccountOptionsByLocationId($locationId);
            $options = array_map(function (User $group) {
                return $group->formatForOption();
            }, $groups);

            return $this->successResponse('', $options);
        } catch (Exception $e) {
            return $this->errorResponse('取得群組帳號時發生錯誤', $e);
        }
    }
}
