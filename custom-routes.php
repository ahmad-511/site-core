<?php
use App\Core\Router;
use \App\Controller;

// Router::get('/test2/{id}', function($reqParams){
//     return 'Hello, this is a test with ID #'.$reqParams['id'];
// });

// Router::get('/test/{something}', 'testView');
// Router::get('/test', 'testView');

// Account
Router::get('/api/User/Read', [Controller\UserController::class, 'Read']);
Router::get('/api/User/Read/{user_id}', [Controller\UserController::class, 'Read']);
Router::post('/api/User/Create', [Controller\UserController::class, 'Create']);
Router::post('/api/User/Update', [Controller\UserController::class, 'Update']);
Router::post('/api/User/Login', [Controller\UserController::class, 'Login']);
?>