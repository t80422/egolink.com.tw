<?php

namespace App\Models;

use CodeIgniter\Model;
use Exception;

class RoleFeatureModel extends Model
{
    protected $table            = 'role_features';
    protected $primaryKey       = 'rf_Id';
    protected $allowedFields    = [
        'rf_r_Id',
        'rf_f_Id'
    ];

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];

    /**
     * 更新角色功能權限
     *
     * @param integer $roleId
     * @param array $featureIds
     * @return void
     */
    public function updateRoleFeatures(int $roleId, array $featureIds)
    {
        $this->db->transStart();

        try {
            // 先刪除該角色現有的關聯
            $this->where('rf_r_Id', $roleId)->delete();
            
            // 新增功能
            if (!empty($featureIds)) {
                $datas = array_map(function ($featureId) use ($roleId) {
                    return [
                        'rf_r_Id' => $roleId,
                        'rf_f_Id' => $featureId
                    ];
                }, $featureIds);

                $this->insertBatch($datas);
            }

            $this->db->transComplete();
        } catch (Exception $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    /**
     * 取得角色的功能列表
     *
     * @param integer $roleId
     * @return array
     */
    public function getFeatureIdsByRoleId(int $roleId): array
    {
        return $this->where('rf_r_Id', $roleId)->findAll();
    }

    /**
     * 檢查角色是否擁有特定功能的權限
     *
     * @param integer $roleId
     * @param integer $featureId
     * @return boolean
     */
    public function hasFeature(int $roleId, int $featureId): bool
    {
        return $this->where([
            'rf_r_Id' => $roleId,
            'rf_f_Id' => $featureId
        ])->countAllResults() > 0;
    }
}
