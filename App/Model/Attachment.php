<?php
declare (strict_types = 1);

namespace App\Model;

use App\Core\App;
use App\Core\Model;
use App\Core\Result;
use Exception;

class Attachment extends Model {

    public function isReferenced(array $params = []):Result{
        // No refrences for accounts yet
        try{
            return new Result(
                []
            );

        }catch(Exception $ex){
            return new Result(
                null,
                $ex->getMessage(),
                'db_error'
            );
        }
    }

    public function Count(array $params = []):Result{
        $sql = "SELECT COUNT(attachment_id) AS 'count'
            FROM attachments";

        $filter = $this->buildSQLFilter(
            $params,
            array(
                'account_id' =>     ['AND', 'account_id = :sf_account_id'],
                'reference_id' =>   ['AND', 'reference_id = :sf_reference_id'],
                'type' =>           ['AND', 'type = :sf_type']
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
                App::loc('Failed to count {object}', '', ['object' => 'attachments']),
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
            (account_id, type, reference_id, mime_type, path, size, description, create_date)
            VALUES(:account_id, :type, :reference_id, :mime_type, :path, :size, :description, :create_date)";

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
        $sql = "SELECT a.attachment_id, a.account_id, CONCAT(acc.name, ' ', acc.surname) AS account, a.type, a.reference_id, a.mime_type, a.path, a.size, a.description, a.create_date
            FROM attachments AS a
            INNER JOIN accounts AS acc ON acc.account_id = a.account_id";

        $args = [
            'limit' => $limit= App::getPageLimit($params['limit']??0),
            'offset' => App::getPageOffset($params['page']??1, $limit)
        ];

        $filter = $this->buildSQLFilter(
            $params,
            array(
                'attachment_id' =>      ['AND', 'a.attachment_id = :sf_attachment_id'],
                'account_id' =>         ['AND', 'a.account_id = :sf_account_id'],
                'type' =>               ['AND', 'a.type = :sf_type'],
                'reference_id' =>       ['AND', 'a.reference_id = :sf_reference_id'],
                'mime_type'=>           ['AND', "a.mime_type LIKE CONCAT('%', :sf_mime_type, '%')"],
                'description'=>         ['AND', "a.description LIKE CONCAT('%', :sf_description, '%')"],
                'create_date_from'=>    ['AND', "a.create_date >= :sf_create_date_from"],
                'create_date_to'=>      ['AND', "a.create_date <= DATE_ADD(:sf_create_date_to, INTERVAL 1 DAY)"]
            ),
            'sf_'
        );

        $sql .= $filter->Query;

        $sql .= " ORDER BY a.create_date DESC
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
                App::loc('Failed to read {object}', '', ['object' => 'attachments']),
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
        $sql = "SELECT attachment_id, mime_type, size, description, create_date
            FROM attachments";

        $args = [];

        $filter = $this->buildSQLFilter(
            $params,
            array(
                'type' =>               ['AND', 'type = :sf_type'],
                'reference_id' =>       ['AND', 'reference_id = :sf_reference_id']
            ),
            'sf_'
        );

        $sql .= $filter->Query;

        $sql .= " ORDER BY create_date DESC;";

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
                App::loc('Failed to read {object}', '', ['object' => 'attachments']),
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
                type = :type,
                reference_id = :reference_id,
                mime_type = :mime_type,
                path = :path,
                size = :size,
                description = :description,
                create_date = :create_date
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
        $sql = "SELECT attachment_id, type, path FROM attachments WHERE attachment_id = :attachment_id;
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

    public function DeleteByAccount(array $params = []): Result
    {
        $sql = "SELECT attachment_id, type, path
            FROM attachments
            WHERE account_id = :account_id AND (type = :type OR :type = '');

            DELETE FROM attachments
            WHERE account_id = :account_id AND (type = :type OR :type = '');";

        $params['type'] = $params['type']??'';

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
        $sql = "SELECT attachment_id, type, path
            FROM attachments
            WHERE account_id = :account_id AND (type = :type OR :type = '') AND reference_id = :reference_id;

            DELETE FROM attachments
            WHERE account_id = :account_id AND (type = :type OR :type = '') AND reference_id = :reference_id;";

        $params['type'] = $params['type']??'';

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