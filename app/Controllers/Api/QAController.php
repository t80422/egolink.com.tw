<?php

namespace App\Controllers\Api;

use App\Models\QAModel;
use Exception;

class QAController extends BaseApiController
{
    private $qaModel;

    public function __construct()
    {
        $this->qaModel = new QAModel();
    }

    // 新增
    public function create()
    {
        try {
            $data = $this->getRequest();
            $this->qaModel->insert($data);

            return $this->successResponse();
        } catch (Exception $e) {
            return $this->errorResponse('新增時發生錯誤', $e);
        }
    }

    // 列表
    public function index()
    {
        try {
            $datas = $this->qaModel->getList();
            $result = [];

            foreach ($datas as $data) {
                $result[] = $this->formatData($data);
            }

            return $this->successResponse('', $result);
        } catch (Exception $e) {
            return $this->errorResponse('取得列表時發生錯誤', $e);
        }
    }

    // 詳細
    public function detail($id = null)
    {
        try {
            $data = $this->qaModel->find($id);

            if (!$data) {
                return $this->errorResponse('找不到指定對象');
            }

            $result = $this->formatData($data);

            return $this->successResponse('', $result);
        } catch (Exception $e) {
            return $this->errorResponse('取得詳細時發生錯誤', $e);
        }
    }

    // 修改
    public function edit($id = null)
    {
        try {
            $qa = $this->qaModel->find($id);

            if (!$qa) {
                return $this->errorResponse('找不到指定對象');
            }

            $data = $this->getRequest();
            $this->qaModel->update($id, $data);

            return $this->successResponse();
        } catch (Exception $e) {
            return $this->errorResponse('修改時發生錯誤', $e);
        }
    }

    // 刪除
    public function delete($id = null)
    {
        try {
            $qa = $this->qaModel->find($id);

            if (!$qa) {
                return $this->errorResponse('找不到指定對象');
            }

            $this->qaModel->delete($id);

            return $this->successResponse();
        } catch (Exception $e) {
            return $this->errorResponse('刪除時發生錯誤', $e);
        }
    }

    /**
     * 取得請求資料
     *
     * @return array
     */
    private function getRequest(): array
    {
        $title = $this->request->getVar('title');
        $content = $this->request->getVar('content');

        return [
            'q_Title' => $title,
            'q_Content' => $content
        ];
    }

    /**
     * 格式化資料
     *
     * @param [type] $data
     * @return array
     */
    private function formatData($data): array
    {
        return [
            'id' => $data['q_Id'],
            'title' => $data['q_Title'],
            'content' => $data['q_Content'],
            'date' => date('Y-m-d', strtotime($data['q_Date'])),
        ];
    }
}
