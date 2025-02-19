<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class User extends Entity
{
    protected $datamap = [
        'id' => 'u_Id', //編號
        'name' => 'u_Name', // 姓名
        'password' => 'u_Password', // 密碼
        'roleId' => 'u_r_Id', // 角色編號
        'email' => 'u_Account', // 帳號(Email)
        'verified' => 'u_Verified', // 驗證
        'verifyToken' => 'u_VerifyToken', // 驗證Token
        'verifyExpires' => 'u_VerifyExpires', // 驗證期限
        'locationId' => 'u_l_Id', // 據點編號
        'phone' => 'u_Phone', // 手機
        'postalCode' => 'u_PostalCode', // 	郵遞區號
        'address' => 'u_Address', // 地址
        'parentId' => 'u_ParentId', // 群組編號
        'canAutoVote' => 'u_CanAutoVote' // 可否自動投票
    ];

    public function formatForList(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'location' => $this->attributes['locationName'] ?? null,
            'role' => $this->attributes['roleName'] ?? null,
            'group' => $this->attributes['parentName'] ?? null
        ];
    }

    public function formatForDetail(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'locationId' => $this->locationId,
            'roleId' => $this->roleId,
            'groupId' => $this->parentId,
            'postalCode' => $this->postalCode,
            'address' => $this->address,
            'canAutoVote' => $this->canAutoVote
        ];
    }

    public function formatForOption(): array
    {
        return [
            'value' => $this->id,
            'label' => $this->name
        ];
    }

    public function formatForProfile(): array
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "phone" => $this->phone,
            "postalCode" => $this->postalCode,
            "address" => $this->address,
            "locationId" => $this->locationId
        ];
    }
}
