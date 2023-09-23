<?php
use App\Core\Localizer as L;
?>

<section class="verification-result">
    <h2><?= L::loc('Email address verification')?></h2>
    <div class="message <?= $params['messageType']?>">
        <p><?= nl2br($params['message'])?></p>
    </div>
</section>