<?php
declare (strict_types = 1);

namespace App\Model;

use App\Core\App;
use App\Core\Localizer as L;
use App\Core\Model;
use App\Core\Result;
use Exception;

class Attachment extends Model {

    public function Count(array $params = []):Result{
        $sql = "SELECT COUNT(attachment_id) AS 'count'
            FROM attachments";

        $filter = $this->buildSQLFilter(
            $params,
            array(
                'account_id' =>     ['AND', 'account_id = :sf_account_id'],
                'reference_id' =>   ['AND', 'reference_id = :sf_reference_id'],
                'category' =>       ['AND', 'category = :sf_category']
            ),
            'sf_'
        );

        $sql .= $filter->Query;

        $group = array_map(function($item){
            return str_replace('sf_', '', $item);
        }, array_keys($filter->Params));

        $group = implode(', ', $group);

        $sql .= " GROUP BY " . $group . ";";

        $rowsets = [];
        try {
            $rowsets = $this->query($sql, $filter->Params);
        }catch (Exception $ex){
            return new Result(
                null,
                $ex->getMessage(),
                'db_error'
            );
        }

        if ($rowsets === false) {
            return new Result(
                null,
                L::loc('Failed to count {object}', '', ['object' => 'attachments']),
                'error'
            );
        }

        return new Result(
            empty($rowsets)?0: $rowsets[0]['count']
        );
    }

    public function Create(array $params = []): Result
    {
        $sql = "INSERT INTO attachments
            (account_id, category, reference_id, mime_type, path, size, original_name, updated_at, updated_by)
            VALUES(:account_id, :category, :reference_id, :mime_type, :path, :size, :original_name, :updated_at, :updated_by)";

        try {
            $id = $this->query($sql, $params);
            return new Result(
                $id
            );

        }catch(Exception $ex){
            return new Result(
                null,
                $ex->getMessage(),
                'db_error'
            );
        }
    }

    public function Read(array $params = []): Result
    {
        $sql = "SELECT a.attachment_id, a.account_id, s.full_name, a.category, a.reference_id, a.mime_type, a.path, a.size, a.original_name, a.updated_at, IFNULL(acc.name, '') AS updated_by
            FROM attachments AS a
            INNER JOIN seafarers AS s ON s.account_id = a.account_id
            LEFT JOIN accounts AS acc ON acc.account_id = a.updated_by";

        $args = [
            'limit' => $limit= App::getPageLimit($params['limit']??0),
            'offset' => App::getPageOffset($params['page']??1, $limit)
        ];

        $filter = $this->buildSQLFilter(
            $params,
            array(
                'attachment_id' =>      ['AND', 'a.attachment_id = :sf_attachment_id'],
                'account_id' =>         ['AND', 'a.account_id = :sf_account_id'],
                'category' =>           ['AND', 'a.category = :sf_type'],
                'reference_id' =>       ['AND', 'a.reference_id = :sf_reference_id'],
                'mime_type'=>           ['AND', "a.mime_type LIKE CONCAT('%', :sf_mime_type, '%')"],
                'original_name'=>       ['AND', "a.original_name LIKE CONCAT('%', :sf_original_name, '%')"],
            ),
            'sf_'
        );

        $sql .= $filter->Query;

        $sql .= " ORDER BY a.updated_at DESC
            LIMIT :limit OFFSET :offset;";
        
        // Adding meta data when reading all records
        $hasMeta = false;
        if (($params['attachment_id']??0) == 0) {
            $hasMeta = true;

            $sql .= "SELECT COUNT(*) AS total_records, :limit AS records_per_page
                FROM attachments AS a";
            $sql .= $filter->Query.";";
        }

        $args = array_merge($args, $filter->Params);
        
        $rowsets = [];
        try {
            $rowsets = $this->query($sql, $args);
        }catch (Exception $ex){
            return new Result(
                null,
                $ex->getMessage(),
                'db_error'
            );
        }

        if ($rowsets === false) {
            return new Result(
                [],
                L::loc('Failed to read {object}', '', ['object' => 'attachments']),
                'error'
            );
        }

        return new Result(
            $hasMeta?$rowsets[0]:$rowsets,
            '',
            '',
            '',
            $hasMeta?$rowsets[1][0]:null
        );
    }

    public function List(array $params = []): Result
    {
        $sql = "SELECT attachment_id, mime_type, size, original_name, updated_at
            FROM attachments";

        $args = [];

        $filter = $this->buildSQLFilter(
            $params,
            array(
                'category' =>       ['AND', 'category = :sf_type'],
                'reference_id' =>   ['AND', 'reference_id = :sf_reference_id']
            ),
            'sf_'
        );

        $sql .= $filter->Query;

        $sql .= " ORDER BY updated_at DESC;";

        $args = array_merge($args, $filter->Params);
        
        $rowsets = [];
        try {
            $rowsets = $this->query($sql, $args);
        }catch (Exception $ex){
            return new Result(
                null,
                $ex->getMessage(),
                'db_error'
            );
        }

        if ($rowsets === false) {
            return new Result(
                [],
                L::loc('Failed to read {object}', '', ['object' => 'attachments']),
                'error'
            );
        }

        return new Result(
            $rowsets
        );
    }

    public function Update(array $params = []): Result
    {
        $sql = "UPDATE attachments
            SET
                category = :category,
                reference_id = :reference_id,
                mime_type = :mime_type,
                path = :path,
                size = :size,
                original_name = :original_name,
                updated_at = :updated_at,
                updated_by = :updated_by
            WHERE attachment_id = :attachment_id AND account_id = :account_id;";

        try {
            if($this->query($sql, $params)){
                return new Result(
                    $params['attachment_id']
                );
            }

        }catch(Exception $ex){
            return new Result(
                null,
                $ex->getMessage(),
                'db_error'
            );
        }
    }

    public function Delete(array $params = []): Result
    {
        $sql = "SELECT attachment_id, category, path FROM attachments WHERE attachment_id = :attachment_id;
            DELETE FROM attachments WHERE attachment_id = :attachment_id;";

        try {
            $rowsets = $this->query($sql, $params);

            return new Result(
                $rowsets
            );

        }catch(Exception $ex){
            return new Result(
                null,
                $ex->getMessage(),
                'db_error'
            );
        }
    }

    public function DeleteByCategory(array $params = []): Result
    {
        $sql = "SELECT attachment_id, category, path
            FROM attachments
            WHERE account_id = :account_id AND (category = :category OR :category = '');
            
            DELETE FROM attachments
            WHERE account_id = :account_id AND (category = :category OR :category = '');";
        
        try {
            $rowsets = $this->query($sql, $params);
            
            return new Result(
                $rowsets
            );

        }catch(Exception $ex){
            return new Result(
                null,
                $ex->getMessage(),
                'db_error'
            );
        }
    }

    public function DeleteByReference(array $params = []): Result
    {
        $sql = "SELECT attachment_id, category, path
            FROM attachments
            WHERE account_id = :account_id AND (category = :category OR :category = '') AND reference_id = :reference_id;

            DELETE FROM attachments
            WHERE account_id = :account_id AND (category = :category OR :category = '') AND reference_id = :reference_id;";

        $params['category'] = $params['category']??'';

        try {
            $rowsets = $this->query($sql, $params);
            
            return new Result(
                $rowsets
            );

        }catch(Exception $ex){
            return new Result(
                null,
                $ex->getMessage(),
                'db_error'
            );
        }
    }
}