<section class="container thank-you">
    <h2>Thank you <em><?= $params['full_name']?></em> for signing up to <b><?= $params['website_title']?></b> services</h2>
    
    <div class="sub-section">
        <p>Your account has been created but you still need to do some extra steps for us to verify your registration data</p>
        
        <ul>
            <li>We sent a message containing a verification link to your registered email <em><?= $params['email']?></em>, please click on that link</li>
            <li>We also sent an SMS containing a verification code to your registered mobile <em><?= $params['mobile']?></em>, so <a href="<?= $params['login_view_url']?>">log in to your account</a>, go to <a href="<?= $params['mobile_verification_view_url']?>">Mobile number verification</a> page, type in the code in the specified box and then click <b>Verify</b></li>
        </ul>
    </div>

    <div class="sub-section">
        <p>Once your email is verified you can <a href="<?= $params['login_view_url']?>">login to your account</a> and <a href="<?= $params['my_profile_view_url']?>">navigate to your profile</a></p>
        
        <ul>
            <li>Upload a clear photo of your face if you didn't already</li>
            <li>Register your cars if any</li>
        </ul>
    </div>

    <div  class="sub-section">
        <p>One of our staff members will then check your data and activate your account</p>
        <p>After that you'll be able to use our services</p>
    </div>
</section>