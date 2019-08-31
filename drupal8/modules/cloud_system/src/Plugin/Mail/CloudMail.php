<?php

namespace Drupal\cloud_system\Plugin\Mail;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Mail\MailInterface;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\cloud_system\PHPMailer\PHPMailer;

/**
 * Modify the drupal mail system to use smtp when sending emails.
 *
 * Include the option to choose between plain text or HTML.
 *
 * @Mail(
 *   id = "CloudMail",
 *   label = @Translation("Cloud Mailer"),
 *   description = @Translation("Sends the message, using SMTP.")
 * )
 */
class CloudMail implements MailInterface {
  protected $AllowHtml;

  private $debugging = FALSE;

  private $username = 'services@verycloud.cn';
  private $password = 'nicaiba_88';

  private $smtpFrom = 'services@verycloud.cn';
  private $siteMail = 'services@verycloud.cn';

  private $protocol = '';

  private $smtpQueue = TRUE;

  private $smtpHost = 'mail.verycloud.cn';
  private $smtpPort = 25;

  /**
   * Concatenate and wrap the e-mail body for either plain-text or HTML emails.
   *
   * @param array $message
   *   A message array, as described in hook_mail_alter().
   *
   * @return array
   *   The formatted $message.
   */
  public function format(array $message) {
    if (isset($message['conf']) && !empty($message['conf'])) {
      $conf = $message['conf'];
      $this->smtpHost = $conf['smtp_server'];
      $this->username = $conf['smtp_user'];
      $this->smtpFrom = $conf['smtp_user'];
      $this->siteMail = $conf['smtp_user'];
      $this->password = $conf['smtp_pwd'];
      $this->smtpPort = $conf['smtp_port'];

      $message['sender'] = $conf['smtp_user'];
      $message['logo'] = isset($conf['logo']) ? $conf['logo'] : '';
      $message['custom_header'] = isset($conf['header']) ? $conf['header'] : '';
      $message['custom_footer'] = isset($conf['footer']) ? $conf['footer'] : '';
      unset($message['conf']);
    }

    if (isset($message['ssl_protocol']) && !empty($message['ssl_protocol'])) {
      $this->protocol = $message['ssl_protocol'];
    }

    $this->AllowHtml = TRUE;
    // Join the body array into one string.
    $render = [
      '#theme' => isset($message['params']['theme']) ? $message['params']['theme'] : 'cloud_mail',
      '#message' => $message,
    ];

    $message['body'] = \Drupal::service('renderer')->renderRoot($render);

    if ($this->AllowHtml == 0) {
      // Convert any HTML to plain-text.
      $message['body'] = MailFormatHelper::htmlToText($message['body']);
      // Wrap the mail body for sending.
      $message['body'] = MailFormatHelper::wrapMail($message['body']);
    }
    return $message;
  }

  /**
   * Send the e-mail message.
   *
   * See
   * http://api.drupal.org/api/drupal/includes--mail.inc/interface/MailSystemInterface/7.
   *
   * @param array $message
   *   A message array, as described in hook_mail_alter().
   *
   * @return bool
   *   TRUE if the mail was successfully accepted, otherwise FALSE.
   * @throws \Drupal\cloud_system\Plugin\Exception\PHPMailerException
   */
  public function mail(array $message) {
    $id = $message['id'];
    $to = $message['to'];
    $from = $message['from'];
    $body = $message['body'];
    $headers = $message['headers'];
    $subject = $message['subject'];
    $sender = $message['sender'] ?? $this->username;
    $isQueue = isset($message['isQueue']) ? (int) $message['isQueue'] : 1;

    // Don't send email if body is empty.
    if (empty($body)) {
      return FALSE;
    }

    // Create a new PHPMailer object - autoloaded from registry.
    $mailer = new PHPMailer();

    // Turn on debugging, if requested.
    if ($this->debugging) {
      $mailer->SMTPDebug = TRUE;
    }

    if (!$isQueue) {
      $this->smtpQueue = FALSE;
    }

    if ($this->smtpQueue) {
      \Drupal::logger('smtp')->notice('Queue sending mail to: @to', ['@to' => $to]);
      // Send smtp.
      $queue = \Drupal::queue('smtp_queue', TRUE);
      $message['body'] = isset($message['params']['body']) ? $message['params']['body'][0] : $body;
      $queue->createItem($message);
      return TRUE;
    }

    // Set the from name.
    $from_name = $message['from_name'] ?? '一站式云平台';
    if (empty($from_name)) {
      // If value is not defined in settings, use site_name.
      $from_name = \Drupal::config('system.site')->get('name');
    }

    // Hack to fix reply-to issue.
    $properfrom = $this->siteMail;
    if (!empty($properfrom)) {
      $headers['From'] = $properfrom;
    }
    if (!isset($headers['Reply-To']) || empty($headers['Reply-To'])) {
      if (strpos($from, '<')) {
        $reply = preg_replace('/>.*/', '', preg_replace('/.*</', '', $from));
      }
      else {
        $reply = $from;
      }
      $headers['Reply-To'] = $reply;
    }

    // Blank value will let the e-mail address appear.
    if ($from == NULL || $from == '') {
      // If from e-mail address is blank, use smtpFrom config option.
      if (($from = $this->smtpFrom)) {
        // If smtpFrom config option is blank, use site_email.
        if (($from = $this->siteMail) == '') {
          \Drupal::logger('smtp')->error('There is no submitted from address.');
          return FALSE;
        }
      }
    }

    // Defines the From value to what we expect.
    $mailer->From = $from;
    $mailer->FromName = $from_name;
    $mailer->Sender = isset($message['sender']) ? $message['sender'] : $sender;

    // Create the list of 'To:' recipients.
    $torecipients = explode(',', $to);
    foreach ($torecipients as $torecipient) {
      $to_comp = $this->getComponents($torecipient);
      $mailer->addAddress($to_comp['email'], $to_comp['name']);
    }

    // Parse the headers of the message and set the PHPMailer object's settings
    // accordingly.
    foreach ($headers as $key => $value) {
      // watchdog('error', 'Key: ' . $key . ' Value: ' . $value);.
      switch (strtolower($key)) {
        case 'from':
          if ($from == NULL or $from == '') {
            // If a from value was already given, then set based on header.
            // Should be the most common situation since drupal_mail moves the
            // from to headers.
            $from           = $value;
            $mailer->From     = $value;
            // Then from can be out of sync with from_name !
            $mailer->FromName = '';
            $mailer->Sender   = $value;
          }
          break;

        case 'content-type':
          // Parse several values on the Content-type header,
          // storing them in an array like
          // key=value -> $vars['key']='value'.
          $vars = explode(';', $value);
          foreach ($vars as $i => $var) {
            if ($cut = strpos($var, '=')) {
              $new_var = trim(strtolower(substr($var, $cut + 1)));
              $new_key = trim(substr($var, 0, $cut));
              unset($vars[$i]);
              $vars[$new_key] = $new_var;
            }
          }
          // Set the charset based on the provided value,
          // otherwise set it to UTF-8 (which is Drupals internal default).
          $mailer->CharSet = isset($vars['charset']) ? $vars['charset'] : 'UTF-8';
          // If $vars is empty then set an empty value at index 0
          // to avoid a PHP warning in the next statement.
          $vars[0] = isset($vars[0]) ? $vars[0] : '';

          switch ($vars[0]) {
            case 'text/plain':
              // The message includes only a plain text part.
              $mailer->isHtml(TRUE);
              $content_type = 'text/html';
              break;

            case 'text/html':
              // The message includes only an HTML part.
              $mailer->isHtml(TRUE);
              $content_type = 'text/html';
              break;

            case 'multipart/related':
              // Get the boundary ID from the Content-Type header.
              $boundary = $this->getSubstring($value, 'boundary', '"', '"');

              // The message includes an HTML part w/inline attachments.
              $mailer->ContentType = $content_type = 'multipart/related; boundary="' . $boundary . '"';
              break;

            case 'multipart/alternative':
              // The message includes both a plain text and an HTML part.
              $mailer->ContentType = $content_type = 'multipart/alternative';

              // Get the boundary ID from the Content-Type header.
              $boundary = $this->getSubstring($value, 'boundary', '"', '"');
              break;

            case 'multipart/mixed':
              // The message includes one or more attachments.
              $mailer->ContentType = $content_type = 'multipart/mixed';

              // Get the boundary ID from the Content-Type header.
              $boundary = $this->getSubstring($value, 'boundary', '"', '"');
              break;

            default:
              // Everything else is unsuppored by PHPMailer.
              \Drupal::logger('smtp')->error('The @header of your message is not supported by PHPMailer and will be sent as text/plain instead.', ['@header' => "Content-Type: $value"]);
              // Force the Content-Type to be text/plain.
              $mailer->isHtml(FALSE);
              $content_type = 'text/plain';
          }
          break;

        case 'reply-to':
          // Only add a "reply-to" if it's not the same as "return-path".
          if ($value != $headers['Return-Path']) {
            $reply_to_comp = $this->getComponents($value);
            $mailer->addReplyTo($reply_to_comp['email'], $reply_to_comp['name']);
          }
          break;

        case 'content-transfer-encoding':
          $mailer->Encoding = $value;
          break;

        case 'return-path':
          $mailer->Sender = $value;
          break;

        case 'mime-version':
        case 'x-mailer':
          // Let PHPMailer specify these.
          break;

        case 'errors-to':
          $mailer->addCustomHeader('Errors-To: ' . $value);
          break;

        case 'cc':
          $ccrecipients = explode(',', $value);
          foreach ($ccrecipients as $ccrecipient) {
            $cc_comp = $this->getComponents($ccrecipient);
            $mailer->addCc($cc_comp['email'], $cc_comp['name']);
          }
          break;

        case 'bcc':
          $bccrecipients = explode(',', $value);
          foreach ($bccrecipients as $bccrecipient) {
            $bcc_comp = $this->getComponents($bccrecipient);
            $mailer->addBcc($bcc_comp['email'], $bcc_comp['name']);
          }
          break;

        default:
          // The header key is not special - add it as is.
          $mailer->addCustomHeader($key . ': ' . $value);
      }
    }

    /*
     * Need to figure out the following.
     *
     * Add one last header item, but not if it has already been added.
     * $errors_to = FALSE;
     * foreach ($mailer->CustomHeader as $custom_header) {
     *   if ($custom_header[0] = '') {
     *     $errors_to = TRUE;
     *   }
     * }
     * if ($errors_to) {
     *   $mailer->addCustomHeader('Errors-To: '. $from);
     * }
     */
    // Add the message's subject.
    $mailer->Subject = $subject;

    // Processes the message's body.
    switch ($content_type) {
      case 'multipart/related':
        $mailer->Body = $body;
        // TODO: Figure out if there is anything more to handling this type.
        break;

      case 'multipart/alternative':
        // Split the body based on the boundary ID.
        $body_parts = $this->boundarySplit($body, $boundary);
        foreach ($body_parts as $body_part) {
          // If plain/text within the body part, add it to $mailer->AltBody.
          if (strpos($body_part, 'text/plain')) {
            // Clean up the text.
            $body_part = trim($this->removeHeaders(trim($body_part)));
            // Include it as part of the mail object.
            $mailer->AltBody = $body_part;
          }
          // If plain/html within the body part, add it to $mailer->Body.
          elseif (strpos($body_part, 'text/html')) {
            // Clean up the text.
            $body_part = trim($this->removeHeaders(trim($body_part)));
            // Include it as part of the mail object.
            $mailer->Body = $body_part;
          }
        }
        break;

      case 'multipart/mixed':
        // Split the body based on the boundary ID.
        $body_parts = $this->boundarySplit($body, $boundary);

        // Determine if there is an HTML part for
        // when adding the plain text part.
        $text_plain = FALSE;
        $text_html  = FALSE;
        foreach ($body_parts as $body_part) {
          if (strpos($body_part, 'text/plain')) {
            $text_plain = TRUE;
          }
          if (strpos($body_part, 'text/html')) {
            $text_html = TRUE;
          }
        }

        foreach ($body_parts as $body_part) {
          // If test/plain within the body part, add it to either
          // $mailer->AltBody or $mailer->Body, depending on whether there is
          // also a text/html part ot not.
          if (strpos($body_part, 'multipart/alternative')) {
            // Get boundary ID from the Content-Type header.
            $boundary2 = $this->getSubstring($body_part, 'boundary', '"', '"');
            // Clean up the text.
            $body_part = trim($this->removeHeaders(trim($body_part)));
            // Split the body based on the boundary ID.
            $body_parts2 = $this->boundarySplit($body_part, $boundary2);

            foreach ($body_parts2 as $body_part2) {
              // If plain/text within the body part, add it to $mailer->AltBody.
              if (strpos($body_part2, 'text/plain')) {
                // Clean up the text.
                $body_part2 = trim($this->removeHeaders(trim($body_part2)));
                // Include it as part of the mail object.
                $mailer->AltBody = $body_part2;
                $mailer->ContentType = 'multipart/mixed';
              }
              // If plain/html within the body part, add it to $mailer->Body.
              elseif (strpos($body_part2, 'text/html')) {
                // Get the encoding.
                $body_part2_encoding = $this->getSubstring($body_part2, 'Content-Transfer-Encoding', ' ', "\n");
                // Clean up the text.
                $body_part2 = trim($this->removeHeaders(trim($body_part2)));
                // Check whether the encoding is base64, and if so, decode it.
                if (Unicode::strtolower($body_part2_encoding) == 'base64') {
                  // Include it as part of the mail object.
                  $mailer->Body = base64_decode($body_part2);
                  // Ensure the whole message is recoded in the base64 format.
                  $mailer->Encoding = 'base64';
                }
                else {
                  // Include it as part of the mail object.
                  $mailer->Body = $body_part2;
                }
                $mailer->ContentType = 'multipart/mixed';
              }
            }
          }
          // If text/plain within the body part, add it to $mailer->Body.
          elseif (strpos($body_part, 'text/plain')) {
            // Clean up the text.
            $body_part = trim($this->removeHeaders(trim($body_part)));

            if ($text_html) {
              $mailer->AltBody = $body_part;
              $mailer->isHtml(TRUE);
              $mailer->ContentType = 'multipart/mixed';
            }
            else {
              $mailer->Body = $body_part;
              $mailer->isHtml(FALSE);
              $mailer->ContentType = 'multipart/mixed';
            }
          }
          // If text/html within the body part, add it to $mailer->Body.
          elseif (strpos($body_part, 'text/html')) {
            // Clean up the text.
            $body_part = trim($this->removeHeaders(trim($body_part)));
            // Include it as part of the mail object.
            $mailer->Body = $body_part;
            $mailer->isHtml(TRUE);
            $mailer->ContentType = 'multipart/mixed';
          }
          // Add the attachment.
          elseif (strpos($body_part, 'Content-Disposition: attachment;') && !isset($message['params']['attachments'])) {
            $file_path     = $this->getSubstring($body_part, 'filename=', '"', '"');
            $file_name     = $this->getSubstring($body_part, ' name=', '"', '"');
            $file_encoding = $this->getSubstring($body_part, 'Content-Transfer-Encoding', ' ', "\n");
            $file_type     = $this->getSubstring($body_part, 'Content-Type', ' ', ';');

            if (file_exists($file_path)) {
              if (!$mailer->addAttachment($file_path, $file_name, $file_encoding, $file_type)) {
                \Drupal::logger('smtp')->error('Attahment could not be found or accessed.');
              }
            }
            else {
              // Clean up the text.
              $body_part = trim($this->removeHeaders(trim($body_part)));

              if (Unicode::strtolower($file_encoding) == 'base64') {
                $attachment = base64_decode($body_part);
              }
              elseif (Unicode::strtolower($file_encoding) == 'quoted-printable') {
                $attachment = quoted_printable_decode($body_part);
              }
              else {
                $attachment = $body_part;
              }

              $attachment_new_filename = drupal_tempnam('temporary://', 'smtp');
              $file_path = file_save_data($attachment, $attachment_new_filename, FILE_EXISTS_REPLACE);
              $real_path = \Drupal::service('file_system')->realpath($file_path->uri);

              if (!$mailer->addAttachment($real_path, $file_name)) {
                \Drupal::logger('smtp')->error('Attachment could not be found or accessed.');
              }
            }
          }
        }
        break;

      default:
        $mailer->Body = $body;
        break;
    }

    // Process mimemail attachments,which are prepared in mimemail_mail().
    if (isset($message['params']['attachments'])) {
      foreach ($message['params']['attachments'] as $attachment) {
        if (isset($attachment['filecontent'])) {
          $mailer->addStringAttachment($attachment['filecontent'], $attachment['filename'], 'base64', $attachment['filemime']);
        }
        if (isset($attachment['filepath'])) {
          $filename = isset($attachment['filename']) ? $attachment['filename'] : basename($attachment['filepath']);
          $filemime = isset($attachment['filemime']) ? $attachment['filemime'] : file_get_mimetype($attachment['filepath']);
          $mailer->addAttachment($attachment['filepath'], $filename, 'base64', $filemime);
        }
      }
    }

    // Set the authentication settings.
    $username = $this->username;
    $password = $this->password;

    // If username and password are given, use SMTP authentication.
    if ($username != '' && $password != '') {
      $mailer->SMTPAuth = TRUE;
      $mailer->Username = $username;
      $mailer->Password = $password;
    }

    // Set the protocol prefix for the smtp host.
    switch ($this->protocol) {
      case 'ssl':
        $mailer->SMTPSecure = 'ssl';
        break;

      case 'tls':
        $mailer->SMTPSecure = 'tls';
        break;

      default:
        $mailer->SMTPSecure = '';
    }

    // Set other connection settings.
    $mailer->Host = $this->smtpHost;
    $mailer->Port = $this->smtpPort;
    $mailer->Mailer = 'smtp';

    $mailerArr = [
      'mailer' => $mailer,
      'to' => $to,
      'from' => $from,
    ];

    return $this->smtpMailerSend($mailerArr);
  }

  /**
   * Send mail.
   *
   * @param array $variables
   *   Variables for mail server.
   *
   * @return bool
   *   True if the mail send ok, otherwise False.
   */
  private function smtpMailerSend($variables) {
    $mailer = $variables['mailer'];
    $to = $variables['to'];
    $from = $variables['from'];

    $logger = \Drupal::logger('smtp');

    // Let the people know what is going on.
    $logger->info('Sending mail to: @to', ['@to' => $to]);

    // Try to send e-mail. If it fails, set watchdog entry.
    if (!$mailer->send()) {
      $mail = \Drupal::config('cloud_system.email_config')->get('default_mail');
      \Drupal::service('cloud_system.base')->sendMail($mail, [
        'subject'  => '邮件发送异常',
        'body'     => json_encode($variables),
      ]);
      return FALSE;
    }

    $mailer->smtpClose();
    return TRUE;
  }

  /**
   * Splits the input into parts based on the given boundary.
   *
   * Swiped from Mail::MimeDecode, with modifications based on Drupal's coding
   * standards and this bug report: http://pear.php.net/bugs/bug.php?id=6495
   *
   * @param string $input
   *   A string containing the body text to parse.
   * @param string $boundary
   *   A string with the boundary string to parse on.
   *
   * @return string
   *   An array containing the resulting mime parts
   */
  protected function boundarySplit($input, $boundary) {
    $parts       = [];
    $bs_possible = substr($boundary, 2, -2);
    $bs_check    = '\"' . $bs_possible . '\"';

    if ($boundary == $bs_check) {
      $boundary = $bs_possible;
    }

    $tmp = explode('--' . $boundary, $input);

    for ($i = 1; $i < count($tmp); $i++) {
      if (trim($tmp[$i])) {
        $parts[] = $tmp[$i];
      }
    }

    return $parts;
  }

  /**
   * Strips the headers from the body part.
   *
   * @param string $input
   *   A string containing the body part to strip.
   *
   * @return string
   *   A string with the stripped body part.
   */
  protected function removeHeaders($input) {
    $part_array = explode("\n", $input);

    // Will strip these headers according to RFC2045.
    $headers_to_strip = [
      'Content-Type',
      'Content-Transfer-Encoding',
      'Content-ID',
      'Content-Disposition',
    ];
    $pattern = '/^(' . implode('|', $headers_to_strip) . '):/';

    while (count($part_array) > 0) {

      // Ignore trailing spaces/newlines.
      $line = rtrim($part_array[0]);

      // If the line starts with a known header string.
      if (preg_match($pattern, $line)) {
        $line = rtrim(array_shift($part_array));
        // Remove line containing matched header.
        // If line ends in a ';' and the next line
        // starts with four spaces, it's a continuation
        // of the header split onto the next line.
        // Continue removing lines while we have this condition.
        while (substr($line, -1) == ';' && count($part_array) > 0 && substr($part_array[0], 0, 4) == '    ') {
          $line = rtrim(array_shift($part_array));
        }
      }
      else {
        // No match header, must be past headers; stop searching.
        break;
      }
    }

    $output = implode("\n", $part_array);
    return $output;
  }

  /**
   * Returns a string that is contained within another string.
   *
   * Returns the string from within $source that is some where after $target
   * and is between $beginning_character and $ending_character.
   *
   * @param string $source
   *   A string containing the text to look through.
   * @param string $target
   *   A string containing the text in $source to start looking from.
   * @param string $beginning_character
   *   A string containing the character just before the sought after text.
   * @param string $ending_character
   *   A string containing the character just after the sought after text.
   *
   * @return string
   *   A string with the text found between the $beginning_character and the
   *   $ending_character.
   */
  protected function getSubstring($source, $target, $beginning_character, $ending_character) {
    $search_start     = strpos($source, $target) + 1;
    $first_character  = strpos($source, $beginning_character, $search_start) + 1;
    $second_character = strpos($source, $ending_character, $first_character) + 1;
    $substring        = substr($source, $first_character, $second_character - $first_character);
    $string_length    = strlen($substring) - 1;

    if ($substring[$string_length] == $ending_character) {
      $substring = substr($substring, 0, $string_length);
    }

    return $substring;
  }

  /**
   * Returns an array of name and email address from a string.
   *
   * @param string $input
   *   A string that contains different possible combinations of names and
   *   email address.
   *
   * @return array
   *   An array containing a name and an email address.
   */
  protected function getComponents($input) {
    $components = [
      'input' => $input,
      'name' => '',
      'email' => '',
    ];

    // If the input is a valid email address in its entirety, then there is
    // nothing to do, just return that.
    if (\Drupal::service('email.validator')->isValid(trim($input))) {
      $components['email'] = trim($input);
      return $components;
    }

    // Check if $input has one of the following formats, extract what we can:
    // some name <address@example.com>
    // "another name" <address@example.com>
    // <address@example.com>.
    if (preg_match('/^"?([^"\t\n]*)"?\s*<([^>\t\n]*)>$/', trim($input), $matches)) {
      $components['name'] = trim($matches[1]);
      $components['email'] = trim($matches[2]);
    }

    return $components;
  }

}
