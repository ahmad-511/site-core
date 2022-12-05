<?php

use App\Core\App;

?>

<div class="gdpr-consent hidden" id="gdprConsent">
	<?= App::loc('This website uses minimum amount of cookies to provide you with best experience')?>
	<button class="button"><?= App::loc('I Consent')?></button>
</div>

<script type="module">
	import {$} from '/js/main.js';

	if(localStorage.getItem('gdpr_consent') == 1){
		$('#gdprConsent').remove();
	}else{
		$('#gdprConsent').classList.remove('hidden');

		$('#gdprConsent .button').addEventListener('click', e => {
			localStorage.setItem('gdpr_consent', 1);
			$('#gdprConsent').remove();
		});
	}
</script>