<?php

namespace App\Controllers\Api;

use App\Libraries\EmailService;
use App\Models\LocationModel;
use App\Models\UserModel;
use App\Models\RoleModel;
use Config\Services;

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
        $this->authService=Services::auth();
    }

    // 取得角色選單
    public function getOptions()
    {
        try {
            $currentUser = $this->authService->getUser();
            $roleOptions = $currentUser->role === 1 ? [
                ['value' => 1, 'label' => '總管理者'],
                ['value' => 2, 'label' => '據點帳號']
            ] :
                [
                    ['value' => 2, 'label' => '據點帳號']
                ];

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

            $message=$exist?"帳號重複":"帳號可用";

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
            // 從請求中取得資料
            $data = [
                'u_Account' => $this->request->getVar('email'),
                'u_Password' => $this->request->getVar('password'),
                'u_Name' => $this->request->getVar('name'),
                'u_l_Id' => $this->request->getVar('locationId'),
                'u_Phone' => $this->request->getVar('phone'),
                'u_PostalCode' => $this->request->getVar('postalCode'),
                'u_Address' => $this->request->getVar('address')
            ];

            $roleId = $this->request->getVar('roleId');

            if ($roleId !== null) {
                $data['u_r_Id'] = $roleId;
            } else {
                $data['u_r_Id'] = 4;
            }

            $userId = $this->userModel->insert($data);

            if (!$userId) {
                return $this->errorResponse('建立帳號失敗');
            }

            // 取得使用者資料
            $user = $this->userModel->find($userId);

            // 發送驗證郵件
            $emailService = new EmailService;
            $emailService->sendVerificationEmail($user['u_Account'], $user['u_VerifyToken']);

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

            $data = [
                'u_Name' => $this->request->getVar('name'),
                'u_r_Id' => $this->request->getVar('roleId'),
                'u_l_Id' => $this->request->getVar('locationId'),
                'u_Phone' => $this->request->getVar('phone'),
                'u_PostalCode' => $this->request->getVar('postalCode'),
                'u_Address' => $this->request->getVar('address')
            ];

            if (!$this->userModel->update($id, $data)) {
                return $this->errorResponse('更新失敗');
            }

            return $this->successResponse('更新成功');
        } catch (\Exception $e) {
            return $this->errorResponse('更新使用者時發生錯誤', $e);
        }
    }

    // 詳細
    public function detail($id = null)
    {
        try {
            $user = $this->userModel->find($id);

            if (!$user) {
                return $this->errorResponse('找不到指定使用者');
            }

            $data = [
                'id' => $user['u_Id'],
                'name' => $user['u_Name'],
                'roleId' => $user['u_r_Id'],
                'locationId' => $user['u_l_Id'],
                'email' => $user['u_Account'],
                'phone' => $user['u_Phone'],
                'postalCode' => $user['u_PostalCode'],
                'address' => $user['u_Address']
            ];

            return $this->successResponse('', $data);
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
            // 取得分頁參數
            $page = $this->request->getVar('page') ?? 1;
            $limit = 10;
            $offset = ($page - 1) * $limit;

            // 取得篩選條件
            $roleId = $this->request->getVar('roleId');
            $locationId = $this->request->getVar('locationId');
            $keyword = $this->request->getVar('keyword');

            // 建立查詢
            $builder = $this->userModel->select('
                users.u_Id,
                users.u_Account,
                users.u_Name,
                roles.r_Name as roleName,
                locations.l_Name as locationName
            ')
                ->join('roles', 'roles.r_Id = users.u_r_Id')
                ->join('locations', 'locations.l_Id = users.u_l_Id', 'left');

            // 加入篩選條件
            if (!empty($roleId)) {
                $builder->where('users.u_r_Id', $roleId);
            }

            if (!empty($locationId)) {
                $builder->where('users.u_l_Id', $locationId);
            }

            if (!empty($keyword)) {
                $builder->groupStart()
                    ->like('users.u_Name', $keyword)
                    ->orLike('users.u_Account', $keyword)
                    ->groupEnd();
            }

            // 計算總筆數
            $total = $builder->countAllResults(false);

            // 加入分頁並取得資料
            $users = $builder->limit($limit, $offset)->find();

            // 格式化資料
            $items = array_map(function ($user) {
                return [
                    'id' => $user['u_Id'],
                    'email' => $user['u_Account'],
                    'name' => $user['u_Name'],
                    'roleName' => $user['roleName'],
                    'locationName' => $user['locationName'],
                ];
            }, $users);

            // 回傳資料
            $data = [
                'items' => $items,
                'total' => $total,
                'page' => $page,
                'totalPages' => ceil($total / $limit)
            ];

            return $this->successResponse('', $data);
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
            $currentUser=$this->authService->getUser();
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
}
