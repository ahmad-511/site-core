<?php
use App\Core\Router;

Router::get('/test2/{id}', function($reqParams){
    return 'Hello, this is a test with ID #'.$reqParams['id'];
});

Router::get('/test/{something}', 'testView');
Router::get('/test', 'testView');

Router::get('/test3', [\App\Model\User::class, 'Create']);
Router::get('/test3/{user_id}', [\App\Model\User::class, 'Read']);
?>