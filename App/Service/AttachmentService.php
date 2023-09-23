<?php
declare (strict_types = 1);

namespace App\Service;

use App\Core\Localizer as L;
use App\Core\Service;
use App\Controller\AttachmentController;
use App\Core\Request;
use App\Core\ImageResizer;
use App\Core\Result;
use App\Service\FileService;

class AttachmentService extends Service
{
    public static function AttachmentCount(int $accountId, int $referenceID, string $type = ''): int{
        $attachmentController = new AttachmentController([
            'account_id' => $accountId,
            'reference_id' => $referenceID,
            'type' => $type
        ]);

        $resCount = $attachmentController->Count();

        return $resCount->data;
    }

    public static function GetAttachment(int $attachmentID, int $accountId): array{
        $attachmentController = new AttachmentController();

        $resRead = $attachmentController->Read([
            'attachment_id' => $attachmentID,
            'account_id' => $accountId
        ]);

        if(!empty($resRead->data)){
            $resRead->data = $resRead->data[0];

        }

        return $resRead->data;
    }

    public static function AttachmentByReference(string $category, int $referenceId): Result
    {
        $attachmentController = new AttachmentController();

        if($referenceId == 0){
            return new Result(
                []
            );
        }

        return $attachmentController->List([
            'category' => $category,
            'reference_id' => $referenceId
        ]);
    }

    public static function UploadPersonalPhoto(int $accountId, string $accountName = ''): ?string{
        // Check if seafarer photo included in the request
        $status = Request::isFileSubmitted('photo');
        if($status->success == 0 && empty($status->errors)){
            return null;
        }

        if($status->success == 0 && !empty($status->errors)){
            return implode("\n", $status->errors);
        }

        // Delete old photo
        self::DeleteByCategory($accountId, 'Photo');

        // Store uploaded personal photo
        $file = Request::uploadFile(
            'photo',
            UPLOAD_DIR . '/personal_photo/' . date('Y-m') .'/',
            uniqid('a_', true),
            '',
            ['image/jpeg', 'image/png'],
            true
        );
        
        $uploadError = $file->error;

        // Resize uploaded image and then create attachment record
        if(!$uploadError){
            $resImg = ImageResizer::resize($file->path, 250, 250 , 90);

            if(empty($accountName)){
                $accountName = 'Personal Photo';
            }

            $ext = pathinfo($file->path, PATHINFO_EXTENSION);
            $originalName = $accountName . '.' . $ext;

            $attachmentController = new AttachmentController([
                'seafarer_id' => $accountId,
                'category' => 'Photo',
                'reference_id' => $accountId,
                'mime_type' => $file->mimeType,
                'path' => str_replace(UPLOAD_DIR, '', $file->path),
                'size' => $resImg->size, // for resized image
                'original_name' => $originalName,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            $resCreate = $attachmentController->Create();

            // DB Error
            if($resCreate->messageType != 'success'){
                $uploadError = $resCreate->message;
            }
        }

        // Provide a better info about uploaded photo
        if($uploadError == 'File type not accepted'){
            $uploadError = 'Seafarer photo type not accepted';
        }

        return $uploadError;
    }

    public static function DeleteAttachment(int $attachmentId): Result{
        $attachmentController = new AttachmentController([
            'attachment_id' => $attachmentId
        ]);

        $resDelete = $attachmentController->Delete();
        // Just a better message
        if(!is_null($resDelete->data)){
            $resDelete->message = L::loc('{object} deleted', '', ['object' => 'File']);
        }

        return $resDelete;
    }

    public static function DeleteByAccount(int $accountId, string $type){
        $attachmentController = new AttachmentController([
            'account_id' => $accountId,
            'type' => $type
        ]);

        $attachmentController->DeleteByAccount();
    }

    public static function DeleteByCategory(int $accountId, string $category=''){
        $attachmentController = new AttachmentController([
            'seafarer_id' => $accountId,
            'category' => $category
        ]);

        $attachmentController->DeleteByCategory();
    }

    public static function DeleteByReference(int $accountId, string $category, int $referenceId){
        $attachmentController = new AttachmentController([
            'seafarer_id' => $accountId,
            'category' => $category,
            'reference_id' => $referenceId
        ]);

        $attachmentController->DeleteByReference();
    }

    public static function LoadFile(array $params = []){
        $fileId = intval($params['attachment_id']??0);

        return self::file($fileId);
    }

    public static function File(int $attachmentId = 0, $default = null){
        $data = null;
        
        if($attachmentId > 0){
            $attachmentController = new AttachmentController();
            $resRead = $attachmentController->Read([
                'attachment_id' => $attachmentId
            ]);
    
            $data = $resRead->data;
        }

        $path = '';
        $mimeType = '';
        $size =  0;

        // DB Error
        if(!is_null($data) && !empty($data)){
            $data = $data[0];

            $mimeType = $data['mime_type'];
            $path = UPLOAD_DIR . $data['path'];
        }

        if(empty($path)){
            $path = $default?? BASE_DIR . '/../img/user.png';
            $mimeType ='image/png';

        }elseif(!file_exists($path)){
            $path = BASE_DIR . '/../img/broken.png';
            $mimeType ='image/png';
        }

        header('Content-Type: ' . $mimeType);
		header('Content-Disposition: inline');
		header('Content-Length: '. $size);
		
		// Write directly to the output buffer		   
		readfile($path);
    }
}