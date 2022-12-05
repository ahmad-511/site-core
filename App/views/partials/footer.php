<?php

use App\Core\App;
use App\Core\Router;

?>

<footer class="main-footer">
	<div class="company">
		<img class="logo" src="/img/logo.png" alt="<?= WEBSITE_TITLE ?>">
		<h3><?= WEBSITE_TITLE ?></h3>
		<p class="slogan"><?= WEBSITE_SLOGAN ?></p>
	</div>

	<div class="pages">
		<h3>Pages</h3>
		<ul>
			<li><a href="<?= Router::routeUrl('home-view')?>"><?=App::loc('Home')?></a></li>
			<li><a href="<?= Router::routeUrl('contact-us-view')?>"><?=App::loc('Contacts')?></a></li>
			<li><a href="<?= Router::routeUrl('about-view')?>"><?=App::loc('About')?></a></li>
			<li><a href="<?= Router::routeUrl('terms-of-service-view')?>"><?=App::loc('Terms of service')?></a></li>
			<li><a href="<?= Router::routeUrl('privacy-policy-view')?>"><?=App::loc('Privacy policy')?></a></li>
		</ul>
	</div>

	<div class="social">
		<h3>Contact us</h3>
		<ul>
			<li><a target="_blank" href="https://facebook.com/<?=FACEBOOK_ID?>"><i class="icon-facebook"></i><?=App::loc('Facebook')?></a></li>
			<li><a target="_blank" href="https://www.linkedin.com/in/<?=LINKEDIN_ID?>"><i class="icon-linkedin"></i><?=App::loc('Linkedin')?></a></li>
			<li><a target="_blank" href="https://wa.me/<?= str_replace(' ', '', WHATSAPP)?>"><i class="icon-whatsapp"></i><?=App::loc('Whatsapp')?></a></li>
		</ul>
	</div>
</footer>

<?php Router::renderContent('partials/copyright')?>
<?php Router::renderContent('partials/gdpr')?>
