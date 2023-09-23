<?php
declare (strict_types = 1);

use App\Core\Auth;
use App\Core\Localizer as L;
use App\Core\Router;

Router::setCurrentLayout('blank');
?>
<section class="container">
    <h1><?= L::loc(strval($params['statusCode'])??'')?></h1>
    <h2><?= L::loc($params['errorMessage']??'')?></h2>
    
    <?php if($params['statusCode'] == 403){
        if(Auth::authenticated()):?>
            <a class="btn btn-blue" href="<?= Router::route('dashboard-view')?>"><?= L::loc('Dashboard')?></a>
        <?php else: ?>
            <a class="btn btn-blue" href="<?= Router::route('login-view')?>"><?= L::loc('Login')?></a>
        <?php endif ?>    
    <?php } ?>

    <?php if($params['statusCode'] == 404):?>
        <a class="btn btn-blue" href="<?= Router::route('dashboard-view')?>"><?= L::loc('Dashboard')?></a>
    <?php endif ?>
</section>