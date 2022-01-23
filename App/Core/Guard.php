<?php
declare (strict_types = 1);
namespace App\Core;

use App\Core\Auth;

class GuradResult{
    public bool $isAllowed;
    public string $redirectFileName;
    public string $message;

    public function __construct(bool $isAllowed = true, string $redirectFileName = '', string $message = '')
    {
        $this->isAllowed = $isAllowed;
        $this->redirectFileName = $redirectFileName;
        $this->message = $message;
    }
}

class Guard {
    /**
     * Check if current user can access specified view
     * @param string $routeName Route name
     * @return GuardResult
     */
    public static function canView($routeName): GuradResult
    {
        $adminOnly = [
            'countries-manager-status-view',
            'countries-manager-ids-view',
            'countries-manager-view',
            'locations-manager-status-view',
            'locations-manager-ids-view',
            'locations-manager-view',
            'makers-manager-view',
            'makers-manager-status-view',
            'makers-manager-ids-view',
            'accounts-manager-status-view',
            'accounts-manager-ids-view',
            'accounts-manager-view',
            'cars-manager-status-view',
            'cars-manager-ids-view',
            'cars-manager-view',
            'rides-manager-status-view',
            'rides-manager-ids-view',
            'rides-manager-view',
            'conversations-status-view',
            'conversations-view',
            'ratings-manager-view',
            'ratings-manager-status-view',
            'ratings-manager-ids-view',
            'reports-manager-status-view',
            'reports-manager-ids-view',
            'reports-manager-view',
        ];

        $verifiedUsers = [
            'dashboard-view',
            'my-profile-view',
            'profile-view',
            'my-cars-status-view',
            'my-cars-ids-view',
            'my-cars-view',
            'my-rides-status-view',
            'my-rides-ids-view',
            'my-rides-ids-request-id-view',
            'my-rides-view',
            'my-ride-requests-status-view',
            'my-ride-requests-ids-view',
            'my-ride-requests-view',
            'verify-my-mobile-view',
            'seat-reservation-request-view',
            'offer-a-ride-view',
            'notifications-status-view',
            'notifications-view',
            'my-conversations-status-view',
            'my-conversations-view',
            'messages-ride-recipient-view',
            'messages-ride-view',
            'ride-rating-view',
            'account-ratings-view',
            'my-ratings-view',
            'my-ratings-status-view',
            'my-reports-status-view',
            'my-reports-ids-view',
            'my-reports-view',
            'unsubscribe-view',
        ];

        $disabledViews = [];

        if(MOBILE_VERIFICATION_MODE == 'Receive'){
            $disabledViews[] = 'verify-my-mobile-view';
        }

        if(in_array($routeName, $disabledViews)){
            return new GuradResult(false, 'page-not-found');
        }

        if(in_array($routeName, $adminOnly) && !(Auth::authenticated() && Auth::getUser('account_type') == 'Admin')){
            return new GuradResult(false, 'login', "This is an admin's area only");
        }

        if(in_array($routeName, $verifiedUsers) && !(Auth::authenticated() && in_array(Auth::getUser('account_status'), ['Verifying', 'Active']))){
            return new GuradResult(false, 'login', 'You are not logged in or your account is suspended/not verified');
        }
        
        return new GuradResult();
    }
    
    /**
     * Check if current account is allowed to execute sepecified controller's method
     * @param string $controller Controller name
     * @param string $method Controller's method name
     * @return GuradResult
     */
    public static function canExecute($controller, $method): GuradResult
    {
        $controller = str_replace('App\\Controller\\', '', $controller);

        $adminOnly = [
            'AccountController::Create',
            'AccountController::Read',
            'AccountController::List',
            'AccountController::Update',
            'AccountController::Delete',
            'AccountController::SendVerificationEmail',
            'AccountController::SendVerificationSMS',
            
            'CarController::Create',
            'CarController::Read',
            'CarController::Update',
            'CarController::Delete',
            'CarController::List',

            'CountryController::List',
            'CountryController::Create',
            'CountryController::Update',
            'CountryController::Delete',

            'LocationController::Create',
            'LocationController::Read',
            'LocationController::Update',
            'LocationController::Delete',

            'MakerController::Create',
            'MakerController::Read',
            'MakerController::Update',
            'MakerController::Delete',

            'RideController::Create',
            'RideController::Read',
            'RideController::Update',
            'RideController::Delete',
            'RideController::LastSpecs',

            'RideRequestController::Create',
            'RideRequestController::Read',
            'RideRequestController::ReadIncomingRideRequests',
            'RideRequestController::Update',
            'RideRequestController::UpdateIncomingRideRequest',
            'RideRequestController::Delete',

            'ConversationController::Delete',

            'ConversationController::ReadConversations',
            'MessageController::Delete',

            'RatingController::Read',
            'RatingController::Update',
            'RatingController::Delete',
            
            'ReportController::Create',
            'ReportController::Read',
            'ReportController::Update',
            'ReportController::Delete',
        ];
        
        $verifiedUsers = [
            'AccountController::ReadMyAccount',
            'AccountController::UpdateMyAccount',
            'AccountController::DeleteMyAccount',
            'AccountController::Logout',
            'AccountController::SendMeVerificationEmail',
            'AccountController::SendMeVerificationSMS',
            'AccountController::VerifyMyMobile',
            'AccountController::Unsubscribe',
            
            'CarController::CreateMyCar',
            'CarController::ReadMyCars',
            'CarController::UpdateMyCar',
            'CarController::DeleteMyCar',
            'CarController::DeleteCarPhoto',
            'CarController::PhotoList',
            'CarController::Photo',
            'CarController::MyList',

            'RideController::CreateMyRide',
            'RideController::ReadMyRides',
            'RideController::UpdateMyRide',
            'RideController::DeleteMyRide',
            'RideController::Currency',
            'RideController::MyLastSpecs',

            'RideRequestController::CreateMyRideRequest',
            'RideRequestController::ReadMyRideRequests',
            'RideRequestController::ReadMyIncomingRideRequests',
            'RideRequestController::UpdateMyRideRequest',
            'RideRequestController::UpdateMyIncomingRideRequest',
            'RideRequestController::DeleteMyRideRequest',

            'NotificationController::Read',
            'NotificationController::Update',
            'NotificationController::Delete',
            'NotificationController::Count',
            
            'ConversationController::ReadMyConversations',

            'MessageController::Read',
            'MessageController::Create',

            'RatingController::Read',
            'RatingController::ReadMyReceivedRatings',
            'RatingController::ReadMyGivenRatings',
            'RatingController::CreateMyRating',
            'RatingController::UpdateMyRating',
            'RatingController::GetMyRatingDetails',
            'RatingController::GetRatingDetails',
            'RatingController::AccountRatings',

            'ReportController::CreateMyReport',
            'ReportController::ReadMyReports',
            'ReportController::ReadReportsAgainstMe',
            'ReportController::AccountsList',

            'DashboardController::Stats',
        ];

        $disabledMethods = [];

        if(MOBILE_VERIFICATION_MODE == 'Send'){
            $disabledMethods[] = 'AccountController::VerifyMyMobileWebhook';
        }

        if(MOBILE_VERIFICATION_MODE == 'Receive'){
            $disabledMethods[] = 'AccountController::SendMeVerificationSMS';
            $disabledMethods[] = 'AccountController::VerifyMyMobile';
        }
        
        if(in_array("$controller::$method", $disabledMethods)){
            return new GuradResult(false, '', 'Not found');
        }

        if(in_array("$controller::$method", $adminOnly) && !(Auth::authenticated() && Auth::getUser('account_type') == 'Admin')){
            return new GuradResult(false, '', 'Only admin can call this action');
        }

        if(in_array("$controller::$method", $verifiedUsers) && !(Auth::authenticated() && in_array(Auth::getUser('account_status'), ['Verifying', 'Active']))){
            return new GuradResult(false, '', "You do not have necessary privileges to call this action");
        }

        return new GuradResult();
    }
}
?>