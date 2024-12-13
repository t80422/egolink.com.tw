<?php namespace App\Models;

use CodeIgniter\Model;

class NewsModel extends Model
{
        // Table name
        protected $table      = 'news';
        protected $primaryKey = 'n_id';

        protected $allowedFields = ['n_title', 'n_content', 'n_img'];
        //protected $returnType = 'App\Entities\News';
        protected $returnType = 'array';
        protected $useSoftDeletes = true;

        protected $useTimestamps = true;
        protected $createdField  = 'created_at';
        protected $updatedField  = 'updated_at';
        protected $deletedField  = 'deleted_at';

        protected $validationRules    = [];
        protected $validationMessages = [];
        protected $skipValidation     = false;

        public function getPaginatedNews($limit, $offset)
        {
        return $this->orderBy('n_date', 'DESC')
                        ->findAll($limit, $offset);
        }

        public function getTotalNews()
        {
        return $this->countAllResults();
        }

        public function searchNews($keyword, $limit, $offset)
        {
        return $this->like('n_title', $keyword)
                        ->orLike('n_content', $keyword)
                        ->orderBy('n_date', 'DESC')
                        ->findAll($limit, $offset);
        }

        public function getTotalSearchNews($keyword)
        {
        return $this->like('n_title', $keyword)
                        ->orLike('n_content', $keyword)
                        ->countAllResults();
        }
}