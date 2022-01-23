<?php

use App\Core\App;
?>
<section class="access-denied">
    <h1><?= App::loc('Access denied')?></h1>
    <?php if(!empty($params['message'])):?>
        <p class="reason"><?= $params['message']?></p>
    <?php else:?>
        <p>لست مخولاً بالوصول إلى هذه الصفحة</p>
    <?php endif?>
</section>