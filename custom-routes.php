<?php
use App\Core\Router;
use App\Controller\AccountController;
use App\Core\Response;
use App\Core\Result;

Router::get('/test/{something}', 'testView');
Router::get('/test', 'testView');

Router::get('/test2/{id}', function($reqParams){
    return 'Hello, this is a test with ID #'.$reqParams['id'];
});

Router::get('/get', function($reqParams){
    $data = ['a' => 1, 'b' => 2];
    Response::json($data, 201);
});

Router::put('/put', function($reqParams){
    $data = ['a' => 1, 'b' => 2];
    $resp = new Response($data, 201);
    Response::send($resp);
});

// Account
Router::get('/api/Account/Read', [AccountController::class, 'Read']);
Router::get('/api/Account/Read/{account_id}', [AccountController::class, 'Read']);
Router::post('/api/Account/Create', [AccountController::class, 'Create']);
Router::post('/api/Account/Update', [AccountController::class, 'Update']);
Router::post('/api/Account/Login', [AccountController::class, 'Login']);
Router::get('/api/Account/Photo/{photo_path}', [AccountController::class, 'Photo'], 'account-photo');
