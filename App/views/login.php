<section class="login center-center">
    <form id="frmLogin" class="form">
        <div class="slogan">
            <img class="logo" src="img/logo-small.png" alt="<?= WEBSITE_TITLE?>">
        </div>
       
        <div class="control-group">
            <label for="email">Email</label>
            <input type="email" id="email" autocomplete="off" autofocus>
        </div>
        <p class="control-group">
            <label for="password">Password</label>
            <input type="password" id="password">
        </p>
    
        <p class="form-operations">
            <input type="submit" class="button action" id="btnSubmit" value="Login">
        </p>
    </form>
</section>
<script type="module">
    import {$, $$, errorInResponse, showMessage} from '/App/js/main.js';
    import Ajax from '/App/js/ajax.js';

    $('#frmLogin').addEventListener('submit', e => {
        e.preventDefault();

        let data = {
            email: $('#email').value,
            password: $('#password').value
        };

        btnSubmit.disabled = true;
        // Send Ajax request
        Ajax('POST', '/api/User/Login',
            data,
            function (resp) {
                btnSubmit.disabled = false;

                if (errorInResponse(resp)) {
                    return false;
                }

                setTimeout(function(){
                    document.location.href = resp.redirect || '/Home';
                }, 2000);
            }
        );
    });
</script>