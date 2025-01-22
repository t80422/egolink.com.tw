<?php

namespace App\Libraries;

use App\Entities\Product;
use App\Entities\StockholderGift;
use App\Models\ProductModel;
use App\Models\StockholderGiftsModel;

class PurchaseService
{
    private $sgModel;
    private $productModel;

    public function __construct()
    {
        $this->sgModel = new StockholderGiftsModel();
        $this->productModel = new ProductModel();
    }

    /**
     * 取得股東會資訊選單
     *
     * @return array
     */
    public function getSGOptions(): array
    {
        // $datas = $this->sgModel->getByYear(date("Y"));
        $datas = $this->sgModel->getByYear(2024);
        
        return array_map(function (StockholderGift $data) {
            return $data->formatforOption();
        }, $datas);
    }

    public function getProducts(int $sgId): array
    {

        $datas = $this->productModel->getBySGId($sgId);

        return array_map(function (Product $data) {
            return $data->formatForOptions();
        }, $datas);
    }
}
