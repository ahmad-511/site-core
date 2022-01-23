<?php
    declare (strict_types = 1);

    use App\Core\App;
    use App\Core\Auth;
    use App\Core\Router;
?>

<div class="main-wrapper">
    <section class="account-section container">    
        <div class="account">
            <img src="<?= $params['personal_photo_url']?>" alt="<?= $params['name'], ' ', $params['surname']?>">
            <div class="account-data">
                <h2><?= $params['name'], ' ', $params['surname']?></h2>
                
                <label><?= App::loc('Country')?></label>
                <span><?= $params['country']?></span>
            
                <label><?= App::loc('Language')?></label>
                <span><?= App::loc($params['preferred_language'])?></span>
            
                <label><?= App::loc('Status')?></label>
                <span><?= App::loc($params['account_status'])?> <i class="tag tag-<?= strtolower($params['account_status'])?>"></i></span>
                
                <div class="star-rating-container">
                    <p>
                        <label><?= App::loc('Ratings count')?></label>    
                        <span><?= $params['ratings_count']?></span>
                    </p>
                    <p>
                        <span class="bidi"><?= str_repeat('<i class="icon-star"></i>', intval($params['rating'])), str_repeat('<i class="icon-star-o"></i>', 5 - intval($params['rating']))?></span>
                        <span><?= $params['rating']?></span> <span><?= $params['rating_description']?></span>
                    </p>
                </div>
            </div>
        </div>

        <?php if(Auth::getUser('account_type') == 'Admin'):?>
            <?php if(!empty($params['admin_notes'])):?>
                <p class="admin-notes">
                    <label><?= App::loc('Admin notes')?></label><br>
                    <?= $params['admin_notes']?>
                </p>
            <?php endif?>
            <?php if(!empty($params['remarks'])):?>
                <p class="remarks">
                    <label><?= App::loc('Remarks')?></label><br>
                    <?= $params['remarks']?>
                </p>
            <?php endif?>
        <?php endif?>
    </section>

    <?php if(!empty($params['rating_details'])):?>
    <section class="container rating-details">
        <h2><?= App::loc('Rating details')?></h2>

        <ul>
        <?php foreach($params['rating_details'] as $r):?>
            <li>
                <span class="bidi"><?= $r['stars']?></span>
                <span><?= $r['rating_description']?></span>
                <span class="ratings-count"><?= $r['ratings_count']?></span>
            </li>
        <?php endforeach?>
        </ul>
    </section>
    <?php endif?>

    <?php if(!empty($params['latest_ratings'])):?>
    <section class="container latest-ratings">
        <h2><?= App::loc('Latest received ratings')?></h2>

        <?php foreach($params['latest_ratings'] as $item):?>
            <div class="rating-item">
                <a href="<?= $item['account_profile_url']?>" target="_blank">
                    <img src="<?= $item['account_photo_url']?>" alt="<?= $item['account']?>">
                    
                    <p>
                        <span class="account"><?= $item['account']?></span><br>
                        <span class="bidi">
                            <?= $item['rating']?>
                            <i class="icon-star star"></i>
                            <?= $item['rating_description']?>
                        </span>
                    </p>
                </a>

                <p class="comment" dir="auto"><?= $item['comment']?></p>
                <p>
                    <span class="hint date-time bidi"><?= $item['rating_date']?></span>
                </p>
            </div>
        <?php endforeach?>

        <p class="more-links">
            <a target="_blank" href="<?= Router::routeUrl('account-ratings-view', ['account_id' => $params['account_id']]) ?>">■ <?= App::loc('View full ratings list')?></a>
        </p>
    </section>  
    <?php endif?>
</div>