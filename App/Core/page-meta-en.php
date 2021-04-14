<?php

use App\Core\App;

/**
 * Specify page/view meta tags for localized version of the page/view (depending on the file suffix, i.e. page-meta-xx.php)
 * Use page/view code as a key, the value is the data related to it
 * Supported meta tags are: title, description, keywords, image, url and card
 * These tags will be reflected to some other social media related tags (og:title, og:description, og:image, og:url, twitter:card)
 */
return [
    'home'=>[
        'title'=>App::loc('Home', 'en'),
        'description'=> '',
        'keywords'=>'',
        'image'=>WEBSITE_URL . '/img/og-home-image.png'
    ],
];
?>