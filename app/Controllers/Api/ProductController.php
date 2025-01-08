<?php

namespace App\Controllers\Api;

use App\Libraries\UploadService;
use App\Models\ProductModel;

class ProductController extends BaseApiController{
    protected $productModel;
    protected $uploadSer;

    public function __construct()
    {
        $this->productModel=new ProductModel();
        $this->uploadSer=new UploadService();
    }

}