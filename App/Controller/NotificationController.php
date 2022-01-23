<?php
declare (strict_types = 1);
namespace App\Controller;

use App\Core\Controller;
use App\Core\App;
use App\Model\Notification;
use App\Core\Validator;
use App\Core\ValidationRule;
use App\Core\Result;
use App\Core\Auth;
use App\Core\Request;

class NotificationController extends Controller
{
    private Notification $notification;
    private array $data = [];
    private array $search;
    private Validator $validator;

    public function __construct(array $data = [])
    {
        $this->notification = new Notification();

        // Setup defaults
        $data = App::setupDefaults($data, [
                'notification_id' => 0,
                'account_id' => 0,
                'importance' => 0,
                'notification' => '',
                'params' => '{}',
                'notification_link' => '',
                'create_date' => date('Y-m-d')
        ]);

        
        // Get search params from both request body and query string
        $this->search = array_merge(json_decode($data['search'] ?? '[]', true), json_decode(Request::getQueryParams()['search'] ?? '[]', true));

        $this->data = $data;

        $this->validator = new Validator($this->data);
        $this->validator->add('notification_id', 'Invalid notification id', ValidationRule::number(1));
        $this->validator->add('account_id', 'Invalid account id', ValidationRule::number(1));
        $this->validator->add('importance', 'Invalid importance', ValidationRule::number(0));
        $this->validator->add('notification', 'Invalid notification', ValidationRule::string(1, 200));
        $this->validator->add('notification_status', 'Invalid notification status', ValidationRule::inList(['New', 'Read']));
    }

    // Guard: Admin, User
    public function Read(array $routeParams = []): Result
    {
        $isSingleRecord = false;
        $message = '';
        $messageType = '';

        if(array_key_exists('page', $routeParams) && $routeParams['page'] > 0){
            $this->search['page'] = $routeParams['page'];
        };
        
        if(array_key_exists('notification_id', $routeParams) && !empty($routeParams['notification_id'])){
            $this->search = ['notification_id' => $routeParams['notification_id']];
            $isSingleRecord = true;
        };
        
        $this->search['account_id'] = Auth::getUser('account_id');

        $resRead = $this->notification->Read($this->search);

        // DB Error
        if(is_null($resRead->data)){
            return $resRead;
        }

        if($isSingleRecord && empty($resRead->data)){
            $message = App::loc('{object} not found', '', ['object' => 'Notification']);
            $messageType = 'info';
        }

        $resRead->data = array_map(function($item) {
            $params = json_decode($item['params'], true);
            $item['notification'] = App::loc($item['notification'], '', $params);
            $item['params'] = $params;
            return $item;
        }, $resRead->data);

        return new Result(
            $resRead->data,
            $message,
            $messageType,
            '',
            $resRead->metaData
        );
    }

    // Guard: Admin, User
    public function Update(array $routeParams = []): Result
    {
        $this->data['account_id'] = Auth::getUser('account_id');

        if($dataErr = $this->validator->validate(['notification'])){
            return new Result(
                $dataErr,
                App::loc('Some data are missing or invalid'),
                'validation_error'
            );
        }

        $resUpdate = $this->notification->Update([
            'notification_id' => $this->data['notification_id'],
            'account_id' => $this->data['account_id'],
            'notification_status' => $this->data['notification_status']
        ]);

        // DB Error
        if(is_null($resUpdate->data)){
            return $resUpdate;
        }

        // Return created record
        $resRead = $this->notification->Read([
            'notification_id' => $this->data['notification_id']
        ]);

        // DB Error
        if(is_null($resRead->data)){
            return $resRead;
        }

        return new Result(
            $resRead->data,
            App::loc('The notification is set as read'),
            'success',
            '',
            $resRead->metaData
        );
    }

    // Guard: Admin, User
    public function Delete(array $routeParams = []): Result
    {
        $this->data['account_id'] = Auth::getUser('account_id');
        
        $resDelete = $this->notification->Delete([
            'notification_id' => $this->data['notification_id'],
            'account_id' => $this->data['account_id']
        ]);
        
        // DB Error
        if(is_null($resDelete->data)){
            return new Result(
                null,
                App::loc('Failed to delete {object}', '',['object' => 'Notification']),
                'error',
                ''
            );
        }
        
        return new Result(
            $this->data['notification_id'],
            App::loc('{object} deleted', '', ['object' => 'Notification']),
            'success',
            ''
        );
    }

    // Guard: Admin, User
    public function Count(array $routeParams = []): Result
    {
        $this->data['account_id'] = Auth::getUser('account_id');

        $resCount = $this->notification->Count([
            'account_id' => $this->data['account_id']
        ]);
        
        // DB Error
        if(is_null($resCount->data)){
            return new Result(
                null,
                App::loc('Failed to count {object}', '', ['object' => 'notifications']),
                'error',
                ''
            );
        }
        
        return new Result(
            $resCount->data,
            '',
            'success',
            ''
        );
    }
}
