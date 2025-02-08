<?php

namespace App\Libraries;

use App\Models\DocumentModel;
use App\Models\SubAccountModel;

class DocumentService
{
    private $docModel;
    private $saModel;

    private const DOCUMENT_FIELD_MAPPING = [
        6 => 'idCard',         // 身份證
        7 => 'drivingLicense', // 駕照
        8 => 'hic',           // 健保卡
        9 => 'hrt',           // 戶籍謄本
        10 => 'hc'            // 戶口名簿
    ];

    public function __construct()
    {
        $this->docModel = new DocumentModel();
        $this->saModel = new SubAccountModel();
    }

    public function checkDocCompletion(int $saId, int $sgId): bool
    {
        // 取得子帳號資訊
        $subAcc = $this->saModel->find($saId);

        if (!$subAcc) {
            return false;
        }

        // 取得文件組合要求
        $combinations = $this->docModel->getDocCombinsBySGIds([$sgId]);

        if (empty($combinations[$sgId])) {
            return false;
        }


        // 檢查每個可能的文件組合
        foreach ($combinations[$sgId] as $combination) {
            if ($this->isDocCombinationComplete($subAcc->toArray(), $combination['documentIds'])) {
                return true;
            }
        }

        return false;
    }

    public function isDocCombinationComplete(array $subAcc, array $requredDocIds): bool
    {
        $requiredDocs = array_flip($requredDocIds);

        // 檢查每個需要的文件
        foreach ($requiredDocs as $docId => $value) {
            // 檢查這個文件ID是否有對應的欄位映射
            if (!isset(self::DOCUMENT_FIELD_MAPPING[$docId])) {
                continue; // 如果找不到對應的欄位，跳過這個文件
            }

            // 取得對應的欄位名稱
            $fieldName = self::DOCUMENT_FIELD_MAPPING[$docId];

            // 檢查子帳號是否有這個文件
            if ($subAcc[$fieldName] === '1') {
                unset($requiredDocs[$docId]); // 如果有這個文件，從需求清單中移除
            }
        }

        return empty($requiredDocs);
    }
}
