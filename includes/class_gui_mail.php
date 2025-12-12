<?php
require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/cau_hinh.php';

class Mailer {
	// send mail using configured SMTP constants in includes/cau_hinh.php
	// returns array with success boolean and message
	public static function send($toEmail, $toName, $subject, $bodyHtml, $altBody = null){
		$mail = new PHPMailer(true);
		try {
			// SMTP configuration
			$mail->isSMTP();
			$mail->Host = SMTP_HOST;
			$mail->SMTPAuth = true;
			$mail->Username = SMTP_USER;
			$mail->Password = SMTP_PASS;
			$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
			$mail->Port = SMTP_PORT;

			$mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
			$mail->addAddress($toEmail, $toName);
			$mail->isHTML(true);
			$mail->Subject = $subject;
			$mail->Body = $bodyHtml;
			if($altBody) $mail->AltBody = $altBody;

			$mail->send();
			return ['success' => true, 'message' => 'Message sent'];
		} catch (Exception $e) {
			return ['success' => false, 'message' => $mail->ErrorInfo ?: $e->getMessage()];
		}
	}
}
