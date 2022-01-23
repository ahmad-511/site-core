<?php
declare (strict_types = 1);

namespace App\Service;

use App\Core\App;
use App\Core\Service;
use App\Controller\AttachmentController;
use App\Core\Request;
use App\Core\ImageResizer;
use App\Core\Result;
use App\Service\FileService;

class AttachmentService extends Service
{
    public static function AttachmentCount(int $accountID, int $referenceID, string $type = ''): int{
        $attachmentController = new AttachmentController([
            'account_id' => $accountID,
            'reference_id' => $referenceID,
            'type' => $type
        ]);

        $resCount = $attachmentController->Count();

        return $resCount->data;
    }

    public static function PhotoByReference(string $type, int $referenceID): Result
    {
        $attachmentController = new AttachmentController();

        if($referenceID == 0){
            return new Result(
                []
            );
        }

        return $attachmentController->List([
            'type' => $type,
            'reference_id' => $referenceID
        ]);
    }

    public static function UploadPersonalPhoto(int $accountID): string{
        // Check if personal photo included in the request
        if(!Request::isFileSubmitted('personal_photo')){
            return '';
        }

        // Store uploaded personal photo
        $file = Request::uploadFile(
            'personal_photo',
            PERSONAL_PHOTO_DIR . date('Y-m') .'/',
            FileService::GenerateFileName('acc_', $accountID),
            '',
            ['image/jpeg', 'image/png'],
            true
        );
        
        $uploadError = $file->error;

        // Resize uploaded image and then create attachment record
        if(!$uploadError){
            $resImg = ImageResizer::resize($file->path, 150, 150 , 90);

            $attachmentController = new AttachmentController([
                'account_id' => $accountID,
                'type' => 'PersonalPhoto',
                'reference_id' => $accountID,
                'mime_type' => $file->type,
                'path' => str_replace(UPLOAD_DIR, '', $file->path),
                'size' => $resImg->size, // for resized image
                'description' => 'Personal Photo',
                'create_date' => date('Y-m-d H:i:s')
            ]);

            $resCreate = $attachmentController->Create();

            // DB Error
            if(is_null($resCreate->data)){
                $uploadError = $resCreate->message;
            }
        }

        // Provide a better info about uploaded photo
        if($uploadError == 'File type not accepted'){
            $uploadError = 'Personal photo type not accepted';
        }

        return $uploadError;
    }

    public static function UploadCarPhotos(int $accountID, int $carID): string{
        // Check if car photo included in the request
        if(!Request::isFileSubmitted('car_photo')){
            return '';
        }

        // Checking max allowed attachments per car
        $currentPhotosCount = self::AttachmentCount($accountID, $carID, 'CarPhoto');
        $currentPostedPhotos = count(Request::files('car_photo'));

        if($currentPhotosCount + $currentPostedPhotos > MAX_CAR_PHOTOS){
            return App::loc('Exceeded maximum allowed photos {max_car_photos}', '', ['max_car_photos' => MAX_CAR_PHOTOS]);
        }

        // Store uploaded car photo
        $files = Request::uploadFiles(
            'car_photo',
            CAR_PHOTO_DIR . date('Y-m') .'/',
            FileService::GenerateFileName('car_', $carID),
            '',
            ['image/jpeg', 'image/png'],
            true
        );
        
        $uploadErrors = [];

        foreach($files as $file){
            // Resize uploaded image and then create attachment record
            if($file->error){
                $uploadErrors[] = $file->error;
            }else{
                $resImg = ImageResizer::resize($file->path, 800, 800 , 90);
    
                $attachmentController = new AttachmentController([
                    'account_id' => $accountID,
                    'type' => 'CarPhoto',
                    'reference_id' => $carID,
                    'mime_type' => $file->type,
                    'path' => str_replace(UPLOAD_DIR, '', $file->path),
                    'size' => $resImg->size, // for resized image
                    'description' => 'Car Photo',
                    'create_date' => date('Y-m-d H:i:s')
                ]);
    
                $resCreate = $attachmentController->Create();
    
                // DB Error
                if(is_null($resCreate->data)){
                    $uploadErrors[] = $resCreate->message;
                }
            }
        }

        return implode("\n", $uploadErrors);
    }

    public static function DeleteAttachment(int $attachmentID): Result{
        $attachmentController = new AttachmentController([
            'attachment_id' => $attachmentID
        ]);

        $resDelete = $attachmentController->Delete();
        // Just a better message
        if(!is_null($resDelete->data)){
            $resDelete->message = App::loc('{object} deleted', '', ['object' => 'Photo']);
        }

        return $resDelete;
    }

    public static function DeleteByAccount(int $accountID, string $type){
        $attachmentController = new AttachmentController([
            'account_id' => $accountID,
            'type' => $type
        ]);

        $attachmentController->DeleteByAccount();
    }

    public static function DeleteByReference(int $accountID, int $referenceID, string $type){
        $attachmentController = new AttachmentController([
            'account_id' => $accountID,
            'reference_id' => $referenceID,
            'type' => $type
        ]);

        $attachmentController->DeleteByReference();
    }

    public static function Photo(int $photoID = 0, $default = null){
        $data = null;
        
        if($photoID > 0){
            $attachmentController = new AttachmentController();
            $resRead = $attachmentController->Read([
                'attachment_id' => $photoID
            ]);
    
            $data = $resRead->data;
        }

        $photoPath = '';
        $photoMimeType = '';
        $photoSize =  0;

        // DB Error
        if(!is_null($data) && !empty($data)){
            $data = $data[0];

            $photoMimeType = $data['mime_type'];
            $photoPath = UPLOAD_DIR . $data['path'];
        }

        if(empty($photoPath)){
            $photoPath = $default?? BASE_DIR . '/img/user.png';
            $photoMimeType ='image/png';

        }elseif(!file_exists($photoPath)){
            $photoPath = BASE_DIR . '/img/broken.png';
            $photoMimeType ='image/png';
        }

        header('Content-Type: ' . $photoMimeType);
		header('Content-Disposition: inline');
		header('Content-Length: '. $photoSize);
		
		// Write directly to the ouput buffer		   
		readfile($photoPath);
    }
}