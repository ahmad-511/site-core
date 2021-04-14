<?php
namespace App\Core;

use App\Core\Template;
use App\Core\Logger;

class Mailer {
    private static bool $DebugMode = false;

    public static function setDebugMode(bool $debugMode){
        self::$DebugMode = $debugMode;
    }

    /**
     * Send message using PHP built in mailer
     * @param string $to To address
     * @param string $subject message subject
     * @param string $body message body
     * @param string $from message from header
     * @param string $replyTo message reply-to header
     * @param bool $isHtml whether or not the message is html formatted
     * @return bool True if message is accepted by the mailer (not necessarilly delivered), false otherwise
     */
    public static function send(string $to, string $subject, string $body, string $from = '', string $replyTo = '', bool $isHtml = true)
    {
        // Mime needs 70 character per line (this sometimes breaks html emails)
        if(!$isHtml){
            $body = wordwrap($body,70, "\n", true);
        }

        // A new line character followed by a period represents the end of the message, so we add another period to it
        $body = str_replace("\n.", "\n..", $body);
    
        // Adding message header
        $headers = [];

        // Set FROM header, fallback to one declared in php.ini file
        if (empty($from)) {
            $from = ini_get('sendmail_from');
        }
    
        $headers[] = "FROM: $from";
    
        // Set Reply-To header, fallback to FROM header
        if (empty($replyTo)) {
            $replyTo = $from;
        }
    
        $headers[] = "Reply-To: $replyTo";
    
        // Set necessary headers for html email body
        if ($isHtml) {
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-type:text/html;charset=UTF-8";
        }
    
        // Log the message to a file instead of sending it when in debug mode
        if(self::$DebugMode){
            Logger::log(implode("\r\n", array(
                implode("\r\n", $headers),
                "To: " . $to,
                "Subject: " . $subject,
                "\r\n" . $body)));
            
            return true;
        }

        // Send mail
        $isAccepted = @mail(
            $to,
            $subject,
            $body,
            implode("\r\n", $headers)
        );
    
        return $isAccepted;
    }
    
    /**
     * Send message using specified template
     * @param string $to To address
     * @param string $subject message subject
     * @param string $template Template name to be used for the message body
     * @param array $params Array of parameters used in the template
     * @param string $from message from header
     * @param string $replyTo message reply-to header
     * @param bool $isHtml whether or not the message is html formatted
     * @return bool True if message is accepted by the mailer (not necessarilly delivered), false otherwise
     */
    public static function sendTemplate(string $to, string $subject, string $template, array $params = [], string $from = '', string $replyTo = '', bool $isHtml = true)
    {
        $tpl = new Template($template);
        if(!$tpl){
            return false;
        }
    
        // Add some automatic template params maybe useful inside the message body
        $params['email_subject'] = $subject;
        $params['email_date'] = date('Y-m-d H:i');
    
        $body = $tpl->render($params);

        if ($body === null) {
            return false;
        }
    
        return self::send($to, $subject, $body, $from, $replyTo, $isHtml);
    }
}
?>