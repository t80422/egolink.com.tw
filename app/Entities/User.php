<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class User extends Entity
{
    protected $datamap = [
        'id' => 'u_Id',
        'name' => 'u_Name',
        'password' => 'u_Password',
        'roleId' => 'u_r_Id',
        'email' => 'u_Account',
        'verified' => 'u_Verified',
        'verifyToken' => 'u_VerifyToken',
        'verifyExpires' => 'u_VerifyExpires',
        'locationId' => 'u_l_Id',
        'phone' => 'u_Phone',
        'postalCode' => 'u_PostalCode',
        'address' => 'u_Address',
        'parentId' => 'u_ParentId'
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
            'email' => $this->email,
            'phone' => $this->phone,
            'locationId'=>$this->locationId,
            'locationName' => $this->attributes['locationName'],
            'roleId'=>$this->roleId,
            'roleName' => $this->attributes['roleName'],
            'groupId'=>$this->parentId,
            'groupName' => $this->attributes['parentName'],
            'postalCode' => $this->postalCode,
            'address' => $this->address
        ];
    }

    public function formatForOption(): array
    {
        return [
            'value' => $this->id,
            'label' => $this->name
        ];
    }
}
