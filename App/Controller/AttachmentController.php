<?php
declare (strict_types = 1);
namespace App\Controller;

use App\Core\Controller;
use App\Core\App;
use App\Model\Attachment;
use App\Core\Validator;
use App\Core\ValidationRule;
use App\Core\Result;
use App\Core\File;
use App\Core\Request;


// This controller has no external api and should be called directly from the code
class AttachmentController extends Controller
{
    private Attachment $attachment;
    private array $data = [];
    private array $search;
    private Validator $validator;

    public function __construct(array $data = [])
    {
        $this->attachment = new Attachment();

        // Setup defaults
        $data = App::setupDefaults($data, [
           'attachment_id' => 0,
           'account_id' => 0,
           'type' => '',
           'reference_id' => 0,
           'mime_type' => '',
           'path' => '',
           'size' => 0,
           'description' => '',
           'create_date' => date('Y-m-d H:i:s')
        ]);
        // Get search params from both request body and query string
        $this->search = array_merge(json_decode($data['search'] ?? '[]', true), json_decode(Request::getQueryParams()['search'] ?? '[]', true));

        $this->data = $data;

        $this->validator = new Validator($this->data);
        $this->validator->add('attachment_id', 'Invalid attachment id', ValidationRule::number(1));
        $this->validator->add('account_id', 'Account not recognized', ValidationRule::number(1));
        $this->validator->add('type', 'Type not recognized', ValidationRule::inList(['PersonalPhoto', 'CarPhoto']));
        $this->validator->add('reference_id', 'Invalid reference', ValidationRule::number(1));
        $this->validator->add('path', 'Invalid path', ValidationRule::string());
        $this->validator->add('size', 'Invalid size', ValidationRule::number(1));
    }

    public function Create(array $params = []): Result
    {
        if($dataErr = $this->validator->validate(['attachment_id'])){
            return new Result(
                $dataErr,
                App::loc('Some data are missing or invalid'),
                'validation_error'
            );
        }

        $resCount = $this->attachment->count([
            'account_id' => $this->data['account_id'],
            'type' => $this->data['type']
        ]);

        // DB Error
        if(is_null($resCount->data)){
            return $resCount;
        }

        // Existing record found
        $maxAttachmentsCount = $params['max_attachments_count']??0;

        if($maxAttachmentsCount && $resCount->data >= $maxAttachmentsCount){
            return new Result(
                null,
                App::loc('Exceeded max attachments count {max_attachments}', '', ['max_attachments' => $maxAttachmentsCount]),
                'error'
            );
        }

        $resCreate = $this->attachment->Create([
            'account_id' => $this->data['account_id'],
            'type' => $this->data['type'],
            'reference_id' => $this->data['reference_id'],
            'mime_type' => $this->data['mime_type'],
            'path' => $this->data['path'],
            'size' => $this->data['size'],
            'description' => $this->data['description'],
            'create_date' => $this->data['create_date']
        ]);

        // DB Error
        if(is_null($resCreate->data)){
            return $resCreate;
        }

        $attachmentID = $resCreate->data;

        // Return created record
        $resRead = $this->attachment->Read([
            'attachment_id' => $attachmentID
        ]);

        // DB Error
        if(is_null($resRead->data)){
            return $resRead;
        }

        return new Result(
            $resRead->data,
            App::loc('{object} created', '', ['object' => 'Attachment']),
            'success',
            '',
            $resRead->metaData
        );
    }

    public function Read(array $routeParams = []): Result
    {
        $isSingleRecord = false;
        $message = '';
        $messageType = '';

        if(array_key_exists('page', $routeParams) && $routeParams['page'] > 0){
            $this->search['page'] = $routeParams['page'];
        };

        if(array_key_exists('type', $routeParams) && !empty($routeParams['type'])){
            $this->search = ['type' => $routeParams['type']];
        };

        if(array_key_exists('reference_id', $routeParams) && $routeParams['reference_id'] > 0){
            $this->search = ['reference_id' => $routeParams['reference_id']];
        };

        if(array_key_exists('limit', $routeParams) && $routeParams['limit'] > 0){
            $this->search = ['limit' => $routeParams['limit']];
        };

        if(array_key_exists('attachment_id', $routeParams) && $routeParams['attachment_id'] > 0){
            $this->search = ['attachment_id' => $routeParams['attachment_id']];
            $isSingleRecord = true;
        };
        
        if(array_key_exists('account_id', $routeParams) && $routeParams['account_id'] > 0){
            $this->search['account_id'] = $routeParams['account_id'];
        };
        
        $resRead = $this->attachment->Read($this->search);

        // DB Error
        if(is_null($resRead->data)){
            return $resRead;
        }

        if($isSingleRecord && empty($resRead->data)){
            $message = App::loc('{object} not found', '', ['object' => 'Attachment']);
            $messageType = 'info';
        }

        return new Result(
            $resRead->data,
            $message,
            $messageType,
            '',
            $resRead->metaData
        );
    }

    public function List(array $routeParams = []): Result
    {
        if(array_key_exists('type', $routeParams) && !empty($routeParams['type'])){
            $this->search = ['type' => $routeParams['type']];
        };

        if(array_key_exists('reference_id', $routeParams) && $routeParams['reference_id'] > 0){
            $this->search = ['reference_id' => $routeParams['reference_id']];
        };
        
        $resList = $this->attachment->List($this->search);

        // DB Error
        if(is_null($resList->data)){
            return $resList;
        }

        return new Result(
            $resList->data
        );
    }

    public function DeleteByAccount(array $routeParams = []): Result
    {
        $resDeleted = $this->attachment->DeleteByAccount([
            'account_id' => $this->data['account_id'],
            'type' => $this->data['type']
        ]);

        $deleted = $resDeleted->data;

        // Delete related files
        $actualDeleted = 0;

        foreach($deleted as $attach){
            if($this->DeleteAttachmentFile($attach['path'])){
                $actualDeleted++;
            }
        }
        
        return new Result(
            $deleted,
            APP::loc('{actual} of {total} files have been deleted', '', ['actual' => $actualDeleted, 'total' => count($deleted)])
        );
    }

    public function DeleteByReference(array $routeParams = []): Result
    {
        $resDeleted = $this->attachment->DeleteByReference([
            'account_id' => $this->data['account_id'],
            'reference_id' => $this->data['reference_id'],
            'type' => $this->data['type']
        ]);

        $deleted = $resDeleted->data;

        // Delete related files
        $actualDeleted = 0;

        foreach($deleted as $attach){
            if($this->DeleteAttachmentFile($attach['path'])){
                $actualDeleted++;
            }
        }
        
        return new Result(
            $deleted,
            APP::loc('{actual} of {total} files have been deleted', '', ['actual' => $actualDeleted, 'total' => count($deleted)])
        );
    }

    public function Delete(array $routeParams = []): Result
    {
        $resReferenced = $this->attachment->isReferenced([
            'attachment_id' => $this->data['attachment_id'],
            'account_id' => $this->data['account_id']
        ]);

        if(!empty($resReferenced->data)){
            $resReferenced->data = array_map(function($item){
                $item['model'] = App::loc($item['model']);
                return $item;
            }, $resReferenced->data);
            
            return new Result(
                $resReferenced->data,
                App::loc('{object} is referenced by', '', ['object' => 'Attachment']),
                'reference_error',
                ''
            );
        }
        
        $resDeleted = $this->attachment->Delete(['attachment_id' => $this->data['attachment_id']]);
        $deleted = $resDeleted->data;

        if(!empty($deleted)){
            $this->DeleteAttachmentFile($deleted[0]['path']);

            return new Result(
                $this->data['attachment_id'],
                App::loc('{object} deleted', '', ['object' => 'Attachment']),
                'success',
                ''
            );
        }

        return new Result(
            null,
            App::loc('Failed to delete {object}', '',['object' => 'Attachment']),
            'error',
            ''
        );
    }

    public function Count(array $routeParams = []): Result
    {
        $resCount = $this->attachment->Count([
            'account_id' => $this->data['account_id'],
            'reference_id' => $this->data['reference_id'],
            'type' => $this->data['type']
        ]);

        // DB Error
        if(is_null($resCount->data)){
            return $resCount;
        }
        
        return new Result(
            intval($resCount->data),
            '',
            'success',
            ''
        );

        return new Result(
            null,
            App::loc('Failed to count {object}', '', ['object' => 'attachments']),
            'error',
            ''
        );
    }

    private function DeleteAttachmentFile(string $path){
        $path = UPLOAD_DIR . $path;

        if(File::remove($path)){
            return true;
        }

        return false;
    }
}
