<?php

namespace App\Libraries;

use App\Entities\SubAccount;
use App\Models\SubAccountModel;
use App\Models\UserModel;
use Exception;

class SubAccountService
{
    protected $saModel;

    public function __construct()
    {
        $this->saModel = new SubAccountModel();
    }

    public function createSubAccount(array $data)
    {
        if ($this->saModel->isIdCardNumExists($data['idCardNum'])) {
            throw new Exception('此身份證字號已存在');
        }

        $subAccount = new SubAccount($data);
        if(!$this->saModel->insert($subAccount)){
            throw new Exception('新增失敗');
        }
    }

    public function updateSubAccount(int $id, array $data, ?int $userId = null)
    {
        $subAccount = $this->saModel->find($id);

        if (!$subAccount) {
            throw new Exception('找不到指定資料');
        }

        if ($userId  && $subAccount->userId !== $userId) {
            throw new Exception('無權限編輯此子帳號');
        }

        $subAccount->fill($data);

        $this->saModel->update($id, $subAccount);
    }

    public function deleteSubAccount(int $id)
    {
        $subAccount = $this->saModel->find($id);

        if (!$subAccount) {
            throw new Exception('找不到指定資料');
        }

        $this->saModel->delete($id);
    }

    public function getList(int $userId, array $params = [], bool $isAdmin = false): array
    {
        if ($isAdmin) {
            $userModel = new UserModel();

            if (!$userModel->find($userId)) {
                throw new Exception('找不到指定資料');
            }
        }

        return $this->saModel->getList($userId, $params);
    }

    public function getDetail(int $id, ?int $userId = null): SubAccount
    {
        $subAccount = $this->saModel->find($id);

        if (!$subAccount) {
            throw new Exception('找不到指定資料');
        }

        if ($userId && $subAccount->userId !== $userId) {
            throw new Exception('無權限查看此子帳號');
        }

        return $subAccount;
    }

    public function getAutoVoteSubAccounts(int $userId): array
    {
        $datas = $this->saModel->getAutoVoteData($userId);

        return $datas;
    }
}
