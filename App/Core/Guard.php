<?php
declare (strict_types = 1);
namespace App\Core;

use App\Core\Auth;
use App\Core\GuardResult;

class Guard {
    /**
     * Check if current user can access specified view
     * @param string $routeName Route name
     * @return GuardResult
     */
    public static function canView($routeName): GuardResult
    {
        // View names that require authentication
        $roles = [
            'accounts-view' => ['Admin'],
            'countries-view' => ['Admin', 'Management'],
            'cities-view' => ['Admin', 'Management'],
            'ports-view' => ['Admin', 'Management'],
            'ranks-view' => ['Admin', 'Management'],
            'document-types-view' => ['Admin', 'Management'],
            'vessel-types-view' => ['Admin', 'Management'],
            'vessels-view' => ['Admin', 'Management'],
            'seafarers-view' => ['Admin', 'Management', 'Manning'],
            'experiences-view' => ['Admin', 'Management', 'Manning'],
            'documents-view' => ['Admin', 'Management', 'Manning'],
            'applications-view' => ['Admin', 'Management', 'Manning'],
            'contracts-view' => ['Admin', 'Management', 'Manning'],
            'crew-change-view' => ['Admin', 'Management', 'Manning'],
            'dashboard-view' => ['Admin', 'Management', 'Manning'],
            'my-profile-view' => ['Admin', 'Management', 'Manning'],
        ];

        // View names to be disabled in certain conditions (as if not exists)
        $disabledViews = [];

        if(array_key_exists($routeName, $disabledViews)){
            return new GuardResult(false, 'error', 'File not found', 404);
        }

        // Authentication is required
        if(array_key_exists($routeName, $roles)){
            if(!Auth::authenticated()){
                return new GuardResult(false, 'error', "You are not an authenticated user", 403);
            }

            // Check user role
            $allowedRoles = $roles[$routeName];
            
            if(!in_array(Auth::getUser('role'), $allowedRoles)){
                $allowedRoles = implode(' / ', $allowedRoles);
                return new GuardResult(false, 'error', "You must have $allowedRoles role to access this aria", 403);
            }
        }
        
        return new GuardResult();
    }
    
    /**
     * Check if current account is allowed to execute specified controller's method
     * @param string $controller Controller name
     * @param string $method Controller's method name
     * @return GuardResult
     */
    public static function canExecute($controller, $method): GuardResult
    {
        $controller = str_replace('App\\Controller\\', '', $controller);

        // Controller methods that require authentication
        $roles = [
            'MonitorController::Dashboard' => ['Admin', 'Management', 'Manning'],
            'MonitorController::DocumentExpiry' => ['Admin', 'Management', 'Manning'],
            'MonitorController::ComingSignOffs' => ['Admin', 'Management', 'Manning'],

            
            'AccountController::Read' => ['Admin'],
            'AccountController::Create' => ['Admin'],
            'AccountController::Update' => ['Admin'],
            'AccountController::Delete' => ['Admin'],
            'AccountController::ReadMyProfile' => ['Admin', 'Management', 'Manning'],
            'AccountController::UpdateMyProfile' => ['Admin', 'Management', 'Manning'],
            'AccountController::Logout' => ['Admin', 'Management', 'Manning'],

            'AttachmentService::LoadFile' => ['Admin', 'Management', 'Manning'],

            'CountryController::List' => ['Admin', 'Management', 'Manning'],
            'CountryController::Read' => ['Admin', 'Management'],
            'CountryController::Create' => ['Admin', 'Management'],
            'CountryController::Update' => ['Admin', 'Management'],
            'CountryController::Delete' => ['Admin', 'Management'],

            'CityController::Read' => ['Admin', 'Management'],
            'CityController::Create' => ['Admin', 'Management'],
            'CityController::Update' => ['Admin', 'Management'],
            'CityController::Delete' => ['Admin', 'Management'],

            'PortController::List' => ['Admin', 'Management'],
            'PortController::Read' => ['Admin', 'Management'],
            'PortController::Create' => ['Admin', 'Management'],
            'PortController::Update' => ['Admin', 'Management'],
            'PortController::Delete' => ['Admin', 'Management'],

            'VesselTypeController::Read' => ['Admin', 'Management'],
            'VesselTypeController::Create' => ['Admin', 'Management'],
            'VesselTypeController::Update' => ['Admin', 'Management'],
            'VesselTypeController::Delete' => ['Admin', 'Management'],
            'VesselTypeController::ReadDesignatedDocuments' => ['Admin', 'Management'],
            'VesselTypeController::SaveDesignatedDocuments' => ['Admin', 'Management'],

            'DocumentTypeController::Read' => ['Admin', 'Management'],
            'DocumentTypeController::Create' => ['Admin', 'Management'],
            'DocumentTypeController::Update' => ['Admin', 'Management'],
            'DocumentTypeController::Delete' => ['Admin', 'Management'],
            'DocumentTypeController::ReadDocumentRanks' => ['Admin', 'Management'],
            'DocumentTypeController::SaveDocumentRanks' => ['Admin', 'Management'],
            'DocumentTypeController::RankDocumentsList' => ['Admin', 'Management', 'Manning'],

            'RankController::Read' => ['Admin', 'Management'],
            'RankController::Create' => ['Admin', 'Management'],
            'RankController::Update' => ['Admin', 'Management'],
            'RankController::Delete' => ['Admin', 'Management'],

            'VesselController::Read' => ['Admin', 'Management'],
            'VesselController::Create' => ['Admin', 'Management'],
            'VesselController::Update' => ['Admin', 'Management'],
            'VesselController::Delete' => ['Admin', 'Management'],
            'VesselController::ReadSafeManning' => ['Admin', 'Management'],
            'VesselController::SaveSafeManning' => ['Admin', 'Management'],

            'SeafarerController::List' => ['Admin', 'Management', 'Manning'],
            'SeafarerController::Read' => ['Admin', 'Management', 'Manning'],
            'SeafarerController::Create' => ['Admin', 'Management', 'Manning'],
            'SeafarerController::Update' => ['Admin', 'Management', 'Manning'],
            'SeafarerController::Delete' => ['Admin', 'Management', 'Manning'],

            'ExperienceController::Read' => ['Admin', 'Management', 'Manning'],
            'ExperienceController::GetTotalSeaService' => ['Admin', 'Management', 'Manning'],
            'ExperienceController::Read' => ['Admin', 'Management', 'Manning'],
            'ExperienceController::Create' => ['Admin', 'Management', 'Manning'],
            'ExperienceController::Update' => ['Admin', 'Management', 'Manning'],
            'ExperienceController::Delete' => ['Admin', 'Management', 'Manning'],

            'DocumentController::Read' => ['Admin', 'Management', 'Manning'],
            'DocumentController::CheckValidity' => ['Admin', 'Management', 'Manning'],
            'DocumentController::Read' => ['Admin', 'Management', 'Manning'],
            'DocumentController::Create' => ['Admin', 'Management', 'Manning'],
            'DocumentController::Update' => ['Admin', 'Management', 'Manning'],
            'DocumentController::DeleteFile' => ['Admin', 'Management', 'Manning'],
            'DocumentController::Delete' => ['Admin', 'Management', 'Manning'],

            'ApplicationController::List' => ['Admin', 'Management', 'Manning'],
            'ApplicationController::Read' => ['Admin', 'Management', 'Manning'],
            'ApplicationController::Create' => ['Admin', 'Management', 'Manning'],
            'ApplicationController::Update' => ['Admin', 'Management', 'Manning'],
            'ApplicationController::Delete' => ['Admin', 'Management', 'Manning'],

            'ContractController::List' => ['Admin', 'Management', 'Manning'],
            'ContractController::Read' => ['Admin', 'Management', 'Manning'],
            'ContractController::Create' => ['Admin', 'Management', 'Manning'],
            'ContractController::Update' => ['Admin', 'Management', 'Manning'],
            'ContractController::Delete' => ['Admin', 'Management', 'Manning'],

            'RouteController::RecentRoute' => ['Admin', 'Management', 'Manning'],
            'RouteController::PreviousRoute' => ['Admin', 'Management', 'Manning'],
            'RouteController::NextRoute' => ['Admin', 'Management', 'Manning'],
            'RouteController::CheckRouteSafeManning' => ['Admin', 'Management', 'Manning'],
            'RouteController::Read' => ['Admin', 'Management', 'Manning'],
            'RouteController::Read' => ['Admin', 'Management', 'Manning'],
            'RouteController::Create' => ['Admin', 'Management', 'Manning'],
            'RouteController::Update' => ['Admin', 'Management', 'Manning'],
            'RouteController::Delete' => ['Admin', 'Management', 'Manning'],

            'CrewChangeController::ReadRoute' => ['Admin', 'Management', 'Manning'],
            'CrewChangeController::Read' => ['Admin', 'Management', 'Manning'],
            'CrewChangeController::Create' => ['Admin', 'Management', 'Manning'],
            'CrewChangeController::Update' => ['Admin', 'Management', 'Manning'],
            'CrewChangeController::Delete' => ['Admin', 'Management', 'Manning'],
        ];
        
        // Controller methods that must be disabled in certain conditions
        $disabledMethods = [];
        
        if(in_array("$controller::$method", $disabledMethods)){
            return new GuardResult(false, '', 'Not found', 404);
        }

        // Authentication is required
        if(array_key_exists("$controller::$method", $roles)){
            if(!Auth::authenticated()){
                return new GuardResult(false, 'error', "You are not an authenticated user", 403);
            }

            // Check user role
            $allowedRoles = $roles["$controller::$method"];
            
            if(!in_array(Auth::getUser('role'), $allowedRoles)){
                $allowedRoles = implode(' / ', $allowedRoles);
                return new GuardResult(false, 'error', "You must have $allowedRoles role to call this action", 403);
            }
        }

        return new GuardResult();
    }
}
?>