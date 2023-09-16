<?php
declare (strict_types = 1);
namespace App\Service;

use App\Core\Localizer as L;
use App\Core\Result;
use App\Core\Service;
use App\Model\MailProvider;
use App\Model\MailQueue;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use App\Core\DB;
use App\Core\Template;

class MailService extends Service
{
    private static int $MaxBatchSize = 25;
    private static int $SendRate = 10;
    private static bool $SMTPAuth = false;
    private static string $SMTPSecure = ''; // tls, ssl, ''
    private static string $OutputDir = '';
    private static array $ProviderDefault = [];

    public static function SetMaxBatchSize(int $size){
        self::$MaxBatchSize = $size;
    }

    public static function SetSendRate(int $rate){
        self::$SendRate = $rate;
    }

    public static function SetSMTPAuth(bool $smtpAuth){
        self::$SMTPAuth = $smtpAuth;
    }

    public static function SetSMTPSecure(string $smtpSecure){
        self::$SMTPSecure = $smtpSecure;
    }

    public static function SetOutputDir(string $outputDir){
        self::$OutputDir = $outputDir;
    }

    public static function SetProviderDefault(array $providerDefault){
        self::$ProviderDefault = $providerDefault;
    }

    public static function ParseAddress($address){
		preg_match('#([a-z0-9._+-]+)@([a-z0-9.-]+\.[a-z]{2,6})#i', $address, $m);
	
		if(empty($m)){
			return [];
		}
		
		$email = $m[0];
		$name = trim(str_replace([$email, '<', '>'], '', $address));

		if(empty($name)){
			$name = $m[1];
		}
        
		return array(
			'email' =>$email,
			'name'  =>$name,
			'domain'=>$m[2]
		);
	}

    private static function GetProviders(): array{
        $mailProvider = new MailProvider();
        $resRead = $mailProvider->Read();

        if(is_null($resRead->data)){
            return [];
        }

        return $resRead->data;
    }

    public static function Send(string $to, string $subject = '', string $body='', array $params = [], bool $useTemplate = false, int $priority = 2, string $languageCode = ''): Result
    {
        static $mailQueue = null;

        if(is_null($mailQueue)){
            $mailQueue = new MailQueue();
        }
        
        $resCreate = $mailQueue->Create([
            'to' => $to,
            'subject' => $subject,
            'body' => $body,
            'params' => json_encode($params),
            'use_template' => $useTemplate,
            'priority' => $priority,
            'language_code' => $languageCode,
            'queue_date' => date('Y-m-d H:i:s'),
            'status' => 'Pending',
            'remarks' => ''
        ]);

        if(is_null($resCreate->data)){
            return $resCreate;
        }

        $queueID = $resCreate->data;

        return new Result(
            $queueID,
            L::loc('Message sent'),
            'success'
        );
    }

    public static function SendMultiple($messages = []):Array
    {
        $data = [];
        
        DB::beginTransaction();
        foreach($messages as [$to, $subject, $body, $params, $useTemplate, $priority, $languageCode]){
            $res = self::Send($to, $subject, $body, $params, (bool)$useTemplate, intval($priority), $languageCode);

            if(is_null($res)){
                DB::RollBack();
                return null;
            }

            $data[] = $res;
        }
        DB::commit();

        return $data;
    }

    public static function ProcessQueue(bool $onlyHightPriority = false):Result
    {
        $providers = self::GetProviders();

        if(empty($providers)){
            return new Result(
                0,
                L::loc('Mail providers out of capacity'),
                'info'
            );
        }
        
        static $mailQueue = null;

        if(is_null($mailQueue)){
            $mailQueue = new MailQueue();
        }

        $batchLeft = self::$MaxBatchSize;

        $sendStatus = [];

        // Cache template for better performance
        $currentCachingState = Template::IsCaching();
        Template::EnableCaching(true);

        foreach($providers as $provider){
            $batchSize = min($batchLeft, $provider['hour_capacity']);
            $batchLeft -= $batchSize;

            // Get mails from the queue
            $params = ['batch_size' => $batchSize];

            if($onlyHightPriority){
                $params['priority'] = 0;
            }

            $resRead = $mailQueue->Read($params);

            // Exit when the queue is empty
            if(empty($resRead->data)){
                break;
            }

            $providerName = $provider['provider'];

            $sendStatus[$providerName] = self::Dispatch($resRead->data, $provider);
        }

        // Restore previous caching state
        Template::EnableCaching($currentCachingState);

        return new Result($sendStatus);
    }

    private static function Dispatch(array $messages = [], array $provider = []): array
    {
        // Construct PHPMailer with exception handling
        static $mailer = null;

        if(is_null($mailer)){
            $mailer = new PHPMailer(true);
        }
        
        self::SetupMailer($mailer, $provider);
        $sendStatus = [];

        $sentCount = 0;

        DB::beginTransaction();

        foreach($messages as $message){
            $emailStatus = [
                'queue_id' => $message['queue_id'],
                'status' => 'success',
                'remarks' => ''
            ];
            
            $emlParts = self::ParseAddress($message['to']);

            if(empty($emlParts)){
                $emailStatus['status'] = 'Failed';
                $emailStatus['remarks'] = 'Bad email format';
                $sendStatus[] = $emailStatus;

                self::UpdateEmailStatus($emailStatus);

                continue;
            }

			$mailer->addAddress($emlParts['email'], $emlParts['name']);
			
            $params = json_decode($message['params'], true);

            // Handling template
            $body = $message['body'];

            // Generate body from template
            if($message['use_template'] == 1){
                $tpl = new Template($body, $message['language_code']);
                // Adding some useful params
                $params['EMAIL_SUBJECT'] = $message['subject'];
                $params['EMAIL_DATE'] = $message['queue_date'];

                $body = $tpl->render($params);
            }else{
                // Replace body params
                $tplTemp = new Template('');
                $tplTemp->setTemplate($body);
                $body = $tplTemp->render($params);
            }

            $mailer->Subject = $message['subject'];
            $mailer->Body = $body;

            if(!empty(self::$OutputDir)){
                $mailer->preSend();
                file_put_contents(self::$OutputDir . $message['queue_id'].'.eml', $mailer->getSentMIMEMessage());
                
                self::DeleteQueue(intval($emailStatus['queue_id']));
                $sentCount++;
            }else{
                try{
                    // Limit sending rate per second to prevent blacklisting and off load the server 
                    if(self::$SendRate > 0 && $sentCount > 0 && $sentCount % self::$SendRate == 0){
                        sleep(1);
                    }

                    $isSent = $mailer->send();
                    
                    if($isSent){
                        self::DeleteQueue(intval($emailStatus['queue_id']));
                        $sentCount++;
                    }else{
                        $emailStatus['status'] = 'Failed';
                        $emailStatus['remarks'] = $mailer->ErrorInfo;
    
                        self::UpdateEmailStatus($emailStatus);
                    }
                }
                catch(Exception $ex){
                    $emailStatus['status'] = 'Failed';
                    $emailStatus['remarks'] = $ex->getMessage();

                    self::UpdateEmailStatus($emailStatus);
                }
            }
            
            $mailer->clearAllRecipients();
            
            $sendStatus[] = $emailStatus;
        }

        $mailer->smtpClose();

        self::UpdateProviderUsageCounters(intval($provider['provider_id']), $sentCount);
        
        DB::commit();

        return $sendStatus;
    }

    public static function DirectSend($to, $subject, $body, $params = [], $useTemplate = 0, $languageCode = '', $queueID = null){
        // Construct PHPMailer with exception handling
        static $mailer = null;
        static $sentCount = 0;

        if(is_null($mailer)){
            $mailer = new PHPMailer(true);
        }
        
        self::SetupMailer($mailer);

        $emlParts = self::ParseAddress($to);

        if(empty($emlParts)){
            return [
                'status' => 'error',
                'remark' => 'Bad email format'
            ];
        }

        $mailer->addAddress($emlParts['email'], $emlParts['name']);

        // Generate body from template
        if($useTemplate == 1){
            $tpl = new Template($body, $languageCode);
            // Adding some useful params
            $params['EMAIL_SUBJECT'] = $subject;
            $params['EMAIL_DATE'] = date('Y-m-d H:i:s');

            $body = $tpl->render($params);
        }else{
            // Replace body params
            $tplTemp = new Template('');
            $tplTemp->setTemplate($body);
            $body = $tplTemp->render($params);
        }

        $mailer->Subject = $subject;
        $mailer->Body = $body;

        if(!empty(self::$OutputDir)){
            $mailer->preSend();
            file_put_contents(self::$OutputDir . ($queueID??intval(microtime(true))).'.eml', $mailer->getSentMIMEMessage());
        }else{
            try{
                // Limit sending rate per second to prevent blacklisting and off load the server 
                if(self::$SendRate > 0 && $sentCount > 0 && $sentCount % self::$SendRate == 0){
                    sleep(1);
                }

                $isSent = $mailer->send();
                
                if($isSent){
                    $sentCount++;
                }else{
                    $emailStatus['status'] = 'error';
                    $emailStatus['remarks'] = $mailer->ErrorInfo;

                    return [
                        'status' => 'error',
                        'remarks' => $mailer->ErrorInfo
                    ];
                }
            }
            catch(Exception $ex){
                return [
                    'status' => 'error',
                    'remarks' => $ex->getMessage()
                ];
            }
        }
        
        $mailer->clearAllRecipients();
        $mailer->smtpClose();

        return [
            'status' => 'success',
            'remarks' => 'Message sent'
        ];
    }

    private static function SetupMailer(PHPMailer $mailer, array $provider = []): PHPMailer
    {
        $mailer->Host = $provider['smtp_host']??self::$ProviderDefault['smtp_host'];
        $mailer->Port = $provider['smtp_port']??self::$ProviderDefault['smtp_port'];
        $mailer->Username = $provider['username']??self::$ProviderDefault['username'];
        $mailer->Password = $provider['password']??self::$ProviderDefault['password'];
        
        // Set from header, fallback to current email username
        $from = $provider['send_from']??self::$ProviderDefault['send_from'];
        if(empty($from)){
            $from = $provider['username'];
        }

        $emlParts = self::ParseAddress($from);
        $mailer->setFrom($emlParts['email'], $emlParts['name']);
        
        $mailer->XMailer = 'MailService / PHPMailer ' . $mailer::VERSION;
        $mailer->isSMTP();
        $mailer->SMTPDebug = 0;
        $mailer->CharSet = 'UTF-8';
        $mailer->SMTPKeepAlive = true; 
        $mailer->isHTML(true); 
        $mailer->SMTPAuth = self::$SMTPAuth;
        $mailer->SMTPSecure = self::$SMTPSecure;

        return $mailer;
    }

    private static function UpdateEmailStatus(array $params){
        static $mailQueue = null;

        if(is_null($mailQueue)){
            $mailQueue = new MailQueue();
        }

        $mailQueue->Update([
            'queue_id' => $params['queue_id'],
            'status' => $params['status'],
            'remarks' => $params['remarks']
        ]);
    }

    private static function DeleteQueue(int $queueID){
        static $mailQueue = null;

        if(is_null($mailQueue)){
            $mailQueue = new MailQueue();
        }

        $mailQueue->Delete([
            'queue_id' => $queueID
        ]);
    }

    private static function UpdateProviderUsageCounters(int $providerID, int $sentCount){
        static $mailProvider = null;

        if(is_null($mailProvider)){
            $mailProvider = new MailProvider();
        }

        $mailProvider->UpdateUsageCounters([
            'provider_id' => $providerID,
            'sent_count' => $sentCount
        ]);
    }

}