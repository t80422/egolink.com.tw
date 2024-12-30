<?php

namespace App\Controllers\Api;

use App\Models\NewsModel;
use Exception;

class NewsController extends BaseApiController
{
    private $newsModel;

    public function __construct()
    {
        $this->newsModel = new NewsModel;
    }

    // 新增
    public function create()
    {
        try {
            $data = $this->getRequest();

            $this->newsModel->insert($data);

            return $this->successResponse();
        } catch (Exception $e) {
            return $this->errorResponse('新增時發生錯誤', $e);
        }
    }

    // 列表
    public function index()
    {
        try {
            $datas = $this->newsModel->getList();
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
            $data = $this->newsModel->find($id);

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
            $news = $this->newsModel->find($id);

            if (!$news) {
                return $this->errorResponse('找不到指定對象');
            }

            $data = $this->getRequest();
            $this->newsModel->update($id, $data);

            return $this->successResponse();
        } catch (Exception $e) {
            return $this->errorResponse('修改時發生錯誤', $e);
        }
    }

    // 刪除
    public function delete($id = null)
    {
        try {
            $news = $this->newsModel->find($id);

            if (!$news) {
                return $this->errorResponse('找不到指定對象');
            }

            $this->newsModel->delete($id);

            return $this->successResponse();
        } catch (Exception $e) {
            return $this->errorResponse('刪除時發生錯誤', $e);
        }
    }

    private function formatData($data): array
    {
        return [
            'id' => $data['n_Id'],
            'title' => $data['n_Title'],
            'content' => $data['n_Content'],
            'date' => date('Y-m-d', strtotime($data['n_Date']))
        ];
    }

    private function getRequest(): array
    {
        try {
            return [
                'n_Title' => $this->request->getVar('title'),
                'n_Content' => $this->request->getVar('content')
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }
}
