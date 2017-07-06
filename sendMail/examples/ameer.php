<?php 
require '../PHPMailerAutoload.php';

$mail = new PHPMailer();
$mail->IsSMTP();
$mail->CharSet = 'UTF-8';

$mail->Host       = "smtp.gmail.com"; // SMTP server example
$mail->SMTPDebug  = 1;                     // enables SMTP debug information (for testing)
$mail->SMTPAuth   = true;                  // enable SMTP authentication
$mail->SMTPSecure = "STARTTLS";
$mail->Port       = 587;                    // set the SMTP port for the GMAIL server
$mail->Username   = "ameer.atteya@babeleye.com"; // SMTP account username example
$mail->Password   = "helloameerinbe2015";        // SMTP account password example

//$mail->isSendmail();
$mail->setFrom('ameer.atteya@babeleye.com', 'Amir Samir');
//$mail->addAddress('hala.grant@babeleye.com', 'Hala Grant');
$mail->addAddress('ameer.atteya@babeleye.com', 'Amir Samir');
$mail->AddCC('ameer.atteya@babeleye.com', 'Amir Samir');
$mail->Subject = '7elwa el le3ba de :D :D';
//$mail->msgHTML(file_get_contents('http://www.google.com'));
$mail->msgHTML("You Got an Email From Ameer :)");
$mail->AltBody = 'This is a plain-text message body';
//send the message, check for errors
if (!$mail->send()) {
    echo "Mailer Error: " . $mail->ErrorInfo;
} else {
    echo "Message sent!";
}
?>