<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class SubAccount extends Entity
{
    protected $datamap = [
        'id' => 'sa_Id', // 編號
        'idCardNum' => 'sa_IdCardNum', // 身分證字號
        'name' => 'sa_Name', // 姓名
        'memo' => 'sa_Memo', // 備註
        'idCard' => 'sa_IdCard', // 身分證
        'hic' => 'sa_HIC', // 健保卡
        'drivingLicense' => 'sa_DrivingLicense', // 駕照
        'hrt' => 'sa_HRT', // 戶籍謄本
        'hc' => 'sa_HC', // 戶口名簿
        'boov' => 'sa_BOOV', // 券商網路下單憑證
        'cdc' => 'sa_CDC', // 自然人憑證
        'userId' => 'sa_u_Id', // 會員編號
        'voucherType' => 'sa_VoucherType' // 電投憑證種類
    ];

    public function formatForList(): array
    {
        $maskedIdCardNum = $this->getMaskedIdCardNum();

        return [
            'id' => $this->id,
            'idCardNum' => $maskedIdCardNum,
            'name' => $this->name,
            'memo' => $this->memo ?? '',
            'idCard' => (bool)$this->idCard,
            'drivingLicense' => (bool)$this->drivingLicense,
            'hic' => (bool)$this->hic,
            'hrt' => (bool)$this->hrt,
            'hc' => (bool)$this->hc,
            'boov' => $this->boov,
            'cdc' => $this->cdc,
            'owner' => $this->attributes['ownerName'] ?? null
        ];
    }

    public function formatForDetail_Client(): array
    {
        $maskedIdCardNum = $this->getMaskedIdCardNum();

        return [
            'id' => $this->id,
            'idCardNum' => $maskedIdCardNum,
            'name' => $this->name,
            'memo' => $this->memo ?? '',
            'voucherType' => $this->voucherType
        ];
    }

    public function formatForDetail_Admin(): array
    {
        $result = $this->formatForDetail_Client();
        $result['idCard'] = (bool) $this->idCard;
        $result['drivingLicense'] = (bool)$this->drivingLicense;
        $result['hic'] = (bool) $this->hic;
        $result['hrt'] = (bool) $this->hrt;
        $result['hc'] = (bool) $this->hc;

        return $result;
    }

    private function getMaskedIdCardNum(): string
    {
        if (empty($this->idCardNum)) {
            return '';
        }

        $prefix = substr($this->idCardNum, 0, 5);

        return $prefix . '*****';
    }
}
