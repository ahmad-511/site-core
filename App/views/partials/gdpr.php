<?php

use App\Core\Localizer as L;

?>

<div class="gdpr-consent hidden" id="gdprConsent">
	<?= L::loc('This website uses minimum amount of cookies to provide you with best experience')?>
	<button class="button"><?= L::loc('I Consent')?></button>
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