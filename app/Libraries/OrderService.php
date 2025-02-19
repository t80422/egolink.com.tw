<?php

namespace App\Libraries;

use App\Entities\Order;
use App\Entities\StockholderGift;
use App\Models\OrderModel;
use App\Models\DocumentModel;
use CodeIgniter\HTTP\Files\UploadedFile;
use Exception;

class OrderService
{
    private $orderModel;
    private $uploadSer;
    private $docSer;
    private $docModel;

    private const UPLOAD_DIR = 'votes';

    public function __construct()
    {
        $this->orderModel = new OrderModel();
        $this->uploadSer = new UploadService();
        $this->docSer = new DocumentService();
        $this->docModel = new DocumentModel();
    }

    public function handleVoteImgUpload(int $orderId, UploadedFile $img)
    {
        $this->orderModel->db->transStart();

        try {
            // 確認委託
            /**
             * @var Order|null $order
             */
            $order = $this->orderModel->find($orderId);

            if (!$order) {
                throw new Exception('找不到指定委託');
            }

            if ($order->status !== '0') {
                throw new Exception('委託狀態不正確');
            }

            // 圖片上傳
            $newFileName = $img->getRandomName();
            $this->uploadSer->uploadFile($img, self::UPLOAD_DIR, $newFileName);

            // 更新資料
            $order->setVoteImg($newFileName);

            // 檢查文件組合是否完整
            if ($this->docSer->checkDocCompletion($order->saId, $order->sgId)) {
                $order->markAsPending();
            }

            if (!$this->orderModel->update($orderId, $order)) {
                throw new Exception('更新失敗');
            }

            $this->orderModel->db->transComplete();
        } catch (Exception $e) {
            $this->orderModel->db->transRollback();

            if (isset($newFileName)) {
                $this->uploadSer->deleteFile($newFileName, self::UPLOAD_DIR);
            }

            throw $e;
        }
    }

    public function getConditions():array{
        $options=[];

        // 取得股東會相關選項
        $codeTables = ['meetingType', 'marketType'];

        foreach($codeTables as $type){
            $options['moreCondition'][$type] = $this->formatCodeTableOptions(StockholderGift::CODE_TABLES[$type]);
        }

        // 取得訂單狀態選項
        $options['orderStatus'] = $this->formatCodeTableOptions(Order::STATUS_NAMES);

        // 取得繳交項目
        $options['documents']= $this->docSer->getOptions();

        return $options;
    }

    private function formatCodeTableOptions(array $codeTables):array{
        return array_map(function($code, $name){
            return [
                'value' => $code,
                'label' => $name
            ];
        }, array_keys($codeTables), array_values($codeTables));
    }
}
