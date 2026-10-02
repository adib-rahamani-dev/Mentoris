<?php
declare(strict_types=1);
namespace App\Services;

final class EmailService
{
    public function send(string $to, string $subject, string $message): bool
    {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) return false;
        if (env('APP_ENV','production') !== 'production' && env('MAIL_MAILER','log') === 'log') {
            error_log('Mentoris email: local delivery suppressed'); return false;
        }
        foreach (['Exception','SMTP','PHPMailer'] as $file) require_once BASE_PATH.'/app/Support/PHPMailer/'.$file.'.php';
        try {
            $mail=new \PHPMailer\PHPMailer\PHPMailer(true);
            $host=trim((string)env('MAIL_HOST',''));
            if ($host!=='') {
                $mail->isSMTP(); $mail->Host=$host; $mail->Port=(int)env('MAIL_PORT',587);
                $mail->Username=(string)env('MAIL_USERNAME',''); $mail->Password=(string)env('MAIL_PASSWORD','');
                $mail->SMTPAuth=$mail->Username!=='';
                $mail->SMTPSecure=(string)env('MAIL_ENCRYPTION','tls'); $mail->Timeout=5; $mail->Timelimit=10;
            } else $mail->isMail();
            $from=(string)env('MAIL_FROM_ADDRESS','no-reply@mentorisacademy.com');
            $mail->CharSet='UTF-8'; $mail->setFrom($from,(string)env('MAIL_FROM_NAME','Mentoris'));
            $mail->addAddress($to); $mail->Subject=$subject;
            $mail->isHTML(true);
            $mail->Body='<html lang="fa" dir="rtl"><body style="font-family:Tahoma,sans-serif;line-height:2;background:#f4f3ed;color:#173e37;padding:24px"><h2>منتوریس</h2><h3>'.htmlspecialchars($subject,ENT_QUOTES,'UTF-8').'</h3><p>'.nl2br(htmlspecialchars($message,ENT_QUOTES,'UTF-8')).'</p></body></html>';
            $mail->AltBody=$subject."\n\n".$message;
            return $mail->send();
        } catch (\Throwable) { error_log('Mentoris email delivery failed; check mail transport configuration.'); return false; }
    }
}
