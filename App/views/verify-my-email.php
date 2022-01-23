<?php
use App\Core\App;
?>

<section class="verification-result">
    <h2><?= App::loc('Email address verification')?></h2>
    <div class="message <?= $params['messageType']?>">
        <p><?= nl2br($params['message'])?></p>
    </div>
</section>