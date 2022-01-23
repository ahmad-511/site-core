<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<base href="<?= ROUTING_BASE?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <link rel="apple-touch-icon" sizes="180x180" href="/icons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/icons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/icons/favicon-16x16.png">
    <link rel="manifest" href="/icons/site.webmanifest">
    <link rel="mask-icon" href="/icons/safari-pinned-tab.svg" color="#5bbad5">
    <link rel="shortcut icon" href="/icons/favicon.ico">
    <meta name="msapplication-TileColor" content="#da532c">
    <meta name="msapplication-config" content="/icons/browserconfig.xml">
    <meta name="theme-color" content="#ffffff">

    <meta name="robots" content="robots.txt">
    <title>Under constructions | <?= WEBSITE_TITLE?></title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            background-color: #000;
        }

        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh; /* expand the body to cover all viewport height */
            font-family: Verdana, Geneva, Tahoma, sans-serif;
            font-size: 16px;
            background-color: #000;
            color: #fff;
        }

        ::placeholder{ /* Firefox 18- */
            color: #212121;
        }
        
        .wrapper {
            margin: auto;
            text-align: center;
        }

        h1, h3 {
            margin: 1rem 0;
            font-weight: normal;
        }

        img {
            max-width: 100%;
        }

        input[type="password"] {
            padding: 5px;
            border-radius: 4px;
            border: 1px solid #212121;
            background-color: #ffc900;
            outline: none;
            text-align: center;
            font-size: 1rem;
            transition: border-color .3s, box-shadow .3s;
        }

        input[type="password"]:focus {
            box-shadow: #ffc900  0 0 5px;
        }

        .stripes {
            height: 40px;
            background-image: linear-gradient(45deg, #ffc900 25%, #212121 25%, #212121 50%, #ffc900 50%, #ffc900 75%, #212121 75%, #212121 100%);
            background-size: 113.14px 113.14px;
        }

    </style>
</head>
<body>
    <div class="stripes">&nbsp</div>

    <section class="wrapper">
        <img src="/App/img/logo.png" alt="<?= WEBSITE_TITLE?>" >
        <h3>Under constructions</h3>
        <form method="POST">
            <input type="password" name="password" autofocus placeholder="Your password">
        </form>
    </section>
    
    <div class="stripes">&nbsp;</div>
</body>
</html>