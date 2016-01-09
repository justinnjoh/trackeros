<?php
  // mailer class - may define several methods for sending mail here

class Mailer_Lib {

  public function __construct() {

  }

  public function send_mail ($to = null, $subject = null, $message = null, $from = null, $reply_to = null) {
    // sends ordinary text mail using built-in php 'mail' function
    // email addresses are ASSUMED validated already

    $ret = false; // presume failure

    $reply_to || $reply_to = $from; // default reply-to to From

    $headers = array();
    $from && $headers[] = "From: $from";
    $reply_to && $headers[] = "Reply-To: $reply_to";

    if ( $to ) {
      $subject || $subject = "";
      $ret = mail($to, $subject, wordwrap($message, 70, "\r\n"), implode("\r\n", $headers));
    }

    return ($ret);
  }

  public function swiftmail ($config = array(), $to = null, $subject = null, $message = null, $from = null, $from_name = null, $reply_to = null, $cc = null) {
    // send mail in both html and text formats
    // the config varialble holds mail server details
    // cc - an array like array('email1', 'email2' => 'name2', 'email3', ...)

    $lib = SITE_ROOT . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR . "vendor" . DIRECTORY_SEPARATOR . "swiftmailer" . DIRECTORY_SEPARATOR . "swift_required.php";

    $return = false;

    // load the library
    if ( is_readable($lib) ) {

      include_once($lib);

      is_null($from_name) && $from_name = "";

      $msg = Swift_Message::newInstance($subject);
      if ( !is_null($msg) ) {

        if ( strlen($from_name) > 0 ) {
          $msg->setFrom(array($from => $from_name));
        }
        else {
          $msg->setFrom(array($from));
        }

        $msg->setTo(array($to));

        // cc ?
        is_null($cc) && $cc = array();
        count($cc) > 0 && $msg->setCc($cc);

        $msg->setBody(nl2br($message), 'text/html');
        $msg->addPart(nl2br(strip_tags($message)), 'text/plain');

        $transport = Swift_SmtpTransport::newInstance($config['host'], $config['port']);

        if ( !is_null($transport) ) {
          isset($config['user_name']) && $transport->setUsername($config['user_name']);
          isset($config['password']) && $transport->setPassword($config['password']);

          $mailer = Swift_Mailer::newInstance($transport); 

          if (!is_null($mailer) ) {
            $res = $mailer->send($msg);

            $res > 0 && $return = true;
          }
        }
      } // msg not null

    }

    return ($return);
  }





}

?>
