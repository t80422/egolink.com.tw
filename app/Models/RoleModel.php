<?php

namespace App\Models;

use CodeIgniter\Model;

class RoleModel extends Model
{
    protected $table = 'roles';
    protected $primaryKey = 'r_Id';
    protected $allowedFields = ['r_Name'];
    protected $returnType = 'array';

    // 依Id取得角色名稱
    public function getRoleName($roleId)
    {
        $role = $this->find($roleId);
        return $role ? $role['r_Name'] : null;
    }
}