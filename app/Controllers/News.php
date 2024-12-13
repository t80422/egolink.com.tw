<?php namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\NewsModel;
use Config\App;

class News extends ResourceController
{
    protected $model;
    protected $config;
    protected $format    = 'json';
	protected $uploadPaths;
	
    // Prefered way
    public function __construct()
    {
        $this->model  = model('NewsModel');
        $this->config = new App();
		$this->uploadPaths = '/uploads/news/';
    }


    // Handles GET Request (news)
    public function index()
    {
        try {
            // 獲取分頁參數
            $page = (int)($this->request->getVar('page') ?? 1);
            $limit = 4;  // 每頁顯示數量
            $offset = ($page - 1) * $limit;
            
            // 獲取分頁數據
            $news_list = $this->model->getPaginatedNews($limit, $offset);
            $total = $this->model->getTotalNews();
            
            // 處理圖片路徑 - 統一在設定檔中管理
            $baseURL = rtrim($this->config->baseURL, '/');

            foreach ($news_list as &$item) {
                if (!empty($item['n_img'])) {
                    $item['n_img'] = $baseURL . $this->uploadPaths . basename($item['n_img']);
                }
            }
            
            return $this->respond([
                'success' => true,
                'message' => empty($news_list) ? '目前沒有最新公告資料' : '資料讀取成功',
                'data' => [
                    'news_list' => $news_list,
                    'pager' => [
                        'currentPage' => $page,
                        'perPage' => $limit,
                        'total' => $total,
                        'pageCount' => ceil($total / $limit),
                        'hasMore' => ($offset + $limit) < $total
                    ]
                ]
            ]);
    
        } catch (\Exception $e) {
            log_message('error', 'List news error: ' . $e->getMessage());
            return $this->fail('資料讀取失敗', ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    // Handles GET Request (news/new)
    public function new()
    {
        try {
            // 從設定檔取得路徑
            $baseURL = rtrim($this->config->baseURL, '/');
            
            // 取得新聞列表
            $news = $this->model
                ->orderBy('n_date', 'desc')
                ->findAll();
    
            // 處理圖片路徑
            if ($news && is_array($news)) {
                foreach ($news as &$item) {
                    if (!empty($item['n_img'])) {
                        $item['n_img'] = $baseURL . $this->uploadPaths . basename($item['n_img']);
                    }
                }
            }
          
            return $this->respond([
                'success' => true,
                'message' => empty($news) ? '目前沒有最新公告資料' : '資料讀取成功',
                'data' => $news
            ]);
    
        } catch (\Exception $e) {
            log_message('error', 'News/new error: ' . $e->getMessage());
            return $this->respond([
                'success' => false,
                'message' => '資料讀取失敗'
            ], 500);
        }
    }
    // Handles GET Request (news/(:num) OR (:segment))
    public function show($id = null)
    {
        $news = $this->model->find($id);
        
        if (!$news) {
            return $this->respond([
                'success' => false,
                'message' => '找不到此筆資料',
                'data' => null
            ], 404);
        }
    
        return $this->respond([
            'success' => true,
            'message' => '資料讀取成功',
            'data' => $news
        ]);
    }
    // Handles POST Request (news)
    public function create()
    {
        // 接收 getPost 格式的資料
        $input = $this->request->getPost();
        
        // 如果沒有 JSON 資料，嘗試獲取 POST 資料
        if (empty($input)) {
            $input = $this->request->getPost();
        }
        
        // 準備儲存資料
        $saveData = [
            'n_title' => $input['n_title'] ?? null,
            'n_content' => $input['n_content'] ?? null,
            'n_date' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s')
        ];

        try {
            // 處理圖片上傳
            $img = $this->request->getFile('n_img');
            if ($img && $img->isValid() && !$img->hasMoved()) {
                $newName = $img->getRandomName();
                $img->move('./uploads/news', $newName);
                $saveData['n_img'] = '/uploads/news/' . $newName;
            }

            // 新增資料
            $this->model->insert($saveData);
            
            return $this->respond([
                'success' => true,
                'message' => '新增成功',
                'data' => $saveData
            ], 201);
            
        } catch (\Exception $e) {
            log_message('error', 'Create News error: ' . $e->getMessage());
            return $this->respond([
                'success' => false,
                'message' => '新增失敗：' . $e->getMessage()
            ], 500);
        }
    }
    // Handles GET Request (news/(:segment/edit))
    public function edit($id = null)
    {
        // 添加日誌輸出
        log_message('debug', 'Received edit request for ID: ' . $id);
        
        // 驗證 ID
        if (empty($id)) {
            return $this->respond([
                'success' => false,
                'message' => 'ID 不能為空'
            ], 400);
        }
    
        // 查詢數據
        $data = $this->model->find($id);
    
        // 記錄查詢結果
        log_message('debug', 'Query result: ' . json_encode($data));
    
        // 檢查是否找到數據
        if (!$data) {
            return $this->respond([
                'success' => false,
                'message' => '找不到 ID 為 ' . $id . ' 的資料'
            ], 404);
        }
    
        // 處理圖片路徑 - 統一在設定檔中管理
        $baseURL = rtrim($this->config->baseURL, '/');
        
        // 修正：只處理單筆資料的圖片路徑
        if (!empty($data['n_img'])) {
            $data['n_img'] = $baseURL . $this->uploadPaths . basename($data['n_img']);
        }
    
        // 返回成功響應
        return $this->respond([
            'success' => true,
            'data' => $data
        ], 200);
    }
    // Handles PUT Request (news/(:segment))
    public function update($id = null)
    {
        try {
            // 檢查記錄是否存在
            $news = $this->model->find($id);
            if (!$news) {
                return $this->respond([
                    'success' => false,
                    'message' => '找不到此筆資料'
                ], 404);
            }
    
            $baseURL = rtrim($this->config->baseURL, '/');
            
            // 獲取輸入數據 - 支援多種請求方式
            $input = [];
            if ($this->request->getMethod() === 'put') {
                parse_str(file_get_contents("php://input"), $input);
            } else {
                $input = $this->request->getPost();
            }
    
            // 記錄接收到的數據
            log_message('debug', 'Received input data: ' . json_encode($input));
    
            // 準備更新資料
            $updateData = [
                'n_title' => $input['n_title'] ?? $news['n_title'],
                'n_content' => $input['n_content'] ?? $news['n_content'],
                'updated_at' => date('Y-m-d H:i:s')
            ];
    
            // 處理圖片上傳
            $files = $this->request->getFiles();
            if (!empty($files['n_img'])) {
                $img = $files['n_img'];
                if ($img->isValid() && !$img->hasMoved()) {
                    // 刪除舊圖片
                    if (!empty($news['n_img'])) {
                        $oldImgPath = FCPATH . 'uploads/news/' . basename($news['n_img']);
                        if (file_exists($oldImgPath)) {
                            unlink($oldImgPath);
                        }
                    }
    
                    // 上傳新圖片
                    $newName = $img->getRandomName();
                    $img->move(FCPATH . 'uploads/news', $newName);
                    $updateData['n_img'] = $newName;
                }
            }
    
            // 更新資料
            $success = $this->model->update($id, $updateData);
            
            if ($success) {
                // 取得更新後的完整資料
                $updatedNews = $this->model->find($id);
                
                // 處理圖片路徑
                if (!empty($updatedNews['n_img'])) {
                    $updatedNews['n_img'] = $baseURL . $this->uploadPaths . $updatedNews['n_img'];
                }
    
                return $this->respond([
                    'success' => true,
                    'message' => '更新成功',
                    'data' => $updatedNews
                ]);
            }
    
            throw new \Exception('更新失敗');
    
        } catch (\Exception $e) {
            log_message('error', 'Update news error: ' . $e->getMessage());
            return $this->respond([
                'success' => false,
                'message' => '更新失敗：' . $e->getMessage()
            ], 500);
        }
    }

    // Handles DELETE Request (news/(:segment))
    public function delete($id = null)
    {
        // 檢查記錄是否存在
        $news = $this->model->find($id);
        if (!$news) {
            return $this->respond([
                'success' => false,
                'message' => '找不到此筆資料'
            ], 404);
        }
    
        try {
            // 執行刪除
            $this->model->delete($id);
    
            return $this->respond([
                'success' => true,
                'message' => '刪除成功',
                'id' => $id
            ], 200);
    
        } catch (\Exception $e) {
            return $this->respond([
                'success' => false,
                'message' => '刪除失敗'
            ], 500);
        }
    }

    public function search()
    {
    try {
        // 獲取搜尋關鍵字和分頁參數
        $keyword = $this->request->getVar('keyword');
        $page = (int)($this->request->getVar('page') ?? 1);
        $limit = 4;  // 每頁顯示數量
        $offset = ($page - 1) * $limit;
        
        // 如果沒有關鍵字，返回錯誤
        if (empty($keyword)) {
            return $this->respond([
                'success' => false,
                'message' => '請輸入搜尋關鍵字'
            ], 400);
        }
        
        // 獲取搜尋結果
        $news_list = $this->model->searchNews($keyword, $limit, $offset);
        $total = $this->model->getTotalSearchNews($keyword);
        
        // 處理圖片路徑
        foreach ($news_list as &$item) {
            if (!empty($item['n_img'])) {
                // 確保路徑的一致性
                $item['n_img'] = $baseURL . $this->uploadPaths . basename($item['n_img']);
            }
        }
        
        return $this->respond([
            'success' => true,
            'message' => empty($news_list) ? '沒有符合的搜尋結果' : '搜尋成功',
            'data' => [
                'news_list' => $news_list,
                'pager' => [
                    'currentPage' => $page,
                    'perPage' => $limit,
                    'total' => $total,
                    'pageCount' => ceil($total / $limit),
                    'hasMore' => ($offset + $limit) < $total
                ]
            ]
        ]);

    } catch (\Exception $e) {
        log_message('error', 'Search news error: ' . $e->getMessage());
        return $this->fail('搜尋失敗', ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    }
}
}
