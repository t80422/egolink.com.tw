<?php

declare(strict_types=1);

namespace App\Libraries;

use Exception;

class UploadService
{
    public function uploadFile($file, string $directory, string $newName)
    {
        try {
            $path = FCPATH . 'upload/' . $directory;

            // 確保目錄存在且可寫入
            if (!is_dir($path)) {
                if (!mkdir($path, 0777, true)) {
                    throw new Exception('無法創建目錄');
                }
            }

            // 移動檔案到目標目錄
            $file->move($path, $newName);
        } catch (\Exception $e) {
            throw new Exception('上傳檔案失敗:' . $e->getMessage());
        }
    }

    public function deleteFile(string $fileName, string $directory)
    {
        $fullPath = FCPATH . 'upload\\' . $directory . '\\' . $fileName;

        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }
}
