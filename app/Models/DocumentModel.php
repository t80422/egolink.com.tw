<?php

namespace App\Models;

use App\Entities\Document;
use CodeIgniter\Model;

/**
 * 文件管理
 */
class DocumentModel extends Model
{
    protected $table = 'documents';
    protected $primaryKey = 'd_Id';
    protected $returnType = Document::class;
    protected $allowedFields = ['d_Name'];

    public function getDocCombinsBySGIds(array $sgIds): array
    {
        if (empty($sgIds)) {
            return [];
        }

        $datas = $this->db->table('document_combinations dc')
            ->select('dc.dc_sg_Id, dc.dc_Sequence, d.d_Id, d.d_Name')
            ->join('documents d', 'd.d_Id = dc.dc_d_Id')
            ->whereIn('dc.dc_sg_Id', $sgIds)
            ->orderBy('dc.dc_Sequence')
            ->get()
            ->getResultArray();

        $temp = [];

        foreach ($datas as $data) {
            $sgId = $data['dc_sg_Id'];
            $seq = $data['dc_Sequence'];

            if (!isset($temp[$sgId])) {
                $temp[$sgId] = [];
            }

            if (!isset($temp[$sgId][$seq])) {
                $temp[$sgId][$seq] = [
                    'ids' => [],
                    'names' => []
                ];
            }

            $temp[$sgId][$seq]['ids'][] = $data['d_Id'];
            $temp[$sgId][$seq]['names'][] = $data['d_Name'];
        }

        $result = [];

        foreach ($temp as $sgId => $sequences) {
            $result[$sgId] = [];

            foreach ($sequences as $seq => $docs) {
                $result[$sgId][] = [
                    'sequence' => $seq,
                    'documentIds' => $docs['ids'],
                    'documents' =>  $docs['names']
                ];
            }
        }

        return $result;
    }

    /**
     * 建立文件組合
     *
     * @param integer $sgId
     * @param array $combinations
     * @return void
     */
    public function createCombinations(int $sgId, array $combinations)
    {
        foreach ($combinations as $combination) {
            foreach ($combination['documentIds'] as $docId) {
                $this->db->table('document_combinations')
                    ->insert([
                        'dc_sg_Id' => $sgId,
                        'dc_Sequence' => $combination['sequence'],
                        'dc_d_Id' => $docId
                    ]);
            }
        }
    }

    /**
     * 刪除文件組合
     *
     * @param integer $sgId
     * @return void
     */
    public function deleteCombinations(int $sgId)
    {
        return $this->db->table('document_combinations')
            ->where('dc_sg_Id', $sgId)
            ->delete();
    }
}
