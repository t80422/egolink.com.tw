<?php

namespace App\Controllers\Api;

use App\Models\LocationModel;
use App\Libraries\UploadService;

class LocationController extends BaseApiController
{
    protected LocationModel $locationModel;
    protected UploadService $uploadSer;

    private $fileDir = 'locations';

    public function __construct()
    {
        $this->locationModel = new LocationModel;
        $this->uploadSer = new uploadService;
    }

    // 列表
    public function index()
    {
        try {
            $locations = $this->locationModel->findAll();

            $format = array_map(function ($location) {
                return [
                    'id' => $location['l_Id'],
                    'name' => $location['l_Name'],
                    'phone' => $location['l_Phone'],
                    'address' => $location['l_Address'],
                    'lineLink' => $location['l_LineLink'],
                    'imgUrl' => $location['l_Image'] ? base_url('upload/' . $this->fileDir . '/' . $location['l_Image']) : null
                ];
            }, $locations);

            $data = [
                'items' => $format
            ];

            return $this->successResponse('Success', $data);
        } catch (\Exception $e) {
            return $this->errorResponse('取得據點列表時發生錯誤', $e);
        }
    }

    // 新增
    public function create()
    {
        try {
            $data = $this->getDataFromRequest();
            $image = $this->request->getFile('image');
            $newName = null;

            if ($image && $image->isValid()) {
                $newName = $image->getRandomName();
                $data['l_Image'] = $newName;
            }

            $this->locationModel->insert($data);

            if ($image && $newName) {
                $this->uploadSer->uploadFile($image, $this->fileDir, $newName);
            }

            return $this->successResponse('新增成功');
        } catch (\Exception $e) {
            return $this->errorResponse('新增據點時發生錯誤', $e);
        }
    }

    // 詳細
    public function detail($id = null)
    {
        try {
            $location = $this->locationModel->find($id);

            if (!$location) {
                return $this->errorResponse('找不到指定據點');
            }

            $data = [
                'id' => $location['l_Id'],
                'name' => $location['l_Name'],
                'phone' => $location['l_Phone'],
                'address' => $location['l_Address'],
                'lineLink' => $location['l_LineLink'],
                'image' => $location['l_Image']
            ];

            if (isset($location['l_Image'])) {
                $data['imageUrl'] = base_url('upload/' . $this->fileDir . '/' . $location['l_Image']);
            }

            return $this->successResponse('Success', $data);
        } catch (\Exception $e) {
            return $this->errorResponse('取得據點詳細時發生錯誤', $e);
        }
    }

    // 修改
    public function edit($id = null)
    {
        try {
            $location = $this->locationModel->find($id);

            if (!$location) {
                return $this->errorResponse('找不到指定的據點');
            }

            $data = $this->getDataFromRequest();
            $image = $this->request->getFile('image');
            $oldImg = $location['l_Image'];

            if ($image && $image->isValid()) {
                $newName = $image->getRandomName();
                $data['l_Image'] = $newName;
            }

            $this->locationModel->update($id, $data);

            if (isset($newName)) {
                $this->uploadSer->uploadFile($image, $this->fileDir, $newName);

                if ($oldImg) {
                    $this->uploadSer->deleteFile($oldImg, $this->fileDir);
                }
            }

            return $this->successResponse('修改成功');
        } catch (\Exception $e) {
            return $this->errorResponse('修改據點時發生錯誤', $e);
        }
    }

    // 刪除
    public function delete($id = null)
    {
        try {
            $location = $this->locationModel->find($id);

            if (!$location) {
                return $this->errorResponse('找不到指定據點');
            }

            $oldImg = $location['l_Image'];

            if (!$this->locationModel->delete($id)) {
                return $this->errorResponse('刪除失敗');
            }

            if ($oldImg) {
                $this->uploadSer->deleteFile($oldImg, $this->fileDir);
            }

            return $this->successResponse('刪除成功');
        } catch (\Exception $e) {
            return $this->errorResponse('刪除據點時發生錯誤', $e);
        }
    }

    // 據點選單
    public function getOptions()
    {
        try {
            $locations = $this->locationModel->select(['l_Id', 'l_Name'])->findAll();
            $options = array_map(function ($location) {
                return [
                    'value' => $location['l_Id'],
                    'label' => $location['l_Name']
                ];
            }, $locations);

            return $this->successResponse('Success', ['options' => $options]);
        } catch (\Exception $e) {
            return $this->errorResponse('取得據點選單時發生錯誤', $e);
        }
    }

    private function getDataFromRequest(): array
    {
        return [
            'l_Name' => $this->request->getVar('name'),
            'l_Phone' => $this->request->getVar('phone'),
            'l_Address' => $this->request->getVar('address'),
            'l_LineLink' => $this->request->getVar('lineLink'),
        ];
    }
}
