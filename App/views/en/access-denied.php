<?php

use App\Core\App;
?>
<section class="access-denied container">
    <h1><?= App::loc('Access denied')?></h1>
    <?php if(!empty($params['message'])):?>
        <p><?= $params['message']?></p>
    <?php else:?>
        <p>You are not authorized to access this page</p>
    <?php endif?>
</section>