<?php
declare (strict_types = 1);
namespace App\Controller;

use App\Core\Controller;
use App\Core\App;
use App\Core\Auth;
use App\Core\Localizer as L;
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
    private Validator $validator;

    public function __construct(array $data = [])
    {
        $this->attachment = new Attachment();

        // Setup defaults
        $data = App::setupDefaults($data, [
           'attachment_id' => 0,
           'account_id' => 0,
           'category' => '',
           'reference_id' => 0,
           'mime_type' => '',
           'path' => '',
           'size' => 0,
           'original_name' => '',
           'updated_at' => date('Y-m-d H:i:s'),
           'updated_by' => Auth::getUser(Auth::$AuthUserId)
        ]);

        $this->data = $data;

        $this->validator = new Validator($this->data);
        $this->validator->add('attachment_id', 'Invalid attachment id', ValidationRule::number(1));
        $this->validator->add('account_id', 'Account not recognized', ValidationRule::number(1));
        $this->validator->add('category', 'Category not recognized', ValidationRule::inList(['Photo', 'Document']));
        $this->validator->add('reference_id', 'Invalid reference', ValidationRule::number(1));
        $this->validator->add('path', 'Invalid path', ValidationRule::string());
        $this->validator->add('size', 'Invalid size', ValidationRule::number(1));
    }

    public function Create(array $params = []): Result
    {
        if($dataErr = $this->validator->validate(['attachment_id'])){
            return new Result(
                $dataErr,
                L::loc('Some data are missing or invalid'),
                'validation_error'
            );
        }

        $resCount = $this->attachment->count([
            'account_id' => $this->data['account_id'],
            'category' => $this->data['category']
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
                L::loc('Exceeded max attachments count {max_attachments}', '', ['max_attachments' => $maxAttachmentsCount]),
                'error'
            );
        }

        $resCreate = $this->attachment->Create([
            'account_id' => $this->data['account_id'],
            'category' => $this->data['category'],
            'reference_id' => $this->data['reference_id'],
            'mime_type' => $this->data['mime_type'],
            'path' => $this->data['path'],
            'size' => $this->data['size'],
            'original_name' => $this->data['original_name'],
            'updated_at' => $this->data['updated_at'],
            'updated_by' => $this->data['updated_by'],
        ]);

        // DB Error
        if(is_null($resCreate->data)){
            return $resCreate;
        }

        $attachmentID = intval($resCreate->data);

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
            L::loc('{object} created', '', ['object' => 'Attachment']),
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

        // Get search params from request query string
        $search = json_decode(Request::query('search', '[]'), true);

        if(($page = Request::query('page', 0)) > 0){
            $search['page'] = $page;
        };

        if(array_key_exists('category', $routeParams) && !empty($routeParams['category'])){
            $search = ['category' => $routeParams['category']];
        };

        if(array_key_exists('reference_id', $routeParams) && $routeParams['reference_id'] > 0){
            $search = ['reference_id' => $routeParams['reference_id']];
        };

        if(array_key_exists('limit', $routeParams) && $routeParams['limit'] > 0){
            $search = ['limit' => $routeParams['limit']];
        };

        if(array_key_exists('attachment_id', $routeParams) && $routeParams['attachment_id'] > 0){
            $search = ['attachment_id' => $routeParams['attachment_id']];
            $isSingleRecord = true;
        };
        
        if(array_key_exists('account_id', $routeParams) && $routeParams['account_id'] > 0){
            $search['account_id'] = $routeParams['account_id'];
        };
        
        $resRead = $this->attachment->Read($search);

        // DB Error
        if(is_null($resRead->data)){
            return $resRead;
        }

        if($isSingleRecord && empty($resRead->data)){
            $message = L::loc('{object} not found', '', ['object' => 'Attachment']);
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
        $search = [];
        if(array_key_exists('category', $routeParams) && !empty($routeParams['category'])){
            $search = ['category' => $routeParams['category']];
        };

        if(array_key_exists('reference_id', $routeParams) && $routeParams['reference_id'] > 0){
            $search = ['reference_id' => $routeParams['reference_id']];
        };
        
        $resList = $this->attachment->List($search);

        // DB Error
        if(is_null($resList->data)){
            return $resList;
        }

        return new Result(
            $resList->data
        );
    }

    public function DeleteByCategory(array $routeParams = []): Result
    {
        $resDeleted = $this->attachment->DeleteByCategory([
            'account_id' => $this->data['account_id'],
            'category' => $this->data['category']
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
            L::loc('{actual} of {total} files have been deleted', '', ['actual' => $actualDeleted, 'total' => count($deleted)])
        );
    }

    public function DeleteByReference(array $routeParams = []): Result
    {
        $resDeleted = $this->attachment->DeleteByReference([
            'account_id' => $this->data['account_id'],
            'category' => $this->data['category'],
            'reference_id' => $this->data['reference_id']
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
            L::loc('{actual} of {total} files have been deleted', '', ['actual' => $actualDeleted, 'total' => count($deleted)])
        );
    }

    public function Delete(array $routeParams = []): Result
    {   
        $resDeleted = $this->attachment->Delete(['attachment_id' => $this->data['attachment_id']]);
        $deleted = $resDeleted->data;

        if(!empty($deleted)){
            $this->DeleteAttachmentFile($deleted[0]['path']);

            return new Result(
                $this->data['attachment_id'],
                L::loc('{object} deleted', '', ['object' => 'Attachment']),
                'success',
                ''
            );
        }

        return new Result(
            null,
            L::loc('Failed to delete {object}', '',['object' => 'Attachment']),
            'error',
            ''
        );
    }

    public function Count(array $routeParams = []): Result
    {
        $resCount = $this->attachment->Count([
            'account_id' => $this->data['account_id'],
            'category' => $this->data['category'],
            'reference_id' => $this->data['reference_id']
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
            L::loc('Failed to count {object}', '', ['object' => 'Attachment']),
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
