<?php

namespace App\Models;

use App\Entities\Feature;
use CodeIgniter\Model;

class FeatureModel extends Model
{
    protected $table            = 'features';
    protected $primaryKey       = 'f_Id';
    protected $useAutoIncrement = false;
    protected $returnType       = Feature::class;
    protected $allowedFields    = [
        'f_Name',
        'f_Code'
    ];

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];

    /**
     * 取得角色的功能權限列表
     *
     * @param integer $roleId
     * @return array
     */
    public function getFeaturesByRoleId(int $roleId): array
    {
        return $this->builder('features f')
            ->select('f.*')
            ->join('role_features rf', 'rf.rf_f_Id = f.f_Id')
            ->where('rf.rf_r_Id', $roleId)
            ->get()
            ->getResult($this->returnType);
    }

    /**
     * 檢查角色是否有特定功能的權限
     *
     * @param integer $roleId
     * @param string $featureCode
     * @return boolean
     */
    public function hasPermission(int $roleId, string $featureCode): bool
    {
        return $this->builder('features f')
            ->join('role_features rf', 'rf.rf_f_Id = f.f_Id')
            ->where('rf.rf_r_Id', $roleId)
            ->where('f.f_Code', $featureCode)
            ->countAllResults() > 0;
    }

    public function getByFeatureIds(array $ids):array{
        return $this->builder()-> whereIn('f_Id',$ids)->get()->getResult();
    }
}
