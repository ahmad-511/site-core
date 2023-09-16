<?php
declare (strict_types = 1);

use App\Service\FakeSMSService;
use App\Service\SMSEgyptService;
use App\Service\SMSService;

// use App\Controller\AccountController;
// use App\Service\MailService;
// use App\Core\DB;
// use App\Core\App;
// use App\Core\Router;
// use App\Model\Account;
// use App\Core\ExpressionParser;

?>

<!-- <h1>Sending emails</h1> -->
<h1>Sending SMS</h1>

<?php
// DB::beginTransaction();

// for($i = 1; $i < 50; $i++){
//     $resSend = MailService::Send(
//         'zaks@zaksdg.com',
//         'Hello {name}',
//         'contact-us',
//         [
//             'message' => 'Hello {name},\nWe are here to help you generate you first email template',
//             'name' => ['Ahmad', 'Khalid', 'Yasmin'][random_int(0, 2)],
//             'email' => ['ahmad@gmail.com', 'khalid@gmail.com', 'yasmin@gmail.com'][random_int(0, 2)],
//             'date' => date('Y-m-d')
//         ],
//         true,
//         random_int(0, 5),
//         ['en', 'ar'][random_int(0, 1)]
//     );

//     echo '<pre>', print_r($resSend, true), '</pre>';
// }

// DB::commit();

// $accountController = new AccountController(['account_id' => 1]);
// $accountController->SendVerificationEmail();

// $accountController->SendSignupEmail(1);

// $resProcess = MailService::ProcessQueue(true);


// Testing Expression parser
// echo '<pre>';
// echo '10-2+3/2*4 = ', 10-2+3/2*4, PHP_EOL;
// echo 'Result = ', ExpressionParser::parse('10-2+3/2*4'), PHP_EOL, PHP_EOL;

// echo '2** -3 **3*4*2*( (2+(3) )*-3) = ', 2** -3 **3*4*2*( (2+(3) )*-3), PHP_EOL;
// echo 'Result = ', ExpressionParser::parse('2** -3 **3*4*2*( (2+(3) )*-3)'), PHP_EOL, PHP_EOL;

// echo '-3-3*-8 = ', -3-3*-8, PHP_EOL;
// echo 'Result = ', ExpressionParser::parse('-3-3*-8'), PHP_EOL, PHP_EOL;

// echo '27/2+15*(-12/-3)*(2+4) = ', 27/2+15*(-12/-3)*(2+4), PHP_EOL;
// echo 'Result = ', ExpressionParser::parse('27/2+15*(-12/-3)*(2+4)'), PHP_EOL, PHP_EOL;

// echo '27/2+15*(-12/-3)*(2+4) + 2** -3 **3*4*2*((2+(3))*-3) = ', 27/2+15*(-12/-3)*(2+4) + 2** -3 **3*4*2*((2+(3))*-3), PHP_EOL;
// echo 'Result = ', ExpressionParser::parse('27/2+15*(-12/-3)*(2+4) + 2** -3 **3*4*2*((2+(3))*-3)'), PHP_EOL, PHP_EOL;

// echo 'true && false && false = ', true && false && false, PHP_EOL;
// echo 'Result = ', ExpressionParser::parse('true && false && false'), PHP_EOL, PHP_EOL;

// echo '4>=2 && 3<5 = ', 4>=2 && 3<5, PHP_EOL;
// echo 'Result = ', ExpressionParser::parse('4>=2 && 3<5'), PHP_EOL, PHP_EOL;

// echo App::loc("{name} is requesting a ride on your journey departing from {from_location} to {to_location}[1]");

// print_r(ReleanSMSService::Send('201227662354', 'وصلك شي ابن عمي , أنا عبد الله'));
// print_r(SMSService::Send('201020663902', 'Hello from KhednyM3ak'));
// print_r(SMSService::Send('123456', 'Hello from KhednyM3ak'));
// print_r(FakeSMSService::Send('201227662354', 'Hello from KhednyM3ak'));

// echo '</pre>';
// ( [data] => 1 [message] => Your message was sent ! [messageType] => success [redirect] => [metaData] => Array ( [type] => success [msg] => Your message was sent ! [data] => Array ( [smsid] => 53928896 [sent] => 1 [failed] => 0 [reciver] => 201227662354 ) ) )
// SMSEgyptService [{"type":"success","msg":"Your message was sent !","data":{"smsid":"53928863","sent":1,"failed":0,"reciver":"201227662354"}}]
?>

<script type="module">
    import xhr from './js/xhr.js'

    xhr({
        method: 'PUT',
        url: '/put',
        body: {a:1, b:2},
        callback(data, status){
            console.log(data, status)
        }
    }) 
</script>