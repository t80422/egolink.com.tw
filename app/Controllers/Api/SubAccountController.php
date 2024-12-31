<?php

namespace App\Controllers\Api;

use App\Libraries\UploadService;
use App\Models\SubAccountModel;
use Config\Services;
use Exception;

class SubAccountController extends BaseApiController
{
    protected $saModel;
    protected $authSer;
    protected $uploadSer;
    protected const IMG_PATH = "SubAccount/";

    public function __construct()
    {
        $this->saModel = new SubAccountModel();
        $this->authSer = Services::auth();
        $this->uploadSer = new UploadService();
    }

    // 新增
    public function create()
    {
        try {
            $data = $this->getRequestData();
            $data['sa_u_Id'] = $this->authSer->getUser()->id;

            // 檢查身分證是否存在
            if ($this->saModel->where('sa_IdCardNum', $data['sa_IdCardNum'])->first()) {
                return $this->errorResponse('此身份證字號已存在');
            }

            $this->saModel->insert($data);

            return $this->successResponse('新增成功');
        } catch (\Exception $e) {
            return $this->errorResponse('新增時發生錯誤', $e);
        }
    }

    // 列表
    public function index()
    {
        try {
            $params = [
                'page' => $this->request->getVar('page'),
                'sortField' => $this->request->getVar('sortField'),
                'sortOrder' => $this->request->getVar('sortOrder'),
                'keyword' => $this->request->getVar('keyword')
            ];

            $userId = $this->authSer->getUser()->id;
            $items = $this->saModel->getList($userId, $params);
            $result = array_map(fn($item) => $this->formatResponseData($item, true), $items['items']);
            $response = [
                'items' => $result,
                'total' => $items['total'],
                'page' => $items['page'],
                'totalPages' => $items['totalPages']
            ];

            return $this->successResponse('', $response);
        } catch (Exception $e) {
            return $this->errorResponse('取得列表時發生錯誤', $e);
        }
    }

    // 修改
    public function edit($id = null)
    {
        try {
            $subAcc = $this->saModel->find($id);
            if (!$subAcc) {
                return $this->errorResponse('找不到指定子帳號');
            }

            $data = $this->getRequestData();

            $this->saModel->update($id, $data);

            return $this->successResponse('修改成功');
        } catch (Exception $e) {
            $this->errorResponse('修改時發生錯誤', $e);
        }
    }

    // 詳細
    public function detail($id)
    {
        try {
            $data = $this->saModel->find($id);

            if (!$data) {
                return $this->errorResponse('找不到指定子帳號');
            }

            return $this->successResponse('', $this->formatResponseData($data));
        } catch (Exception $e) {
            return $this->errorResponse('取得詳細時發生錯誤', $e);
        }
    }

    // 20241231 不用上傳圖檔
    // // 上傳圖檔
    // public function upload($id)
    // {
    //     try {
    //         $this->saModel->transStart();

    //         $subAcc = $this->saModel->find($id);

    //         if (!$subAcc) {
    //             return $this->errorResponse('找不到指定子帳號');
    //         }

    //         $fieldMap = [
    //             'idCardF' => 'sa_IdCardImg_F',
    //             'idCardB' => 'sa_IdCardImg_B',
    //             'drivingLicense' => 'sa_DLImg',
    //             'healthCard' => 'sa_HICImg'
    //         ];

    //         // 取得上傳檔案
    //         $files = [
    //             'idCardF' => $this->request->getFile('idCardFImg'),
    //             'idCardB' => $this->request->getFile('idCardBImg'),
    //             'drivingLicense' => $this->request->getFile('drivingLicenseImg'),
    //             'healthCard' => $this->request->getFile('healthCardImg')
    //         ];

    //         $basePath = self::IMG_PATH . $id;
    //         $updateData = [];

    //         foreach ($fieldMap as $type => $dbField) {
    //             $file = $files[$type];
    //             $hasOldFile = !empty($subAcc[$dbField]);

    //             // 如果有新檔案上傳
    //             if ($file->isValid()) {
    //                 if ($hasOldFile) {
    //                     $this->uploadSer->deleteFile($subAcc[$dbField], $basePath);
    //                 }

    //                 $newName = $type . '_' . uniqid() . '.' . $file->getExtension();
    //                 $this->uploadSer->uploadFile($file, $basePath, $newName);
    //                 $updateData[$dbField] = $newName;
    //             } else if ($hasOldFile) {
    //                 $this->uploadSer->deleteFile($subAcc[$dbField], $basePath);
    //                 $updateData[$dbField] = null;
    //             }
    //         }

    //         if (!empty($updateData)) {
    //             $this->saModel->update($id, $updateData);
    //         }

    //         $this->saModel->transComplete();

    //         return $this->successResponse();
    //     } catch (Exception $e) {
    //         $this->saModel->transRollback();
    //         return $this->errorResponse('上傳檔案時發生錯誤', $e);
    //     }
    // }

    // 刪除
    public function delete($id = null)
    {
        try {
            $this->saModel->transStart();

            $subAcc = $this->saModel->find($id);

            if (!$subAcc) {
                return $this->errorResponse('找不到指定子帳號');
            }

            // 刪除子帳號的資料夾
            $folderPath = FCPATH . 'upload/' . self::IMG_PATH . $id;

            if (is_dir($folderPath)) {
                // 先刪除資料夾內的檔案
                $files = glob($folderPath . '/*');

                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }

                rmdir($folderPath);
            }

            $this->saModel->delete($id);
            $this->saModel->transComplete();

            return $this->successResponse();
        } catch (Exception $e) {
            $this->saModel->transRollback();
            return $this->errorResponse('刪除時發生錯誤', $e);
        }
    }

    /**
     * 取得請求資料
     *
     * @return array
     */
    private function getRequestData(): array
    {
        $data = [
            'sa_Name' => $this->request->getVar('name'),
            'sa_Memo' => $this->request->getVar('memo'),
        ];

        $idCardNum = $this->request->getVar('idCardNum');

        if ($idCardNum) {
            $data['sa_IdCardNum']  = $idCardNum;
        }

        $voucherType = $this->request->getVar('voucherType');

        if ($voucherType) {
            $data['sa_VoucherType'] = $voucherType;
        }

        return $data;
    }

    /**
     * 格式化回應資料
     *
     * @param [type] $data
     * @return array
     */
    private function formatResponseData($data, bool $isIndex = false): array
    {
        // 取前5碼
        $prefix = substr($data['sa_IdCardNum'], 0, 5);
        $maskedIdCardNum = $prefix . '*****';

        $result = [
            'id' => $data['sa_Id'],
            'idCardNum' => $maskedIdCardNum,
            'name' => $data['sa_Name'],
            'memo' => $data['sa_Memo'] ?? ''
        ];

        if ($isIndex) {
            // 20241231 不用上傳圖檔
            // $result['idCardFrontImg'] = !empty($data['sa_IdCardImg_F']);
            // $result['idCardBackImg'] = !empty($data['sa_IdCardImg_B']);
            // $result['drivingLicenseImg'] = !empty($data['sa_DLImg']);
            // $result['healthCardImg'] = !empty($data['sa_HICImg']);
            $result['idCard'] = (bool)$data['sa_IdCard'];
            $result['drivingLicense'] = (bool)$data['sa_DrivingLicense'];
            $result['healthCard'] = (bool)$data['sa_HIC'];
            $result['hrt'] = (bool)$data['sa_HRT'];
            $result['hc'] = (bool)$data['sa_HC'];
            $result['boov'] = !empty($data['sa_BOOV']);
            $result['owner'] = $data['u_Name'];
        } else {
            $result['voucherType'] = $data['sa_VoucherType'];
        }

        return $result;
    }
}
