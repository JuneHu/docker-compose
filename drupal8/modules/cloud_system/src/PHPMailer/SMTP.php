<?php

namespace Drupal\cloud_system\PHPMailer;

/**
 * Class SMTP.
 *
 * SMTP is rfc 821 compliant and implements all the rfc 821.
 * SMTP commands except TURN which will always return a not implemented error.
 * SMTP also provides some utility methods for sending mail
 * to an SMTP server.
 *
 * @package Drupal\cloud_system\PHPMailer
 */
class SMTP {
  /**
   * SMTP server port.
   *
   * @var int
   */
  public $smtpPort = 25;

  /**
   * SMTP reply line ending.
   *
   * @var string
   */
  public $CRLF = "\r\n";

  /**
   * Sets whether debugging is turned on.
   *
   * @var bool
   */
  public $doDebug;

  /**
   * Sets VERP use on/off (default is off).
   *
   * @var bool
   */
  public $doVerp = FALSE;

  /**
   * The socket to the server.
   *
   * @var object
   */
  private $smtpConn;

  /**
   * Error if any on the last call.
   *
   * @var string
   */
  private $error;

  /**
   * The reply the server sent to us for HELO.
   *
   * @var string
   */
  private $heloRply;

  /**
   * Initialize the class so that the data is in a known state.
   */
  public function __construct() {
    $this->smtpConn = 0;
    $this->error = NULL;
    $this->heloRply = NULL;
    $this->doDebug = 0;
  }

  /**
   * Connect to the server specified on the port specified.
   *
   * If the port is not specified use the default smtpPort.
   *
   * If tval is specified then a connection will try and be
   * established with the server for that number of seconds.
   *
   * If tval is not specified the default is 30 seconds to
   * try on the connection.
   *
   * SMTP CODE SUCCESS: 220
   * SMTP CODE FAILURE: 421.
   *
   * @param string $host
   *   The host of the server.
   * @param int $port
   *   The port to use.
   * @param int $tval
   *   Give up after ? secs.
   *
   * @return bool
   *   The connect object.
   */
  public function connect($host, $port = 0, $tval = 30) {
    // Set the error val to NULL so there is no confusion.
    $this->error = NULL;

    // Make sure we are __not__ connected.
    if ($this->connected()) {
      // Already connected, generate error.
      $this->error = ["error" => "Already connected to a server"];
      return FALSE;
    }

    if (empty($port)) {
      $port = $this->smtpPort;
    }

    // Connect to the smtp server.
    $this->smtpConn = @fsockopen($host, $port, $errno, $errstr, $tval);
    // Verify we connected properly.
    if (empty($this->smtpConn)) {
      $this->error = [
        "error" => "Failed to connect to server",
        "errno" => $errno,
        "errstr" => $errstr,
      ];
      if ($this->doDebug >= 1) {
        echo "SMTP -> ERROR: " . $this->error["error"] . ": $errstr ($errno)" . $this->CRLF . '<br />';
      }
      return FALSE;
    }

    // SMTP server can take longer to respond,
    // give longer timeout for first read
    // Windows does not have support for this timeout function.
    if (substr(PHP_OS, 0, 3) != "WIN") {
      socket_set_timeout($this->smtpConn, $tval, 0);
    }

    // Get any announcement.
    $announce = $this->getLines();

    if ($this->doDebug >= 2) {
      echo "SMTP -> FROM SERVER:" . $announce . $this->CRLF . '<br />';
    }

    return TRUE;
  }

  /**
   * Initiate a TLS communication with the server.
   *
   * SMTP CODE 220 Ready to start TLS
   * SMTP CODE 501 Syntax error (no parameters allowed)
   * SMTP CODE 454 TLS not available due to temporary reason.
   *
   * @return bool
   *   True if success, otherwise False.
   */
  public function startTls() {
    // To avoid confusion.
    $this->error = NULL;

    if (!$this->connected()) {
      $this->error = ["error" => "Called startTls() without being connected"];
      return FALSE;
    }

    fwrite($this->smtpConn, "STARTTLS" . $this->CRLF);

    $rply = $this->getLines();
    $code = substr($rply, 0, 3);

    if ($this->doDebug >= 2) {
      echo "SMTP -> FROM SERVER:" . $rply . $this->CRLF . '<br />';
    }

    if ($code != 220) {
      $this->error = [
        "error" => "STARTTLS not accepted from server",
        "smtp_code" => $code,
        "smtp_msg" => substr($rply, 4),
      ];
      if ($this->doDebug >= 1) {
        echo "SMTP -> ERROR: " . $this->error["error"] . ": " . $rply . $this->CRLF . '<br />';
      }
      return FALSE;
    }

    // Begin encrypted connection.
    if (!stream_socket_enable_crypto($this->smtpConn, TRUE, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Performs SMTP authentication.
   *
   * Must be run after running the Hello() method.
   * Returns TRUE if successfully authenticated.
   *
   * @param string $username
   *   The username of authenticated.
   * @param string $password
   *   The password of authenticated.
   *
   * @return bool
   *   True if authorized, otherwise False.
   */
  public function authenticate($username, $password) {
    // Start authentication.
    fwrite($this->smtpConn, "AUTH LOGIN" . $this->CRLF);

    $rply = $this->getLines();
    $code = substr($rply, 0, 3);

    if ($code != 334) {
      $this->error = [
        "error" => "AUTH not accepted from server",
        "smtp_code" => $code,
        "smtp_msg" => substr($rply, 4),
      ];
      if ($this->doDebug >= 1) {
        echo "SMTP -> ERROR: " . $this->error["error"] . ": " . $rply . $this->CRLF . '<br />';
      }
      return FALSE;
    }

    // Send encoded username.
    fwrite($this->smtpConn, base64_encode($username) . $this->CRLF);

    $rply = $this->getLines();
    $code = substr($rply, 0, 3);

    if ($code != 334) {
      $this->error = [
        "error" => "Username not accepted from server",
        "smtp_code" => $code,
        "smtp_msg" => substr($rply, 4),
      ];
      if ($this->doDebug >= 1) {
        echo "SMTP -> ERROR: " . $this->error["error"] . ": " . $rply . $this->CRLF . '<br />';
      }
      return FALSE;
    }

    // Send encoded password.
    fwrite($this->smtpConn, base64_encode($password) . $this->CRLF);

    $rply = $this->getLines();
    $code = substr($rply, 0, 3);

    if ($code != 235) {
      $this->error = [
        "error" => "Password not accepted from server",
        "smtp_code" => $code,
        "smtp_msg" => substr($rply, 4),
      ];
      if ($this->doDebug >= 1) {
        echo "SMTP -> ERROR: " . $this->error["error"] . ": " . $rply . $this->CRLF . '<br />';
      }
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Returns TRUE if connected to a server otherwise FALSE.
   *
   * @return bool
   *   True if is connected, otherwise False.
   */
  public function connected() {
    if (!empty($this->smtpConn)) {
      $sock_status = socket_get_status($this->smtpConn);
      if ($sock_status["eof"]) {
        // The socket is valid but we are not connected.
        if ($this->doDebug >= 1) {
          echo "SMTP -> NOTICE:" . $this->CRLF . "EOF caught while checking if connected";
        }
        $this->close();
        return FALSE;
      }
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Closes the socket and cleans up the state of the class.
   *
   * It is not considered good to use this function without
   * first trying to use QUIT.
   */
  public function close() {
    // There is no confusion.
    $this->error = NULL;
    $this->heloRply = NULL;
    if (!empty($this->smtpConn)) {
      // Close the connection and cleanup.
      fclose($this->smtpConn);
      $this->smtpConn = 0;
    }
  }

  /**
   * Issues a data command and sends the msg_data to the server.
   *
   * Finializing the mail transaction.
   * $msg_data is the message that is to be send with the headers.
   * Each header needs to be on a single line followed by a <CRLF>
   * with the message headers and the message body being seperated
   * by and additional <CRLF>.
   *
   * Implements rfc 821: DATA <CRLF>.
   *
   * SMTP CODE INTERMEDIATE: 354
   *     [data]
   *     <CRLF>.<CRLF>
   *     SMTP CODE SUCCESS: 250
   *     SMTP CODE FAILURE: 552,554,451,452
   * SMTP CODE FAILURE: 451,554
   * SMTP CODE ERROR  : 500,501,503,421.
   *
   * @return bool
   *   True if get stream data, otherwise False.
   */
  public function data($msg_data) {
    // No confusion is caused.
    $this->error = NULL;

    if (!$this->connected()) {
      $this->error = [
        "error" => "Called data() without being connected",
      ];
      return FALSE;
    }

    fwrite($this->smtpConn, "DATA" . $this->CRLF);

    $rply = $this->getLines();
    $code = substr($rply, 0, 3);

    if ($this->doDebug >= 2) {
      echo "SMTP -> FROM SERVER:" . $rply . $this->CRLF . '<br />';
    }

    if ($code != 354) {
      $this->error = [
        "error" => "DATA command not accepted from server",
        "smtp_code" => $code,
        "smtp_msg" => substr($rply, 4),
      ];
      if ($this->doDebug >= 1) {
        echo "SMTP -> ERROR: " . $this->error["error"] . ": " . $rply . $this->CRLF . '<br />';
      }
      return FALSE;
    }

    /*
     * The server is ready to accept data
     * according to rfc 821 we should not send more than 1000
     * including the CRLF
     * characters on a single line so we will break the data up
     * into lines by \r and/or \n then if needed we will break
     * each of those into smaller lines to fit within the limit.
     *
     * in addition we will be looking for lines that start with
     * a period '.' and append and additional period '.' to that
     * line.
     *
     * NOTE: this does not count towards limit.
     */

    // Normalize the line breaks so we know the explode works.
    $msg_data = str_replace("\r\n", "\n", $msg_data);
    $msg_data = str_replace("\r", "\n", $msg_data);
    $lines = explode("\n", $msg_data);

    /*
     * We need to find a good way to determine is headers are
     * in the msg_data or if it is a straight msg body
     * currently I am assuming rfc 822 definitions of msg headers
     * and if the first field of the first line (':' sperated)
     * does not contain a space then it _should_ be a header
     * and we can process all lines before a blank "" line as
     * headers.
     */
    $field = substr($lines[0], 0, strpos($lines[0], ":"));
    $in_headers = FALSE;
    if (!empty($field) && !strstr($field, " ")) {
      $in_headers = TRUE;
    }

    // Used below; set here for ease in change.
    $max_line_length = 998;

    while (list(, $line) = @each($lines)) {
      $lines_out = NULL;
      if ($line == "" && $in_headers) {
        $in_headers = FALSE;
      }
      // Ok we need to break this line up into several smaller lines.
      while (strlen($line) > $max_line_length) {
        $pos = strrpos(substr($line, 0, $max_line_length), " ");
        // Patch to fix DOS attack.
        if (!$pos) {
          $pos = $max_line_length - 1;
          $lines_out[] = substr($line, 0, $pos);
          $line = substr($line, $pos);
        }
        else {
          $lines_out[] = substr($line, 0, $pos);
          $line = substr($line, $pos + 1);
        }

        /* If processing headers add a LWSP-char to the front of new line
         * rfc 822 on long msg headers.
         */
        if ($in_headers) {
          $line = "\t" . $line;
        }
      }
      $lines_out[] = $line;

      // Send the lines to the server.
      while (list(, $line_out) = @each($lines_out)) {
        if (strlen($line_out) > 0) {
          if (substr($line_out, 0, 1) == ".") {
            $line_out = "." . $line_out;
          }
        }
        fwrite($this->smtpConn, $line_out . $this->CRLF);
      }
    }

    // Message data has been sent.
    fwrite($this->smtpConn, $this->CRLF . "." . $this->CRLF);

    $rply = $this->getLines();
    $code = substr($rply, 0, 3);

    if ($this->doDebug >= 2) {
      echo "SMTP -> FROM SERVER:" . $rply . $this->CRLF . '<br />';
    }

    if ($code != 250) {
      $this->error = [
        "error" => "DATA not accepted from server",
        "smtp_code" => $code,
        "smtp_msg" => substr($rply, 4),
      ];
      if ($this->doDebug >= 1) {
        echo "SMTP -> ERROR: " . $this->error["error"] . ": " . $rply . $this->CRLF . '<br />';
      }
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Sends the HELO command to the smtp server.
   *
   * This makes sure that we and the server are in
   * the same known state.
   *
   * Implements from rfc 821: HELO <SP> <domain> <CRLF>.
   *
   * SMTP CODE SUCCESS: 250
   * SMTP CODE ERROR  : 500, 501, 504, 421.
   *
   * @return bool
   *   True if send ok, otherwise False.
   */
  public function hello($host = '') {
    // No confusion is caused.
    $this->error = NULL;

    if (!$this->connected()) {
      $this->error = [
        "error" => "Called hello() without being connected",
      ];
      return FALSE;
    }

    // If hostname for HELO was not specified send default.
    if (empty($host)) {
      // Determine appropriate default to send to server.
      $host = "localhost";
    }

    // Send extended hello first (RFC 2821).
    if (!$this->sendHello("EHLO", $host)) {
      if (!$this->sendHello("HELO", $host)) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Sends a HELO/EHLO command.
   *
   * @return bool
   *   True if send ok, otherwise False.
   */
  private function sendHello($hello, $host) {
    fwrite($this->smtpConn, $hello . " " . $host . $this->CRLF);

    $rply = $this->getLines();
    $code = substr($rply, 0, 3);

    if ($this->doDebug >= 2) {
      echo "SMTP -> FROM SERVER: " . $rply . $this->CRLF . '<br />';
    }

    if ($code != 250) {
      $this->error = [
        "error" => $hello . " not accepted from server",
        "smtp_code" => $code,
        "smtp_msg" => substr($rply, 4),
      ];
      if ($this->doDebug >= 1) {
        echo "SMTP -> ERROR: " . $this->error["error"] . ": " . $rply . $this->CRLF . '<br />';
      }
      return FALSE;
    }

    $this->heloRply = $rply;

    return TRUE;
  }

  /**
   * Starts a mail transaction from the email address specified in $from.
   *
   * Returns TRUE if successful or FALSE otherwise.
   * If True the mail transaction is started and then one or more recipient
   * commands may be called followed by a Data command.
   *
   * Implements rfc 821: MAIL <SP> FROM:<reverse-path> <CRLF>.
   *
   * SMTP CODE SUCCESS: 250
   * SMTP CODE SUCCESS: 552,451,452
   * SMTP CODE SUCCESS: 500,501,421.
   *
   * @return bool
   *   True if start ok, otherwise False.
   */
  public function mail($from) {
    // No confusion is caused.
    $this->error = NULL;

    if (!$this->connected()) {
      $this->error = [
        "error" => "Called mail() without being connected",
      ];
      return FALSE;
    }

    $useVerp = ($this->doVerp ? "XVERP" : "");
    fwrite($this->smtpConn, "MAIL FROM:<" . $from . ">" . $useVerp . $this->CRLF);

    $rply = $this->getLines();
    $code = substr($rply, 0, 3);

    if ($this->doDebug >= 2) {
      echo "SMTP -> FROM SERVER:" . $rply . $this->CRLF . '<br />';
    }

    if ($code != 250) {
      $this->error = [
        "error" => "MAIL not accepted from server",
        "smtp_code" => $code,
        "smtp_msg" => substr($rply, 4),
      ];
      if ($this->doDebug >= 1) {
        echo "SMTP -> ERROR: " . $this->error["error"] . ": " . $rply . $this->CRLF . '<br />';
      }
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Sends the quit command to the server and then closes the socket.
   *
   * If there is no error or the $close_on_error argument is TRUE.
   *
   * Implements from rfc 821: QUIT <CRLF>.
   *
   * SMTP CODE SUCCESS: 221
   * SMTP CODE ERROR  : 500.
   *
   * @return bool
   *   True if quit ok, otherwise False.
   */
  public function quit($close_on_error = TRUE) {
    $this->error = NULL;

    if (!$this->connected()) {
      $this->error = [
        "error" => "Called quit() without being connected",
      ];
      return FALSE;
    }

    // Send the quit command to the server.
    fwrite($this->smtpConn, "quit" . $this->CRLF);

    // Get any good-bye messages.
    $byemsg = $this->getLines();

    if ($this->doDebug >= 2) {
      echo "SMTP -> FROM SERVER:" . $byemsg . $this->CRLF . '<br />';
    }

    $rval = TRUE;
    $e = NULL;

    $code = substr($byemsg, 0, 3);
    if ($code != 221) {
      // Use e as a tmp var cause Close will overwrite $this->error.
      $e = [
        "error" => "SMTP server rejected quit command",
        "smtp_code" => $code,
        "smtp_rply" => substr($byemsg, 4),
      ];
      $rval = FALSE;
      if ($this->doDebug >= 1) {
        echo "SMTP -> ERROR: " . $e["error"] . ": " . $byemsg . $this->CRLF . '<br />';
      }
    }

    if (empty($e) || $close_on_error) {
      $this->close();
    }

    return $rval;
  }

  /**
   * Sends the command RCPT to the SMTP server with the argument of $to.
   *
   * Returns TRUE if the recipient was accepted FALSE if it was rejected.
   *
   * Implements from rfc 821: RCPT <SP> TO:<forward-path> <CRLF>.
   *
   * SMTP CODE SUCCESS: 250,251
   * SMTP CODE FAILURE: 550,551,552,553,450,451,452
   * SMTP CODE ERROR  : 500,501,503,421.
   *
   * @return bool
   *   True if recipt ok, otherwise False.
   */
  public function recipient($to) {
    // No confusion is caused.
    $this->error = NULL;

    if (!$this->connected()) {
      $this->error = [
        "error" => "Called recipient() without being connected",
      ];
      return FALSE;
    }

    fwrite($this->smtpConn, "RCPT TO:<" . $to . ">" . $this->CRLF);

    $rply = $this->getLines();
    $code = substr($rply, 0, 3);

    if ($this->doDebug >= 2) {
      echo "SMTP -> FROM SERVER:" . $rply . $this->CRLF . '<br />';
    }

    if ($code != 250 && $code != 251) {
      $this->error = [
        "error" => "RCPT not accepted from server",
        "smtp_code" => $code,
        "smtp_msg" => substr($rply, 4),
      ];
      if ($this->doDebug >= 1) {
        echo "SMTP -> ERROR: " . $this->error["error"] . ": " . $rply . $this->CRLF . '<br />';
      }
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Sends the RSET command to abort and transaction.
   *
   * Returns TRUE if successful FALSE otherwise.
   *
   * Implements rfc 821: RSET <CRLF>.
   *
   * SMTP CODE SUCCESS: 250
   * SMTP CODE ERROR  : 500,501,504,421.
   *
   * @return bool
   *   True if send ok, otherwise False.
   */
  public function reset() {
    $this->error = NULL;

    if (!$this->connected()) {
      $this->error = [
        "error" => "Called reset() without being connected",
      ];
      return FALSE;
    }

    fwrite($this->smtpConn, "RSET" . $this->CRLF);

    $rply = $this->getLines();
    $code = substr($rply, 0, 3);

    if ($this->doDebug >= 2) {
      echo "SMTP -> FROM SERVER:" . $rply . $this->CRLF . '<br />';
    }

    if ($code != 250) {
      $this->error = [
        "error" => "RSET failed",
        "smtp_code" => $code,
        "smtp_msg" => substr($rply, 4),
      ];
      if ($this->doDebug >= 1) {
        echo "SMTP -> ERROR: " . $this->error["error"] . ": " . $rply . $this->CRLF . '<br />';
      }
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Starts a mail transaction from the email address specified in $from.
   *
   * Returns TRUE if successful or FALSE otherwise.
   * If True the mail transaction is started and then one or more recipient
   * commands may be called followed by a Data command.
   * This command will send the message to the users terminal if they are logged
   * in and send them an email.
   *
   * Implements rfc 821: SAML <SP> FROM:<reverse-path> <CRLF>.
   *
   * SMTP CODE SUCCESS: 250
   * SMTP CODE SUCCESS: 552,451,452
   * SMTP CODE SUCCESS: 500,501,502,421.
   *
   * @return bool
   *   True if send mail ok, otherwise False.
   */
  public function sendAndMail($from) {
    // No confusion is caused.
    $this->error = NULL;

    if (!$this->connected()) {
      $this->error = [
        "error" => "Called sendAndMail() without being connected",
      ];
      return FALSE;
    }

    fwrite($this->smtpConn, "SAML FROM:" . $from . $this->CRLF);

    $rply = $this->getLines();
    $code = substr($rply, 0, 3);

    if ($this->doDebug >= 2) {
      echo "SMTP -> FROM SERVER:" . $rply . $this->CRLF . '<br />';
    }

    if ($code != 250) {
      $this->error = [
        "error" => "SAML not accepted from server",
        "smtp_code" => $code,
        "smtp_msg" => substr($rply, 4),
      ];
      if ($this->doDebug >= 1) {
        echo "SMTP -> ERROR: " . $this->error["error"] . ": " . $rply . $this->CRLF . '<br />';
      }
      return FALSE;
    }
    return TRUE;
  }

  /**
   * This is an optional command for SMTP that this class does not support.
   *
   * This method is here to make the RFC821 Definition
   * complete for this class and __may__ be implimented in the future.
   *
   * Implements from rfc 821: TURN <CRLF>.
   *
   * SMTP CODE SUCCESS: 250
   * SMTP CODE FAILURE: 502
   * SMTP CODE ERROR  : 500, 503.
   *
   * @return bool
   *   If error occured, False give.
   */
  public function turn() {
    $this->error = [
      "error" => "This method, TURN, of the SMTP is not implemented",
    ];
    if ($this->doDebug >= 1) {
      echo "SMTP -> NOTICE: " . $this->error["error"] . $this->CRLF . '<br />';
    }
    return FALSE;
  }

  /**
   * Get the current error.
   *
   * @return array
   *   An error message.
   */
  public function getError() {
    return $this->error;
  }

  /**
   * Read in as many lines as possible.
   *
   * Either before eof or socket timeout occurs on the operation.
   *
   * With SMTP we can tell if we have more lines to read if the
   * 4th character is '-' symbol.
   *
   * If it is a space then we don't need to read anything else.
   *
   * @return string
   *   An many lines.
   */
  private function getLines() {
    $data = "";
    while ($str = @fgets($this->smtpConn, 515)) {
      if ($this->doDebug >= 4) {
        echo "SMTP -> getLines(): \$data was \"$data\"" . $this->CRLF . '<br />';
        echo "SMTP -> getLines(): \$str is \"$str\"" . $this->CRLF . '<br />';
      }
      $data .= $str;
      if ($this->doDebug >= 4) {
        echo "SMTP -> getLines(): \$data is \"$data\"" . $this->CRLF . '<br />';
      }
      // If 4th character is a space, we are done reading, break the loop.
      if (substr($str, 3, 1) == " ") {
        break;
      }
    }
    return $data;
  }

}
