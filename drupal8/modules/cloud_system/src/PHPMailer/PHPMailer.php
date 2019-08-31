<?php

namespace Drupal\cloud_system\PHPMailer;

use Drupal\cloud_system\Plugin\Exception\PHPMailerException;

/**
 * Class PHPMailer.
 *
 * PHPMailer - PHP email transport class.
 * NOTE: Requires PHP version 5 or later.
 *
 * @package Drupal\cloud_system\PHPMailer
 */
class PHPMailer {
  /**
   * Email priority (1 = High, 3 = Normal, 5 = low).
   *
   * @var int
   */
  public $Priority = 3;

  /**
   * Sets the CharSet of the message.
   *
   * @var string
   */
  public $CharSet = 'iso-8859-1';

  /**
   * Sets the Content-type of the message.
   *
   * @var string
   */
  public $ContentType = 'text/plain';

  /**
   * Sets the Encoding of the message.
   *
   * Options for this are "8bit", "7bit", "binary", "base64",
   * and "quoted-printable".
   *
   * @var string
   */
  public $Encoding = '8bit';

  /**
   * Holds the most recent mailer error message.
   *
   * @var string
   */
  public $ErrorInfo = '';

  /**
   * Sets the From email address for the message.
   *
   * @var string
   */
  public $From = 'root@localhost';

  /**
   * Sets the From name of the message.
   *
   * @var string
   */
  public $FromName = 'Root User';

  /**
   * Sets the Sender email (Return-Path) of the message.
   *
   * If not empty, will be sent via -f to sendmail or as
   * 'MAIL FROM' in smtp mode.
   *
   * @var string
   */
  public $Sender = '';

  /**
   * Sets the Subject of the message.
   *
   * @var string
   */
  public $Subject = '';

  /**
   * Sets the Body of the message.
   *
   * This can be either an HTML or text body.
   * If HTML then run isHtml(TRUE).
   *
   * @var string
   */
  public $Body = '';

  /**
   * Sets the text-only body of the message.
   *
   * This automatically sets the email to multipart/alternative.
   * This body can be read by mail clients that do not have
   * HTML email capability such as mutt.
   * Clients that can read HTML will view the normal Body.
   *
   * @var string
   */
  public $AltBody = '';

  /**
   * Sets word wrap on the body of the message to a given number of characters.
   *
   * @var int
   */
  public $WordWrap = 0;

  /**
   * Method to send mail: ("mail", "sendmail", or "smtp").
   *
   * @var string
   */
  public $Mailer = 'mail';

  /**
   * Sets the path of the sendmail program.
   *
   * @var string
   */
  public $Sendmail = '/usr/sbin/sendmail';

  /**
   * Path to PHPMailer plugins.
   *
   * Useful if the SMTP class is in a different
   * directory than the PHP include path.
   *
   * @var string
   */
  public $PluginDir = '';

  /**
   * Sets the email address that a reading confirmation will be sent.
   *
   * @var string
   */
  public $ConfirmReadingTo = '';

  /**
   * Sets the hostname.
   *
   * Use in Message-Id and Received headers and as default HELO string.
   * If empty, the value returned by SERVER_NAME is used
   * or 'localhost.localdomain'.
   *
   * @var string
   */
  public $Hostname = '';

  /**
   * Sets the message ID to be used in the Message-Id header.
   *
   * If empty, a unique id will be generated.
   *
   * @var string
   */
  public $MessageID = '';

  /**
   * Sets the SMTP hosts.
   *
   * All hosts must be separated by a semicolon.
   * You can also specify a different port
   * for each host by using this format: [hostname:port]
   * (e.g. "smtp1.example.com:25;smtp2.example.com").
   * Hosts will be tried in order.
   *
   * @var string
   */
  public $Host = 'localhost';

  /**
   * Sets the default SMTP server port.
   *
   * @var int
   */
  public $Port = 25;

  /**
   * Sets the SMTP HELO of the message (Default is $Hostname).
   *
   * @var string
   */
  public $Helo = '';

  /**
   * Sets connection prefix.
   *
   * Options are "", "ssl" or "tls".
   *
   * @var string
   */
  public $SMTPSecure = '';

  /**
   * Sets SMTP authentication.
   *
   * Utilizes the Username and Password variables.
   *
   * @var bool
   */
  public $SMTPAuth = FALSE;

  /**
   * Sets SMTP username.
   *
   * @var string
   */
  public $Username = '';

  /**
   * Sets SMTP password.
   *
   * @var string
   */
  public $Password = '';

  /**
   * Sets the SMTP server timeout in seconds.
   *
   * This function will not work with the win32 version.
   *
   * @var int
   */
  public $Timeout = 10;

  /**
   * Sets SMTP class debugging on or off.
   *
   * @var bool
   */
  public $SMTPDebug = FALSE;

  /**
   * Prevents the SMTP connection from being closed after each mail sending.
   *
   * If this is set to TRUE then to close the connection
   * requires an explicit call to smtpClose().
   *
   * @var bool
   */
  public $SMTPKeepAlive = FALSE;

  /**
   * Provides the ability to have the TO field process individual emails.
   *
   * Instead of sending to entire TO addresses.
   *
   * @var bool
   */
  public $SingleTo = FALSE;

  /**
   * If SingleTo is TRUE, this provides the array to hold the email addresses.
   *
   * @var bool
   */
  public $SingleToArray = [];

  /**
   * Provides the ability to change the line ending.
   *
   * @var string
   */
  public $LE = "\n";

  /**
   * Used with DKIM DNS Resource Record.
   *
   * @var string
   */
  public $dkimSelector = 'phpmailer';

  /**
   * Used with DKIM DNS Resource Record optional.
   *
   * In format of email address 'you@yourdomain.com'.
   *
   * @var string
   */
  public $dkimIdentity = '';

  /**
   * Used with DKIM DNS Resource Record optional.
   *
   * In format of email address 'you@yourdomain.com'.
   *
   * @var string
   */
  public $dkimDomain = '';

  /**
   * Used with DKIM DNS Resource Record optional.
   *
   * In format of email address 'you@yourdomain.com'.
   *
   * @var string
   */
  public $DKIMPrivate = '';

  /**
   * Callback Action function name.
   *
   * The function that handles the result of the send email action.
   * Parameters:
   *   bool    $result        result of the send action
   *   string  $to            email address of the recipient
   *   string  $cc            cc email addresses
   *   string  $bcc           bcc email addresses
   *   string  $subject       the subject
   *   string  $body          the email body.
   *
   * @var string
   */
  public $actionFunction = '';

  /**
   * Sets the PHPMailer Version number.
   *
   * @var string
   */
  public $Version = '5.1';

  private $smtp = NULL;
  private $to = [];
  private $cc = [];
  private $bcc = [];
  private $ReplyTo = [];
  private $allRecipients = [];
  private $attachment = [];
  private $CustomHeader = [];
  private $messageType = '';
  private $boundary = [];
  private $errorCount = 0;
  private $signCertFile = "";
  private $signKeyFile = "";
  private $signKeyPass = "";
  private $exceptions = FALSE;

  // Message only, continue processing.
  const STOP_MESSAGE = 0;
  // Message?, likely ok to continue processing.
  const STOP_CONTINUE = 1;
  // Message, plus full stop, critical error reached.
  const STOP_CRITICAL = 2;

  /**
   * Constructor.
   *
   * @param bool $exceptions
   *   Should we throw external exceptions?
   */
  public function __construct($exceptions = FALSE) {
    $this->exceptions = FALSE;
    //$this->exceptions = ($exceptions == TRUE);
  }

  /**
   * Sets message type to HTML.
   *
   * @param bool $isHtml
   *   True if html, otherwise False.
   */
  public function isHtml($isHtml = TRUE) {
    if ($isHtml) {
      $this->ContentType = $this->is_html = 'text/html';
    }
    else {
      $this->ContentType = $this->is_html = 'text/plain';
    }
  }

  /**
   * Sets Mailer to send message using SMTP.
   */
  public function isSmtp() {
    $this->Mailer = 'smtp';
  }

  /**
   * Sets Mailer to send message using PHP mail() function.
   */
  public function isMail() {
    $this->Mailer = 'mail';
  }

  /**
   * Sets Mailer to send message using the $Sendmail program.
   */
  public function isSendmail() {
    if (!stristr(ini_get('sendmail_path'), 'sendmail')) {
      $this->Sendmail = '/var/qmail/bin/sendmail';
    }
    $this->Mailer = 'sendmail';
  }

  /**
   * Sets Mailer to send message using the qmail MTA.
   */
  public function isQmail() {
    if (stristr(ini_get('sendmail_path'), 'qmail')) {
      $this->Sendmail = '/var/qmail/bin/sendmail';
    }
    $this->Mailer = 'sendmail';
  }

  /**
   * Adds a "To" address.
   *
   * @param string $address
   *   The email address to send to.
   * @param string $name
   *   The name to send to.
   *
   * @return bool
   *   TRUE on success, FALSE if address already used.
   */
  public function addAddress($address, $name = '') {
    return $this->addAnAddress('to', $address, $name);
  }

  /**
   * Adds a "Cc" address.
   *
   * Note: this function works with the SMTP mailer on win32,
   * not with the "mail" mailer.
   *
   * @param string $address
   *   The email address to send to.
   * @param string $name
   *   The name to send to.
   *
   * @return bool
   *   TRUE on success, FALSE if address already used.
   */
  public function addCc($address, $name = '') {
    return $this->addAnAddress('cc', $address, $name);
  }

  /**
   * Adds a "Bcc" address.
   *
   * Note: this function works with the SMTP mailer on win32,
   * not with the "mail" mailer.
   *
   * @param string $address
   *   The email address to send to.
   * @param string $name
   *   The name to send to.
   *
   * @return bool
   *   TRUE on success, FALSE if address already used.
   */
  public function addBcc($address, $name = '') {
    return $this->addAnAddress('bcc', $address, $name);
  }

  /**
   * Adds a "Reply-to" address.
   *
   * @param string $address
   *   The email address to send to.
   * @param string $name
   *   The name to send to.
   *
   * @return bool
   *   TRUE on success, otherwise False.
   */
  public function addReplyTo($address, $name = '') {
    return $this->addAnAddress('ReplyTo', $address, $name);
  }

  /**
   * Adds an address to one of the recipient arrays.
   *
   * Addresses that have been added already return FALSE,
   * but do not throw exceptions.
   *
   * @param string $kind
   *   One of 'to', 'cc', 'bcc', 'ReplyTo'.
   * @param string $address
   *   The email address to send to.
   * @param string $name
   *   The name to send to.
   *
   * @return bool
   *   TRUE on success, FALSE if address already used or invalid in some way.
   */
  private function addAnAddress($kind, $address, $name = '') {
    if (!preg_match('/^(to|cc|bcc|ReplyTo)$/', $kind)) {
      echo 'Invalid recipient array: ' . kind;
      return FALSE;
    }
    $address = trim($address);
    // Strip breaks and trim.
    $name = trim(preg_replace('/[\r\n]+/', '', $name));
    $errorMessage = 'Invalid address' . ': ' . $address;
    if (!self::validateAddress($address)) {
      $this->setError($errorMessage);
      if ($this->exceptions) {
        throw new PHPMailerException($errorMessage);
      }
      echo $errorMessage;
      return FALSE;
    }
    if ($kind != 'ReplyTo') {
      if (!isset($this->allRecipients[strtolower($address)])) {
        array_push($this->$kind, [$address, $name]);
        $this->allRecipients[strtolower($address)] = TRUE;
        return TRUE;
      }
    }
    else {
      if (!array_key_exists(strtolower($address), $this->ReplyTo)) {
        $this->ReplyTo[strtolower($address)] = [$address, $name];
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Set the From and FromName properties.
   *
   * @param string $address
   *   The email address to send to.
   * @param string $name
   *   The name to send to.
   * @param int $auto
   *   If ReplyTo or Sender is empty, auto add the param.
   *
   * @return bool
   *   True on success, otherwise False.
   */
  public function setFrom($address, $name = '', $auto = 1) {
    $address = trim($address);
    $name = trim(preg_replace('/[\r\n]+/', '', $name));
    $errorMessage = 'Invalid address' . ': ' . $address;
    if (!self::validateAddress($address)) {
      $this->setError($errorMessage);
      if ($this->exceptions) {
        throw new PHPMailerException($errorMessage);
      }
      echo $errorMessage;
      return FALSE;
    }
    $this->From = $address;
    $this->FromName = $name;
    if ($auto) {
      if (empty($this->ReplyTo)) {
        $this->addAnAddress('ReplyTo', $address, $name);
      }
      if (empty($this->Sender)) {
        $this->Sender = $address;
      }
    }
    return TRUE;
  }

  /**
   * Check that a string looks roughly like an email address.
   *
   * Should static so it can be used without instantiation.
   * Tries to use PHP built-in validator in the filter extension
   * (from PHP 5.2), falls back to a reasonably competent regex validator
   * Conforms approximately to RFC2822.
   *
   * @param string $address
   *   The email address to check.
   *
   * @return bool
   *   True if an email address, otherwise False.
   * @link http://www.hexillion.com/samples/#Regex Original pattern found here
   */
  public static function validateAddress($address) {
    if (function_exists('filter_var')) {
      // Introduced in PHP 5.2.
      if (filter_var($address, FILTER_VALIDATE_EMAIL) === FALSE) {
        return FALSE;
      }
      else {
        return TRUE;
      }
    }
    else {
      return preg_match('/^(?:[\w\!\#\$\%\&\'\*\+\-\/\=\?\^\`\{\|\}\~]+\.)*[\w\!\#\$\%\&\'\*\+\-\/\=\?\^\`\{\|\}\~]+@(?:(?:(?:[a-zA-Z0-9_](?:[a-zA-Z0-9_\-](?!\.)){0,61}[a-zA-Z0-9_-]?\.)+[a-zA-Z0-9_](?:[a-zA-Z0-9_\-](?!$)){0,61}[a-zA-Z0-9_]?)|(?:\[(?:(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\.){3}(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\]))$/', $address);
    }
  }

  /**
   * Creates message and assigns Mailer.
   *
   * If the message is not sent successfully then it returns FALSE.
   * Use the ErrorInfo variable to view description of the error.
   */
  public function send() {
    try {
      if ((count($this->to) + count($this->cc) + count($this->bcc)) < 1) {
        throw new PHPMailerException(t('You must provide at least one recipient email address.'), self::STOP_CRITICAL);
      }

      // Set whether the message is multipart/alternative.
      if (!empty($this->AltBody)) {
        $this->ContentType = 'multipart/alternative';
      }

      // Reset errors.
      $this->errorCount = 0;
      $this->setMessageType();
      $header = $this->createHeader();
      $body = $this->createBody();

      if (empty($this->Body)) {
        throw new PHPMailerException(t('Message body empty'), self::STOP_CRITICAL);
      }

      // Digitally sign with DKIM if enabled.
      if ($this->dkimDomain && $this->DKIMPrivate) {
        $header_dkim = $this->dkimAdd($header, $this->Subject, $body);
        $header = str_replace("\r\n", "\n", $header_dkim) . $header;
      }

      // Choose the mailer and send through it.
      switch ($this->Mailer) {
        case 'sendmail':
          return $this->sendmailSend($header, $body);

        case 'smtp':
          return $this->smtpSend($header, $body);

        default:
          return $this->mailSend($header, $body);
      }

    } catch (PHPMailerException $e) {
      $this->setError($e->getMessage());
      if ($this->exceptions) {
        throw $e;
      }
      echo $e->getMessage() . "\n";
      return FALSE;
    }
  }

  /**
   * Sends mail using the $Sendmail program.
   *
   * @param string $header
   *   The message headers.
   * @param string $body
   *   The message body.
   *
   * @return bool
   *   True on success, otherwise False.
   */
  protected function sendmailSend($header, $body) {
    if ($this->Sender != '') {
      $sendmail = sprintf("%s -oi -f %s -t", escapeshellcmd($this->Sendmail), escapeshellarg($this->Sender));
    }
    else {
      $sendmail = sprintf("%s -oi -t", escapeshellcmd($this->Sendmail));
    }
    if ($this->SingleTo === TRUE) {
      foreach ($this->SingleToArray as $key => $val) {
        if (!@$mail = popen($sendmail, 'w')) {
          throw new PHPMailerException(t('Could not execute: !smail', ['!smail' => $this->Sendmail]), self::STOP_CRITICAL);
        }
        fwrite($mail, "To: " . $val . "\n");
        fwrite($mail, $header);
        fwrite($mail, $body);
        $result = pclose($mail);
        // Implement call back function if it exists.
        $isSent = ($result == 0) ? 1 : 0;
        $this->doCallback($isSent, $val, $this->cc, $this->bcc, $this->Subject, $body);
        if ($result != 0) {
          throw new PHPMailerException(t('Could not execute: !smail', ['!smail' => $this->Sendmail]), self::STOP_CRITICAL);
        }
      }
    }
    else {
      if (!@$mail = popen($sendmail, 'w')) {
        throw new PHPMailerException(t('Could not execute: !smail', ['!smail' => $this->Sendmail]), self::STOP_CRITICAL);
      }
      fwrite($mail, $header);
      fwrite($mail, $body);
      $result = pclose($mail);
      // Implement call back function if it exists.
      $isSent = ($result == 0) ? 1 : 0;
      $this->doCallback($isSent, $this->to, $this->cc, $this->bcc, $this->Subject, $body);
      if ($result != 0) {
        throw new PHPMailerException(t('Could not execute: !smail', ['!smail' => $this->Sendmail]), self::STOP_CRITICAL);
      }
    }
    return TRUE;
  }

  /**
   * Sends mail using the PHP mail() function.
   *
   * @param string $header
   *   The message headers.
   * @param string $body
   *   The message body.
   *
   * @return bool
   *   True on success, otherwise False.
   */
  protected function mailSend($header, $body) {
    $toArr = [];
    foreach ($this->to as $t) {
      $toArr[] = $this->addrFormat($t);
    }
    $to = implode(', ', $toArr);

    $params = sprintf("-oi -f %s", $this->Sender);
    if ($this->Sender != '' && strlen(ini_get('safe_mode')) < 1) {
      $old_from = ini_get('sendmail_from');
      ini_set('sendmail_from', $this->Sender);
      if ($this->SingleTo === TRUE && count($toArr) > 1) {
        foreach ($toArr as $key => $val) {
          $rt = @mail($val, $this->encodeHeader($this->secureHeader($this->Subject)), $body, $header, $params);
          // Implement call back function if it exists.
          $isSent = ($rt == 1) ? 1 : 0;
          $this->doCallback($isSent, $val, $this->cc, $this->bcc, $this->Subject, $body);
        }
      }
      else {
        $rt = @mail($to, $this->encodeHeader($this->secureHeader($this->Subject)), $body, $header, $params);
        // Implement call back function if it exists.
        $isSent = ($rt == 1) ? 1 : 0;
        $this->doCallback($isSent, $to, $this->cc, $this->bcc, $this->Subject, $body);
      }
    }
    else {
      if ($this->SingleTo === TRUE && count($toArr) > 1) {
        foreach ($toArr as $key => $val) {
          $rt = @mail($val, $this->encodeHeader($this->secureHeader($this->Subject)), $body, $header, $params);
          // Implement call back function if it exists.
          $isSent = ($rt == 1) ? 1 : 0;
          $this->doCallback($isSent, $val, $this->cc, $this->bcc, $this->Subject, $body);
        }
      }
      else {
        $rt = @mail($to, $this->encodeHeader($this->secureHeader($this->Subject)), $body, $header);
        // Implement call back function if it exists.
        $isSent = ($rt == 1) ? 1 : 0;
        $this->doCallback($isSent, $to, $this->cc, $this->bcc, $this->Subject, $body);
      }
    }
    if (isset($old_from)) {
      ini_set('sendmail_from', $old_from);
    }
    if (!$rt) {
      throw new PHPMailerException(t('Could not instantiate mail function.'), self::STOP_CRITICAL);
    }
    return TRUE;
  }

  /**
   * Sends mail via SMTP using PhpSMTP.
   *
   * @param string $header
   *   The message headers.
   * @param string $body
   *   The message body.
   *
   * @return bool
   *   FALSE if there is a bad MAIL FROM, RCPT, or DATA input.
   */
  protected function smtpSend($header, $body) {
    $bad_rcpt = [];

    if (!$this->smtpConnect()) {
      return FALSE;
      //throw new PHPMailerException(t('SMTP Error: Could not connect to SMTP host.'), self::STOP_CRITICAL);
    }
    $smtp_from = ($this->Sender == '') ? $this->From : $this->Sender;
    if (!$this->smtp->mail($smtp_from)) {
      return FALSE;
      //throw new PHPMailerException(t('The following From address failed: !from', ['!from' => $smtp_from]), self::STOP_CRITICAL);
    }

    // Attempt to send attach all recipients.
    foreach ($this->to as $to) {
      if (!$this->smtp->recipient($to[0])) {
        $bad_rcpt[] = $to[0];
        // Implement call back function if it exists.
        $isSent = 0;
        $this->doCallback($isSent, $to[0], '', '', $this->Subject, $body);
      }
      else {
        // Implement call back function if it exists.
        $isSent = 1;
        $this->doCallback($isSent, $to[0], '', '', $this->Subject, $body);
      }
    }
    foreach ($this->cc as $cc) {
      if (!$this->smtp->recipient($cc[0])) {
        $bad_rcpt[] = $cc[0];
        // Implement call back function if it exists.
        $isSent = 0;
        $this->doCallback($isSent, '', $cc[0], '', $this->Subject, $body);
      }
      else {
        // Implement call back function if it exists.
        $isSent = 1;
        $this->doCallback($isSent, '', $cc[0], '', $this->Subject, $body);
      }
    }
    foreach ($this->bcc as $bcc) {
      if (!$this->smtp->recipient($bcc[0])) {
        $bad_rcpt[] = $bcc[0];
        // Implement call back function if it exists.
        $isSent = 0;
        $this->doCallback($isSent, '', '', $bcc[0], $this->Subject, $body);
      }
      else {
        // Implement call back function if it exists.
        $isSent = 1;
        $this->doCallback($isSent, '', '', $bcc[0], $this->Subject, $body);
      }
    }

    // Create error message for any bad addresses.
    if (count($bad_rcpt) > 0) {
      $badaddresses = implode(', ', $bad_rcpt);
      throw new PHPMailerException(t('SMTP Error: The following recipients failed: @bad', ['@bad' => $badaddresses]));
    }
    if (!$this->smtp->data($header . $body)) {
      throw new PHPMailerException(t('SMTP Error: Data not accepted.'), self::STOP_CRITICAL);
    }
    if ($this->SMTPKeepAlive == TRUE) {
      $this->smtp->reset();
    }
    return TRUE;
  }

  /**
   * Initiates a connection to an SMTP server.
   *
   * @return bool
   *   FALSE if the operation failed.
   */
  public function smtpConnect() {
    if (is_null($this->smtp)) {
      $this->smtp = new SMTP();
    }

    $this->smtp->do_debug = $this->SMTPDebug;
    $hosts = explode(';', $this->Host);
    $index = 0;
    $connection = $this->smtp->connected();

    // Retry while there is no connection.
    try {
      while ($index < count($hosts) && !$connection) {
        $hostinfo = [];
        if (preg_match('/^(.+):([0-9]+)$/', $hosts[$index], $hostinfo)) {
          $host = $hostinfo[1];
          $port = $hostinfo[2];
        }
        else {
          $host = $hosts[$index];
          $port = $this->Port;
        }

        $tls = ($this->SMTPSecure == 'tls');
        $ssl = ($this->SMTPSecure == 'ssl');

        if ($this->smtp->connect(($ssl ? 'ssl://' : '') . $host, $port, $this->Timeout)) {

          $hello = ($this->Helo != '' ? $this->Helo : $this->serverHostname());
          $this->smtp->hello($hello);

          if ($tls) {
            if (!$this->smtp->startTls()) {
              throw new PHPMailerException(t('startTls not supported by server or could not initiate session.'));
            }

            // We must resend HELO after tls negotiation.
            $this->smtp->hello($hello);
          }

          $connection = TRUE;
          if ($this->SMTPAuth) {
            if (!$this->smtp->authenticate($this->Username, $this->Password)) {
              throw new PHPMailerException(t('SMTP Error: Could not authenticate.'));
            }
          }
        }
        $index++;
        if (!$connection) {
          throw new PHPMailerException(t('SMTP Error: Could not connect to SMTP host.'));
        }
      }
    } catch (PHPMailerException $e) {
      $this->smtp->reset();
      throw $e;
    }
    return TRUE;
  }

  /**
   * Closes the active SMTP session if one exists.
   */
  public function smtpClose() {
    if (!is_null($this->smtp)) {
      if ($this->smtp->connected()) {
        $this->smtp->quit();
        $this->smtp->close();
      }
    }
  }

  /**
   * Creates recipient headers.
   *
   * @return string
   *   Mailer addresses.
   */
  public function addrAppend($type, $addr) {
    $addr_str = $type . ': ';
    $addresses = [];
    foreach ($addr as $a) {
      $addresses[] = $this->addrFormat($a);
    }
    $addr_str .= implode(', ', $addresses);
    $addr_str .= $this->LE;

    return $addr_str;
  }

  /**
   * Formats an address correctly.
   *
   * @param string $addr
   *   The mailer address to send.
   *
   * @return string
   *   Mailer addresses.
   */
  public function addrFormat($addr) {
    if (empty($addr[1])) {
      return $this->secureHeader($addr[0]);
    }
    else {
      return $this->encodeHeader($this->secureHeader($addr[1]), 'phrase') . " <" . $this->secureHeader($addr[0]) . ">";
    }
  }

  /**
   * Wraps message for use with mailers.
   *
   * That do not automatically perform wrapping and for quoted-printable.
   * Original written by philippe.
   *
   * @param string $message
   *   The message to wrap.
   * @param int $length
   *   The line length to wrap to.
   * @param bool $qp_mode
   *   Whether to run in Quoted-Printable mode.
   *
   * @return string
   *   Mail message with wrapped.
   */
  public function wrapText($message, $length, $qp_mode = FALSE) {
    $soft_break = ($qp_mode) ? sprintf(" =%s", $this->LE) : $this->LE;
    // If utf-8 encoding is used, we will need to make sure we don't
    // split multibyte characters when we wrap.
    $is_utf8 = (strtolower($this->CharSet) == "utf-8");

    $message = $this->fixEol($message);
    if (substr($message, -1) == $this->LE) {
      $message = substr($message, 0, -1);
    }

    $line = explode($this->LE, $message);
    $message = '';
    for ($i = 0; $i < count($line); $i++) {
      $line_part = explode(' ', $line[$i]);
      $buf = '';
      for ($e = 0; $e < count($line_part); $e++) {
        $word = $line_part[$e];
        if ($qp_mode and (strlen($word) > $length)) {
          $space_left = $length - strlen($buf) - 1;
          if ($e != 0) {
            if ($space_left > 20) {
              $len = $space_left;
              if ($is_utf8) {
                $len = $this->utf8CharBoundary($word, $len);
              }
              elseif (substr($word, $len - 1, 1) == "=") {
                $len--;
              }
              elseif (substr($word, $len - 2, 1) == "=") {
                $len -= 2;
              }
              $part = substr($word, 0, $len);
              $word = substr($word, $len);
              $buf .= ' ' . $part;
              $message .= $buf . sprintf("=%s", $this->LE);
            }
            else {
              $message .= $buf . $soft_break;
            }
            $buf = '';
          }
          while (strlen($word) > 0) {
            $len = $length;
            if ($is_utf8) {
              $len = $this->utf8CharBoundary($word, $len);
            }
            elseif (substr($word, $len - 1, 1) == "=") {
              $len--;
            }
            elseif (substr($word, $len - 2, 1) == "=") {
              $len -= 2;
            }
            $part = substr($word, 0, $len);
            $word = substr($word, $len);

            if (strlen($word) > 0) {
              $message .= $part . sprintf("=%s", $this->LE);
            }
            else {
              $buf = $part;
            }
          }
        }
        else {
          $buf_o = $buf;
          $buf .= ($e == 0) ? $word : (' ' . $word);

          if (strlen($buf) > $length and $buf_o != '') {
            $message .= $buf_o . $soft_break;
            $buf = $word;
          }
        }
      }
      $message .= $buf . $this->LE;
    }

    return $message;
  }

  /**
   * Finds last character boundary.
   *
   * Prior to maxLength in a utf-8 quoted (printable) encoded string.
   * Original written by Colin Brown.
   *
   * @param string $encodedText
   *   UTF-8 QP text.
   * @param int $maxLength
   *   Find last character boundary prior to this length.
   *
   * @return int
   *   The length of last character.
   */
  public function utf8CharBoundary($encodedText, $maxLength) {
    $foundSplitPos = FALSE;
    $lookBack = 3;
    while (!$foundSplitPos) {
      $lastChunk = substr($encodedText, $maxLength - $lookBack, $lookBack);
      $encodedCharPos = strpos($lastChunk, "=");
      if ($encodedCharPos !== FALSE) {
        // Found start of encoded character byte within $lookBack block.
        // Check the encoded byte value (the 2 chars after the '=').
        $hex = substr($encodedText, $maxLength - $lookBack + $encodedCharPos + 1, 2);
        $dec = hexdec($hex);
        // Single byte character.
        if ($dec < 128) {
          // If the encoded char was found at pos 0, it will fit
          // otherwise reduce maxLength to start of the encoded char.
          $maxLength = ($encodedCharPos == 0) ? $maxLength :
            $maxLength - ($lookBack - $encodedCharPos);
          $foundSplitPos = TRUE;
        }
        // First byte of a multi byte character.
        elseif ($dec >= 192) {
          // Reduce maxLength to split at start of character.
          $maxLength = $maxLength - ($lookBack - $encodedCharPos);
          $foundSplitPos = TRUE;
        }
        // Middle byte of a multi byte character, look further back.
        elseif ($dec < 192) {
          $lookBack += 3;
        }
      }
      else {
        // No encoded character found.
        $foundSplitPos = TRUE;
      }
    }
    return $maxLength;
  }

  /**
   * Set the body wrapping.
   */
  public function setWordWrap() {
    if ($this->WordWrap < 1) {
      return;
    }

    switch ($this->messageType) {
      case 'alt':
      case 'alt_attachments':
        $this->AltBody = $this->wrapText($this->AltBody, $this->WordWrap);
        break;

      default:
        $this->Body = $this->wrapText($this->Body, $this->WordWrap);
        break;
    }
  }

  /**
   * Assembles message header.
   *
   * @return string
   *   The assembled header.
   */
  public function createHeader() {
    $result = '';

    // Set the boundaries.
    $uniq_id = md5(uniqid(REQUEST_TIME));
    $this->boundary[1] = 'b1_' . $uniq_id;
    $this->boundary[2] = 'b2_' . $uniq_id;

    $result .= $this->haderLine('Date', self::rfcDate());
    if ($this->Sender == '') {
      $result .= $this->haderLine('Return-Path', trim($this->From));
    }
    else {
      $result .= $this->haderLine('Return-Path', trim($this->Sender));
    }

    // To be created automatically by mail().
    if ($this->Mailer != 'mail') {
      if ($this->SingleTo === TRUE) {
        foreach ($this->to as $t) {
          $this->SingleToArray[] = $this->addrFormat($t);
        }
      }
      else {
        if (count($this->to) > 0) {
          $result .= $this->addrAppend('To', $this->to);
        }
        elseif (count($this->cc) == 0) {
          $result .= $this->haderLine('To', 'undisclosed-recipients:;');
        }
      }
    }

    $from = [];
    $from[0][0] = trim($this->From);
    $from[0][1] = $this->FromName;
    $result .= $this->addrAppend('From', $from);

    // Sendmail and mail() extract Cc from the header before sending.
    if (count($this->cc) > 0) {
      $result .= $this->addrAppend('Cc', $this->cc);
    }

    // Sendmail and mail() extract Bcc from the header before sending.
    if ((($this->Mailer == 'sendmail') || ($this->Mailer == 'mail')) && (count($this->bcc) > 0)) {
      $result .= $this->addrAppend('Bcc', $this->bcc);
    }

    if (count($this->ReplyTo) > 0) {
      $result .= $this->addrAppend('Reply-to', $this->ReplyTo);
    }

    // Mail() sets the subject itself.
    if ($this->Mailer != 'mail') {
      $result .= $this->haderLine('Subject', $this->encodeHeader($this->secureHeader($this->Subject)));
    }

    if ($this->MessageID != '') {
      $result .= $this->haderLine('Message-ID', $this->MessageID);
    }
    else {
      $result .= sprintf("Message-ID: <%s@%s>%s", $uniq_id, $this->serverHostname(), $this->LE);
    }
    $result .= $this->haderLine('X-Priority', $this->Priority);
    $result .= $this->haderLine('X-Mailer', 'PHPMailer ' . $this->Version . ' (phpmailer.sourceforge.net)');

    if ($this->ConfirmReadingTo != '') {
      $result .= $this->haderLine('Disposition-Notification-To', '<' . trim($this->ConfirmReadingTo) . '>');
    }

    // Add custom headers.
    for ($index = 0; $index < count($this->CustomHeader); $index++) {
      $result .= $this->haderLine(trim($this->CustomHeader[$index][0]), $this->encodeHeader(trim($this->CustomHeader[$index][1])));
    }
    if (!$this->signKeyFile) {
      $result .= $this->haderLine('MIME-Version', '1.0');
      $result .= $this->getMailMime();
    }

    return $result;
  }

  /**
   * Returns the message MIME.
   *
   * @return string
   *   Mail mime type.
   */
  public function getMailMime() {
    $result = '';
    switch ($this->messageType) {
      case 'plain':
        $result .= $this->haderLine('Content-Transfer-Encoding', $this->Encoding);
        $result .= sprintf("Content-Type: %s; charset=\"%s\"", $this->ContentType, $this->CharSet);
        break;

      case 'attachments':
      case 'alt_attachments':
        if ($this->inlineImageExists()) {
          $result .= sprintf("Content-Type: %s;%s\ttype=\"text/html\";%s\tboundary=\"%s\"%s", 'multipart/related', $this->LE, $this->LE, $this->boundary[1], $this->LE);
        }
        else {
          $result .= $this->haderLine('Content-Type', 'multipart/mixed;');
          $result .= $this->textLine("\tboundary=\"" . $this->boundary[1] . '"');
        }
        break;

      case 'alt':
        $result .= $this->haderLine('Content-Type', 'multipart/alternative;');
        $result .= $this->textLine("\tboundary=\"" . $this->boundary[1] . '"');
        break;
    }

    if ($this->Mailer != 'mail') {
      $result .= $this->LE . $this->LE;
    }

    return $result;
  }

  /**
   * Assembles the message body.
   *
   * @return string
   *   The assembled message body.
   */
  public function createBody() {
    $body = '';

    if ($this->signKeyFile) {
      $body .= $this->getMailMime();
    }

    $this->setWordWrap();

    switch ($this->messageType) {
      case 'alt':
        $body .= $this->getBoundary($this->boundary[1], '', 'text/plain', '');
        $body .= $this->encodeString($this->AltBody, $this->Encoding);
        $body .= $this->LE . $this->LE;
        $body .= $this->getBoundary($this->boundary[1], '', 'text/html', '');
        $body .= $this->encodeString($this->Body, $this->Encoding);
        $body .= $this->LE . $this->LE;
        $body .= $this->endBoundary($this->boundary[1]);
        break;

      case 'plain':
        $body .= $this->encodeString($this->Body, $this->Encoding);
        break;

      case 'attachments':
        if ($this->is_html) {
          $body .= $this->getBoundary($this->boundary[1], '', $this->is_html, '');
        }
        else {
          $body .= $this->getBoundary($this->boundary[1], '', '', '');
        }
        $body .= $this->encodeString($this->Body, $this->Encoding);
        $body .= $this->LE;
        $body .= $this->attachAll();
        break;

      case 'alt_attachments':
        $body .= sprintf("--%s%s", $this->boundary[1], $this->LE);
        $body .= sprintf("Content-Type: %s;%s" . "\tboundary=\"%s\"%s", 'multipart/alternative', $this->LE, $this->boundary[2], $this->LE . $this->LE);
        // Create text body.
        $body .= $this->getBoundary($this->boundary[2], '', 'text/plain', '') . $this->LE;
        $body .= $this->encodeString($this->AltBody, $this->Encoding);
        $body .= $this->LE . $this->LE;
        // Create the HTML body.
        $body .= $this->getBoundary($this->boundary[2], '', 'text/html', '') . $this->LE;
        $body .= $this->encodeString($this->Body, $this->Encoding);
        $body .= $this->LE . $this->LE;
        $body .= $this->endBoundary($this->boundary[2]);
        $body .= $this->attachAll();
        break;
    }

    if ($this->isError()) {
      $body = '';
    }
    elseif ($this->signKeyFile) {
      try {
        $file = tempnam('', 'mail');
        // TODO check this worked.
        file_put_contents($file, $body);
        $signed = tempnam("", "signed");
        if (@openssl_pkcs7_sign($file, $signed, "file://" . $this->signCertFile, [
          "file://" . $this->signKeyFile,
          $this->signKeyPass,
        ], NULL)) {
          @unlink($file);
          @unlink($signed);
          $body = file_get_contents($signed);
        }
        else {
          @unlink($file);
          @unlink($signed);
          throw new PHPMailerException(t('Signing Error: !err', ['!err' => openssl_error_string()]));
        }
      } catch (PHPMailerException $e) {
        $body = '';
        if ($this->exceptions) {
          throw $e;
        }
      }
    }

    return $body;
  }

  /**
   * Returns the start of a message boundary.
   */
  private function getBoundary($boundary, $charSet, $contentType, $encoding) {
    $result = '';
    if ($charSet == '') {
      $charSet = $this->CharSet;
    }
    if ($contentType == '') {
      $contentType = $this->ContentType;
    }
    if ($encoding == '') {
      $encoding = $this->Encoding;
    }
    $result .= $this->textLine('--' . $boundary);
    $result .= sprintf("Content-Type: %s; charset = \"%s\"", $contentType, $charSet);
    $result .= $this->LE;
    $result .= $this->haderLine('Content-Transfer-Encoding', $encoding);
    $result .= $this->LE;

    return $result;
  }

  /**
   * Returns the end of a message boundary.
   */
  private function endBoundary($boundary) {
    return $this->LE . '--' . $boundary . '--' . $this->LE;
  }

  /**
   * Sets the message type.
   */
  private function setMessageType() {
    if (count($this->attachment) < 1 && strlen($this->AltBody) < 1) {
      $this->messageType = 'plain';
    }
    else {
      if (count($this->attachment) > 0) {
        $this->messageType = 'attachments';
      }
      if (strlen($this->AltBody) > 0 && count($this->attachment) < 1) {
        $this->messageType = 'alt';
      }
      if (strlen($this->AltBody) > 0 && count($this->attachment) > 0) {
        $this->messageType = 'alt_attachments';
      }
    }
  }

  /**
   * Returns a formatted header line.
   *
   * @return string
   *   An formatted header line.
   */
  public function haderLine($name, $value) {
    return $name . ': ' . $value . $this->LE;
  }

  /**
   * Returns a formatted mail line.
   *
   * @return string
   *   An formatted mail line.
   */
  public function textLine($value) {
    return $value . $this->LE;
  }

  /**
   * Adds an attachment from a path on the filesystem.
   *
   * @param string $path
   *   Path to the attachment.
   * @param string $name
   *   Overrides the attachment name.
   * @param string $encoding
   *   File encoding (see $Encoding).
   * @param string $type
   *   File extension (MIME) type.
   *
   * @return bool
   *   FALSE if the file could not be found.
   */
  public function addAttachment($path, $name = '', $encoding = 'base64', $type = 'application/octet-stream') {
    try {
      if (!@is_file($path)) {
        throw new PHPMailerException(t('Could not access file: @nofile', ['@nofile' => $path]), self::STOP_CONTINUE);
      }
      $filename = basename($path);
      if ($name == '') {
        $name = $filename;
      }

      $this->attachment[] = [
        0 => $path,
        1 => $filename,
        2 => $name,
        3 => $encoding,
        4 => $type,
        5 => FALSE,
        6 => 'attachment',
        7 => 0,
      ];

    } catch (PHPMailerException $e) {
      $this->setError($e->getMessage());
      if ($this->exceptions) {
        throw $e;
      }
      echo $e->getMessage() . "\n";
      if ($e->getCode() == self::STOP_CRITICAL) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Return the current array of attachments.
   */
  public function getAttachments() {
    return $this->attachment;
  }

  /**
   * Attaches all fs, string, and binary attachments to the message.
   *
   * @return string
   *   An empty string on failure.
   */
  private function attachAll() {
    // Return text of body.
    $mime = [];
    $cidUniq = [];
    $incl = [];

    // Add all attachments.
    foreach ($this->attachment as $attachment) {
      // Check for string attachment.
      $bString = $attachment[5];
      if ($bString) {
        $string = $attachment[0];
      }
      else {
        $path = $attachment[0];
      }

      if (in_array($attachment[0], $incl)) {
        continue;
      }

      $filename = $attachment[1];
      $name = $attachment[2];
      $encoding = $attachment[3];
      $type = $attachment[4];
      $disposition = $attachment[6];
      $cid = $attachment[7];
      $incl[] = $attachment[0];

      if ($disposition == 'inline' && isset($cidUniq[$cid])) {
        continue;
      }

      $cidUniq[$cid] = TRUE;

      $mime[] = sprintf("--%s%s", $this->boundary[1], $this->LE);
      $mime[] = sprintf("Content-Type: %s; name=\"%s\"%s", $type, $this->encodeHeader($this->secureHeader($name)), $this->LE);
      $mime[] = sprintf("Content-Transfer-Encoding: %s%s", $encoding, $this->LE);

      if ($disposition == 'inline') {
        $mime[] = sprintf("Content-ID: <%s>%s", $cid, $this->LE);
      }

      $mime[] = sprintf("Content-Disposition: %s; filename=\"%s\"%s", $disposition, $this->encodeHeader($this->secureHeader($name)), $this->LE . $this->LE);

      // Encode as string attachment.
      if ($bString) {
        $mime[] = $this->encodeString($string, $encoding);
        if ($this->isError()) {
          return '';
        }
        $mime[] = $this->LE . $this->LE;
      }
      else {
        $mime[] = $this->encodeFile($path, $encoding);
        if ($this->isError()) {
          return '';
        }
        $mime[] = $this->LE . $this->LE;
      }
    }

    $mime[] = sprintf("--%s--%s", $this->boundary[1], $this->LE);

    return implode('', $mime);
  }

  /**
   * Encodes attachment in requested format.
   *
   * @param string $path
   *   The full path to the file.
   * @param string $encoding
   *   The encoding to use; one of 'base64', '7bit', '8bit', 'binary',
   *   'quoted-printable'.
   *
   * @return string
   *   An empty string on failure.
   */
  private function encodeFile($path, $encoding = 'base64') {
    try {
      if (!is_readable($path)) {
        throw new PHPMailerException(t('File Error: Could not open file: @nofile', ['@nofile' => $path]), self::STOP_CONTINUE);
      }
      if (function_exists('get_magic_quotes')) {

        /**
         * Get magic quotes.
         */
        function get_magic_quotes() {
          return FALSE;
        }

      }

      $magic_quotes = get_magic_quotes_runtime();
      if ($magic_quotes) {
        if (version_compare(PHP_VERSION, '5.3.0', '<')) {
          set_magic_quotes_runtime(0);
        }
        else {
          ini_set('magic_quotes_runtime', 0);
        }
      }

      $file_buffer = file_get_contents($path);
      $file_buffer = $this->encodeString($file_buffer, $encoding);
      if ($magic_quotes) {
        if (version_compare(PHP_VERSION, '5.3.0', '<')) {
          set_magic_quotes_runtime($magic_quotes);
        }
        else {
          ini_set('magic_quotes_runtime', $magic_quotes);
        }
      }
      return $file_buffer;
    } catch (Exception $e) {
      $this->setError($e->getMessage());
      return '';
    }
  }

  /**
   * Encodes string to requested format.
   *
   * @param string $str
   *   The text to encode.
   * @param string $encoding
   *   The encoding to use; one of 'base64', '7bit', '8bit', 'binary',
   *   'quoted-printable'.
   *
   * @return string
   *   An empty string on failure.
   */
  public function encodeString($str, $encoding = 'base64') {
    $encoded = '';
    switch (strtolower($encoding)) {
      case 'base64':
        $encoded = chunk_split(base64_encode($str), 76, $this->LE);
        break;

      case '7bit':
      case '8bit':
        $encoded = $this->fixEol($str);
        // Make sure it ends with a line break.
        if (substr($encoded, -(strlen($this->LE))) != $this->LE) {
          $encoded .= $this->LE;
        }
        break;

      case 'binary':
        $encoded = $str;
        break;

      case 'quoted-printable':
        $encoded = $this->encodeQp($str);
        break;

      default:
        $this->setError(t('Unknown encoding: @enc', ['@enc' => $encoding]));
        break;
    }
    return $encoded;
  }

  /**
   * Encode a header string to best (shortest) of Q, B, quoted or none.
   */
  public function encodeHeader($str, $position = 'text') {
    $x = 0;

    switch (strtolower($position)) {
      case 'phrase':
        if (!preg_match('/[\200-\377]/', $str)) {
          // Can't use addslashes as we don't know what value
          // has magic_quotes_sybase.
          $encoded = addcslashes($str, "\0..\37\177\\\"");
          if (($str == $encoded) && !preg_match('/[^A-Za-z0-9!#$%&\'*+\/=?^_`{|}~ -]/', $str)) {
            return ($encoded);
          }
          else {
            return ("\"$encoded\"");
          }
        }
        $x = preg_match_all('/[^\040\041\043-\133\135-\176]/', $str, $matches);
        break;

      case 'comment':
        $x = preg_match_all('/[()"]/', $str, $matches);
      // Fall-through.
      case 'text':

      default:
        $x += preg_match_all('/[\000-\010\013\014\016-\037\177-\377]/', $str, $matches);
        break;
    }

    if ($x == 0) {
      return ($str);
    }

    $maxlen = 75 - 7 - strlen($this->CharSet);
    // Try to select the encoding which should produce the
    // shortest output.
    if (strlen($str) / 3 < $x) {
      $encoding = 'B';
      if (function_exists('mb_strlen') && $this->hasMultiBytes($str)) {
        // Use a custom function which correctly encodes and wraps long
        // multibyte strings without breaking lines within a character.
        $encoded = $this->base64EncodeWrapMb($str);
      }
      else {
        $encoded = base64_encode($str);
        $maxlen -= $maxlen % 4;
        $encoded = trim(chunk_split($encoded, $maxlen, "\n"));
      }
    }
    else {
      $encoding = 'Q';
      $encoded = $this->encodeQ($str, $position);
      $encoded = $this->wrapText($encoded, $maxlen, TRUE);
      $encoded = str_replace('=' . $this->LE, "\n", trim($encoded));
    }

    $encoded = preg_replace('/^(.*)$/m', " =?" . $this->CharSet . "?$encoding?\\1?=", $encoded);
    $encoded = trim(str_replace("\n", $this->LE, $encoded));

    return $encoded;
  }

  /**
   * Checks if a string contains multibyte characters.
   *
   * @param string $str
   *   Multi-byte text to wrap encode.
   *
   * @return bool
   *   True on success, otherwise False.
   */
  public function hasMultiBytes($str) {
    if (function_exists('mb_strlen')) {
      return (strlen($str) > mb_strlen($str, $this->CharSet));
    }
    else {
      // Assume no multibytes (we can't handle without
      // mbstring functions anyway).
      return FALSE;
    }
  }

  /**
   * Correctly encodes and wraps long multibyte strings.
   *
   * For mail headers without breaking lines within a character.
   * Adapted from a function by paravoid at
   * http://uk.php.net/manual/en/function.mb-encode-mimeheader.php.
   *
   * @param string $str
   *   Multi-byte text to wrap encode.
   *
   * @return string
   *   Base64 string.
   */
  public function base64EncodeWrapMb($str) {
    $start = "=?" . $this->CharSet . "?B?";
    $end = "?=";
    $encoded = "";

    $mb_length = mb_strlen($str, $this->CharSet);
    // Each line must have length <= 75, including $start and $end.
    $length = 75 - strlen($start) - strlen($end);
    // Average multi-byte ratio.
    $ratio = $mb_length / strlen($str);
    // Base64 has a 4:3 ratio.
    $offset = $avgLength = floor($length * $ratio * .75);

    for ($i = 0; $i < $mb_length; $i += $offset) {
      $lookBack = 0;

      do {
        $offset = $avgLength - $lookBack;
        $chunk = mb_substr($str, $i, $offset, $this->CharSet);
        $chunk = base64_encode($chunk);
        $lookBack++;
      } while (strlen($chunk) > $length);

      $encoded .= $chunk . $this->LE;
    }

    // Chomp the last linefeed.
    $encoded = substr($encoded, 0, -strlen($this->LE));
    return $encoded;
  }

  /**
   * Encode string to quoted-printable.
   *
   * Only uses standard PHP, slow, but will always work.
   *
   * @param string $input
   *   The text to encode.
   * @param int $line_max
   *   Number of chars allowed on a line before wrapping.
   *
   * @return string
   *   An string is encoded.
   */
  public function encodeQpPhp($input = '', $line_max = 76, $space_conv = FALSE) {
    $hex = [
      '0',
      '1',
      '2',
      '3',
      '4',
      '5',
      '6',
      '7',
      '8',
      '9',
      'A',
      'B',
      'C',
      'D',
      'E',
      'F',
    ];
    $lines = preg_split('/(?:\r\n|\r|\n)/', $input);
    $eol = "\r\n";
    $escape = '=';
    $output = '';
    while (list(, $line) = each($lines)) {
      $linlen = strlen($line);
      $newline = '';
      for ($i = 0; $i < $linlen; $i++) {
        $c = substr($line, $i, 1);
        $dec = ord($c);
        // Convert first point in the line into = 2E.
        if (($i == 0) && ($dec == 46)) {
          $c = '=2E';
        }
        if ($dec == 32) {
          // Convert space at eol only.
          if ($i == ($linlen - 1)) {
            $c = '=20';
          }
          elseif ($space_conv) {
            $c = '=20';
          }
        }
        // Always encode "\t", which is *not* required.
        elseif (($dec == 61) || ($dec < 32) || ($dec > 126)) {
          $h2 = floor($dec / 16);
          $h1 = floor($dec % 16);
          $c = $escape . $hex[$h2] . $hex[$h1];
        }
        // CRLF is not counted.
        if ((strlen($newline) + strlen($c)) >= $line_max) {
          // Soft line break; " =\r\n" is okay.
          $output .= $newline . $escape . $eol;
          $newline = '';
          // Check if newline first character will be point or not.
          if ($dec == 46) {
            $c = '=2E';
          }
        }
        $newline .= $c;
      }
      $output .= $newline . $eol;
    }
    return $output;
  }

  /**
   * Encode string to RFC2045 (6.7) quoted-printable format.
   *
   * Uses a PHP5 stream filter to do the encoding about 64x faster
   * than the old version.
   * Also results in same content as you started with after decoding.
   *
   * @param string $string
   *   The text to encode.
   * @param int $line_max
   *   Number of chars allowed on a line before wrapping.
   * @param bool $space_conv
   *   Dummy param for compatibility with existing encodeQp function.
   *
   * @return string
   *   An string to encoded.
   */
  public function encodeQp($string, $line_max = 76, $space_conv = FALSE) {
    // Use native function if it's available (>= PHP5.3).
    if (function_exists('quoted_printable_encode')) {
      return quoted_printable_encode($string);
    }
    $filters = stream_get_filters();
    // Got convert stream filter.
    if (!in_array('convert.*', $filters)) {
      // Fall back to old implementation.
      return $this->encodeQpPhp($string, $line_max, $space_conv);
    }
    $fp = fopen('php://temp/', 'r+');
    // Normalise line breaks.
    $string = preg_replace('/\r\n?/', $this->LE, $string);
    $params = ['line-length' => $line_max, 'line-break-chars' => $this->LE];
    $s = stream_filter_append($fp, 'convert.quoted-printable-encode', STREAM_FILTER_READ, $params);
    fwrite($fp, $string);
    rewind($fp);
    $out = stream_get_contents($fp);
    stream_filter_remove($s);
    // If it is first char on a line, workaround for bug in Exchange.
    $out = preg_replace('/^\./m', '=2E', $out);
    fclose($fp);
    return $out;
  }

  /**
   * Encode string to q encoding.
   *
   * @param string $str
   *   The text to encode.
   * @param string $position
   *   Where the text is going to be used, see the RFC for what that means.
   *
   * @return string
   *   An string to encoded.
   */
  public function encodeQ($str, $position = 'text') {
    // There should not be any EOL in the string.
    $encoded = preg_replace('/[\r\n]*/', '', $str);

    switch (strtolower($position)) {
      case 'phrase':
        $encoded = preg_replace("/([^A-Za-z0-9!*+\/ -])/e", "'='.sprintf('%02X', ord('\\1'))", $encoded);
        break;

      case 'comment':
        $encoded = preg_replace("/([\(\)\"])/e", "'='.sprintf('%02X', ord('\\1'))", $encoded);
      case 'text':
      default:
        // Replace every high ascii, control =, ? and _ characters.
        // TODO using /e (equivalent to eval()) is probably not a good idea.
        $encoded = preg_replace('/([\000-\011\013\014\016-\037\075\077\137\177-\377])/e', "'='.sprintf('%02X', ord('\\1'))", $encoded);
        break;
    }

    // Replace every spaces to _ (more readable than =20).
    $encoded = str_replace(' ', '_', $encoded);

    return $encoded;
  }

  /**
   * Adds a string or binary attachment (non-filesystem) to the list.
   *
   * This method can be used to attach ascii or binary data,
   * such as a BLOB record from a database.
   *
   * @param string $string
   *   String attachment data.
   * @param string $filename
   *   Name of the attachment.
   * @param string $encoding
   *   File encoding (see $Encoding).
   * @param string $type
   *   File extension (MIME) type.
   */
  public function addStringAttachment($string, $filename, $encoding = 'base64', $type = 'application/octet-stream') {
    // Append to $attachment array.
    $this->attachment[] = [
      0 => $string,
      1 => $filename,
      2 => basename($filename),
      3 => $encoding,
      4 => $type,
      5 => TRUE,
      6 => 'attachment',
      7 => 0,
    ];
  }

  /**
   * Adds an embedded attachment.
   *
   * This can include images, sounds, and just about any other document.
   * Make sure to set the $type to an image type.
   * For JPEG images use "image/jpeg" and for GIF images use "image/gif".
   *
   * @param string $path
   *   Path to the attachment.
   * @param string $cid
   *   Content ID of the attachment.
   *   Use this to identify the Id for accessing the image in an HTML form.
   * @param string $name
   *   Overrides the attachment name.
   * @param string $encoding
   *   File encoding (see $Encoding).
   * @param string $type
   *   File extension (MIME) type.
   *
   * @return bool
   *   True on success.
   */
  public function adEmbeddedImage($path, $cid, $name = '', $encoding = 'base64', $type = 'application/octet-stream') {

    if (!@is_file($path)) {
      $this->setError(t('Could not access file: @nofile', ['@nofile' => $path]));
      return FALSE;
    }

    $filename = basename($path);
    if ($name == '') {
      $name = $filename;
    }

    // Append to $attachment array.
    $this->attachment[] = [
      0 => $path,
      1 => $filename,
      2 => $name,
      3 => $encoding,
      4 => $type,
      5 => FALSE,
      6 => 'inline',
      7 => $cid,
    ];

    return TRUE;
  }

  /**
   * Returns TRUE if an inline attachment is present.
   *
   * @return bool
   *   True on success, otherwise False.
   */
  public function inlineImageExists() {
    foreach ($this->attachment as $attachment) {
      if ($attachment[6] == 'inline') {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Clears all recipients assigned in the TO array.
   */
  public function clearAddresses() {
    foreach ($this->to as $to) {
      unset($this->allRecipients[strtolower($to[0])]);
    }
    $this->to = [];
  }

  /**
   * Clears all recipients assigned in the CC array.
   */
  public function clearCcs() {
    foreach ($this->cc as $cc) {
      unset($this->allRecipients[strtolower($cc[0])]);
    }
    $this->cc = [];
  }

  /**
   * Clears all recipients assigned in the BCC array.
   */
  public function clearBccs() {
    foreach ($this->bcc as $bcc) {
      unset($this->allRecipients[strtolower($bcc[0])]);
    }
    $this->bcc = [];
  }

  /**
   * Clears all recipients assigned in the ReplyTo array.
   */
  public function clearReplyTos() {
    $this->ReplyTo = [];
  }

  /**
   * Clears all recipients assigned in the TO, CC and BCC array.
   */
  public function clearAllRecipients() {
    $this->to = [];
    $this->cc = [];
    $this->bcc = [];
    $this->allRecipients = [];
  }

  /**
   * Clears all previously set filesystem, string, and binary attachments.
   */
  public function clearAttachments() {
    $this->attachment = [];
  }

  /**
   * Clears all custom headers.
   */
  public function clearCustomHeaders() {
    $this->CustomHeader = [];
  }

  /**
   * Adds the error message to the error container.
   */
  protected function setError($msg) {
    $this->errorCount++;
    if ($this->Mailer == 'smtp' and !is_null($this->smtp)) {
      $lasterror = $this->smtp->getError();
      if (!empty($lasterror) and array_key_exists('smtp_msg', $lasterror)) {
        $msg .= '<p>' . t('SMTP server error: @lasterror', ['@lasterror' => $lasterror['smtp_msg']]) . "</p>\n";
      }
    }
    $this->ErrorInfo = $msg;
  }

  /**
   * Returns the proper RFC 822 formatted date.
   *
   * @return string
   *   The rfc date time.
   */
  public static function rfcDate() {
    $tz = date('Z');
    $tzs = ($tz < 0) ? '-' : '+';
    $tz = abs($tz);
    $tz = (int) ($tz / 3600) * 100 + ($tz % 3600) / 60;
    $result = sprintf("%s %s%04d", date('D, j M Y H:i:s'), $tzs, $tz);

    return $result;
  }

  /**
   * Returns the server hostname or 'localhost.localdomain' if unknown.
   *
   * @return string
   *   Server host name.
   */
  private function serverHostname() {
    if (!empty($this->Hostname)) {
      $result = $this->Hostname;
    }
    elseif (isset($_SERVER['SERVER_NAME'])) {
      $result = $_SERVER['SERVER_NAME'];
    }
    else {
      $result = 'localhost.localdomain';
    }

    return $result;
  }

  /**
   * Returns TRUE if an error occurred.
   *
   * @return bool
   *   True on success, otherwise False.
   */
  public function isError() {
    return ($this->errorCount > 0);
  }

  /**
   * Changes every end of line from CR or LF to CRLF.
   *
   * @return string
   *   CRLF string.
   */
  private function fixEol($str) {
    $str = str_replace("\r\n", "\n", $str);
    $str = str_replace("\r", "\n", $str);
    $str = str_replace("\n", $this->LE, $str);
    return $str;
  }

  /**
   * Adds a custom header.
   */
  public function addCustomHeader($custom_header) {
    $this->CustomHeader[] = explode(':', $custom_header, 2);
  }

  /**
   * Evaluates the message.
   *
   * Returns modifications for inline images and backgrounds.
   */
  public function msgHtml($message, $basedir = '') {
    preg_match_all("/(src|background)=\"(.*)\"/Ui", $message, $images);
    if (isset($images[2])) {
      foreach ($images[2] as $i => $url) {
        // Do not change urls for absolute images (thanks to corvuscorax).
        if (!preg_match('#^[A-z]+://#', $url)) {
          $filename = basename($url);
          $directory = dirname($url);
          ($directory == '.') ? $directory = '' : '';
          $cid = 'cid:' . md5($filename);
          $ext = pathinfo($filename, PATHINFO_EXTENSION);
          $mimeType = self::mimeTypes($ext);
          if (strlen($basedir) > 1 && substr($basedir, -1) != '/') {
            $basedir .= '/';
          }
          if (strlen($directory) > 1 && substr($directory, -1) != '/') {
            $directory .= '/';
          }
          if ($this->adEmbeddedImage($basedir . $directory . $filename, md5($filename), $filename, 'base64', $mimeType)) {
            $message = preg_replace("/" . $images[1][$i] . "=\"" . preg_quote($url, '/') . "\"/Ui", $images[1][$i] . "=\"" . $cid . "\"", $message);
          }
        }
      }
    }
    $this->isHtml(TRUE);
    $this->Body = $message;
    $textMsg = trim(strip_tags(preg_replace('/<(head|title|style|script)[^>]*>.*?<\/\\1>/s', '', $message)));
    if (!empty($textMsg) && empty($this->AltBody)) {
      $this->AltBody = html_entity_decode($textMsg);
    }
    if (empty($this->AltBody)) {
      $this->AltBody = 'To view this email message, open it in a program that understands HTML!' . "\n\n";
    }
  }

  /**
   * Gets the MIME type of the embedded or inline image.
   *
   * @param string $ext
   *   File extension.
   *
   * @return string
   *   MIME type of ext.
   */
  public static function mimeTypes($ext = '') {
    $mimes = [
      'hqx' => 'application/mac-binhex40',
      'cpt' => 'application/mac-compactpro',
      'doc' => 'application/msword',
      'bin' => 'application/macbinary',
      'dms' => 'application/octet-stream',
      'lha' => 'application/octet-stream',
      'lzh' => 'application/octet-stream',
      'exe' => 'application/octet-stream',
      'class' => 'application/octet-stream',
      'psd' => 'application/octet-stream',
      'so' => 'application/octet-stream',
      'sea' => 'application/octet-stream',
      'dll' => 'application/octet-stream',
      'oda' => 'application/oda',
      'pdf' => 'application/pdf',
      'ai' => 'application/postscript',
      'eps' => 'application/postscript',
      'ps' => 'application/postscript',
      'smi' => 'application/smil',
      'smil' => 'application/smil',
      'mif' => 'application/vnd.mif',
      'xls' => 'application/vnd.ms-excel',
      'ppt' => 'application/vnd.ms-powerpoint',
      'wbxml' => 'application/vnd.wap.wbxml',
      'wmlc' => 'application/vnd.wap.wmlc',
      'dcr' => 'application/x-director',
      'dir' => 'application/x-director',
      'dxr' => 'application/x-director',
      'dvi' => 'application/x-dvi',
      'gtar' => 'application/x-gtar',
      'php' => 'application/x-httpd-php',
      'php4' => 'application/x-httpd-php',
      'php3' => 'application/x-httpd-php',
      'phtml' => 'application/x-httpd-php',
      'phps' => 'application/x-httpd-php-source',
      'js' => 'application/x-javascript',
      'swf' => 'application/x-shockwave-flash',
      'sit' => 'application/x-stuffit',
      'tar' => 'application/x-tar',
      'tgz' => 'application/x-tar',
      'xhtml' => 'application/xhtml+xml',
      'xht' => 'application/xhtml+xml',
      'zip' => 'application/zip',
      'mid' => 'audio/midi',
      'midi' => 'audio/midi',
      'mpga' => 'audio/mpeg',
      'mp2' => 'audio/mpeg',
      'mp3' => 'audio/mpeg',
      'aif' => 'audio/x-aiff',
      'aiff' => 'audio/x-aiff',
      'aifc' => 'audio/x-aiff',
      'ram' => 'audio/x-pn-realaudio',
      'rm' => 'audio/x-pn-realaudio',
      'rpm' => 'audio/x-pn-realaudio-plugin',
      'ra' => 'audio/x-realaudio',
      'rv' => 'video/vnd.rn-realvideo',
      'wav' => 'audio/x-wav',
      'bmp' => 'image/bmp',
      'gif' => 'image/gif',
      'jpeg' => 'image/jpeg',
      'jpg' => 'image/jpeg',
      'jpe' => 'image/jpeg',
      'png' => 'image/png',
      'tiff' => 'image/tiff',
      'tif' => 'image/tiff',
      'css' => 'text/css',
      'html' => 'text/html',
      'htm' => 'text/html',
      'shtml' => 'text/html',
      'txt' => 'text/plain',
      'text' => 'text/plain',
      'log' => 'text/plain',
      'rtx' => 'text/richtext',
      'rtf' => 'text/rtf',
      'xml' => 'text/xml',
      'xsl' => 'text/xml',
      'mpeg' => 'video/mpeg',
      'mpg' => 'video/mpeg',
      'mpe' => 'video/mpeg',
      'qt' => 'video/quicktime',
      'mov' => 'video/quicktime',
      'avi' => 'video/x-msvideo',
      'movie' => 'video/x-sgi-movie',
      'doc' => 'application/msword',
      'word' => 'application/msword',
      'xl' => 'application/excel',
      'eml' => 'message/rfc822',
    ];
    return (!isset($mimes[strtolower($ext)])) ? 'application/octet-stream' : $mimes[strtolower($ext)];
  }

  /**
   * Set (or reset) Class Objects (variables).
   *
   * Usage Example:
   * $page->set('X-Priority', '3').
   *
   * @param string $name
   *   Parameter Name.
   * @param mixed $value
   *   Parameter Value.
   */
  public function set($name, $value = '') {
    try {
      if (isset($this->$name)) {
        $this->$name = $value;
      }
      else {
        throw new PHPMailerException(t('Cannot set or reset variable: @name', ['@name' => $name]), self::STOP_CRITICAL);
      }
    } catch (Exception $e) {
      $this->setError($e->getMessage());
      if ($e->getCode() == self::STOP_CRITICAL) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Strips newlines to prevent header injection.
   *
   * @param string $str
   *   String.
   *
   * @return string
   *   Trimed string.
   */
  public function secureHeader($str) {
    $str = str_replace("\r", '', $str);
    $str = str_replace("\n", '', $str);
    return trim($str);
  }

  /**
   * Set the private key file and password to sign the message.
   *
   * @param string $cert_filename
   *   Cert file name.
   * @param string $key_filename
   *   Parameter File Name.
   * @param string $key_pass
   *   Password for private key.
   */
  public function sign($cert_filename, $key_filename, $key_pass) {
    $this->signCertFile = $cert_filename;
    $this->signKeyFile = $key_filename;
    $this->signKeyPass = $key_pass;
  }

  /**
   * Set the private key file and password to sign the message.
   *
   * @param string $txt
   *   DKIM QP text.
   */
  public function dkimQp($txt) {
    $tmp = "";
    $line = "";
    for ($i = 0; $i < strlen($txt); $i++) {
      $ord = ord($txt[$i]);
      if (((0x21 <= $ord) && ($ord <= 0x3A)) || $ord == 0x3C || ((0x3E <= $ord) && ($ord <= 0x7E))) {
        $line .= $txt[$i];
      }
      else {
        $line .= "=" . sprintf("%02X", $ord);
      }
    }
    return $line;
  }

  /**
   * Generate DKIM signature.
   *
   * @param string $s
   *   Header.
   */
  public function dkimSign($s) {
    $privKeyStr = file_get_contents($this->DKIMPrivate);
    if ($this->DKIM_passphrase != '') {
      $privKey = openssl_pkey_get_private($privKeyStr, $this->DKIM_passphrase);
    }
    else {
      $privKey = $privKeyStr;
    }
    if (openssl_sign($s, $signature, $privKey)) {
      return base64_encode($signature);
    }
  }

  /**
   * Generate DKIM Canonicalization Header.
   *
   * @param string $s
   *   Header.
   */
  public function dkimHeaderC($s) {
    $s = preg_replace("/\r\n\s+/", " ", $s);
    $lines = explode("\r\n", $s);
    foreach ($lines as $key => $line) {
      list($heading, $value) = explode(":", $line, 2);
      $heading = strtolower($heading);
      // Compress useless spaces.
      $value = preg_replace("/\s+/", " ", $value);
      // Don't forget to remove WSP around the value.
      $lines[$key] = $heading . ":" . trim($value);
    }
    $s = implode("\r\n", $lines);
    return $s;
  }

  /**
   * Generate DKIM Canonicalization Body.
   *
   * @param string $body
   *   Message Body.
   */
  public function dkimBodyC($body) {
    if ($body == '') {
      return "\r\n";
    }
    // Stabilize line endings.
    $body = str_replace("\r\n", "\n", $body);
    $body = str_replace("\n", "\r\n", $body);
    // END stabilize line endings.
    while (substr($body, strlen($body) - 4, 4) == "\r\n\r\n") {
      $body = substr($body, 0, strlen($body) - 2);
    }
    return $body;
  }

  /**
   * Create the DKIM header, body, as new header.
   *
   * @param string $headers_line
   *   Header lines.
   * @param string $subject
   *   Subject.
   * @param string $body
   *   Body.
   */
  public function dkimAdd($headers_line, $subject, $body) {
    // Signature & hash algorithms.
    $dkimSignatureType = 'rsa-sha1';
    // Canonicalization of header/body.
    $dkimCanonicalization = 'relaxed/simple';
    // Query method.
    $dkimQuery = 'dns/txt';
    // Signature Timestamp = seconds since 00:00:00
    // - Jan 1, 1970 (UTC time zone).
    $dkimTime = REQUEST_TIME;
    $subject_header = "Subject: $subject";
    $headers = explode("\r\n", $headers_line);
    foreach ($headers as $header) {
      if (strpos($header, 'From:') === 0) {
        $from_header = $header;
      }
      elseif (strpos($header, 'To:') === 0) {
        $to_header = $header;
      }
    }
    $from = str_replace('|', '=7C', $this->dkimQp($from_header));
    $to = str_replace('|', '=7C', $this->dkimQp($to_header));
    // Copied header fields (dkim-quoted-printable.
    $subject = str_replace('|', '=7C', $this->dkimQp($subject_header));
    $body = $this->dkimBodyC($body);
    // Length of body.
    $dkimlen = strlen($body);
    // Base64 of packed binary SHA-1 hash of body.
    $dkimB64 = base64_encode(pack("H*", sha1($body)));
    $ident = ($this->dkimIdentity == '') ? '' : " i=" . $this->dkimIdentity . ";";
    $dkimhdrs = "DKIM-Signature: v=1; a=" . $dkimSignatureType . "; q=" . $dkimQuery . "; l=" . $dkimlen . "; s=" . $this->dkimSelector . ";\r\n" .
      "\tt=" . $dkimTime . "; c=" . $dkimCanonicalization . ";\r\n" .
      "\th=From:To:Subject;\r\n" .
      "\td=" . $this->dkimDomain . ";" . $ident . "\r\n" .
      "\tz=$from\r\n" .
      "\t|$to\r\n" .
      "\t|$subject;\r\n" .
      "\tbh=" . $dkimB64 . ";\r\n" .
      "\tb=";
    $toSign = $this->dkimHeaderC($from_header . "\r\n" . $to_header . "\r\n" . $subject_header . "\r\n" . $dkimhdrs);
    $signed = $this->dkimSign($toSign);
    return "X-PHPMAILER-DKIM: phpmailer.worxware.com\r\n" . $dkimhdrs . $signed . "\r\n";
  }

  /**
   * Do callback.
   */
  protected function doCallback($isSent, $to, $cc, $bcc, $subject, $body) {
    if (!empty($this->actionFunction) && function_exists($this->actionFunction)) {
      $params = [$isSent, $to, $cc, $bcc, $subject, $body];
      call_user_func_array($this->actionFunction, $params);
    }
  }

}
