<?php

namespace Drupal\cloud_system;

/**
 * Provides a phone number validator.
 * @see https://en.wikipedia.org/wiki/E.164
 */
class CloudSystemValidatePhoneNumber {
  /**
   * Type domestic.
   */
  const TYPE_DOMESTIC = 1;

  /**
   * Type american.
   */
  const TYPE_AMERICAN = 2;

  /**
   * Type international.
   */
  const TYPE_INTERNATIONAL = 3;

  /**
   * Type premium.
   */
  const TYPE_PREMIUM = 4;

  /**
   * Type unknown.
   */
  const TYPE_UNKNOWN = 5;

  /**
   * Type tollfree.
   */
  const TYPE_TOLLFREE = 6;

  /**
   * Type american invalid.
   */
  const TYPE_AMERICAN_INVALID = 7;

  /**
   * Type sip.
   */
  const TYPE_SIP = 8;

  /**
   * Analyze will alter the number to a normalized form.
   *
   * @param string $number
   *   The phone number.
   *
   * @return int
   */
  public function validatePhoneNumber($number) {
    //
    $normalized = $this->normalizePhoneNumberToE164($number);
    switch($type = $this->analyzePhoneNumber($normalized)) {
      case self::TYPE_DOMESTIC:
      case self::TYPE_AMERICAN:
      case self::TYPE_TOLLFREE:
      case self::TYPE_INTERNATIONAL:
      case self::TYPE_SIP:
        return $type;

      default:
        return false;
        break;
    }
  }

  /**
   * Conver letters to numbers.
   *
   * @param sting $phone
   *   The phone number.
   *
   * @return mixed
   */
  protected function convertAlphaNumeric($phone) {
    return str_ireplace(
      ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'],
      ['2','2','2','3','3','3','4','4','4','5','5','5','6','6','6','7','7','7','7','8','8','8','9','9','9','9'],
      $phone);
  }

  /**
   * Convert phone number to E164.
   *
   * @param string $phone
   *   The phone number.
   *
   * @return bool|mixed|string
   */
  protected function normalizePhoneNumberToE164($phone) {
    if (strpos($phone, 'sip:') !== false) {
      return $phone;
    }

    // Convert letters to numbers.
    $phone = $this->convertAlphaNumeric($phone);

    // Get rid of any non (digit, + character).
    $phone = preg_replace('/[^0-9+]/', '', $phone);

    if (preg_match("/^(13[0-9]|14[0-9]|15[0-9]|17[0-9]|18[0-9])\d{8}$/", $phone, $matches)) {
      return "{$matches[0]}";
    }

    // validate intl 10.
    if(preg_match('/^\+([2-9][0-9]{9})$/', $phone, $matches)) {
      return "+{$matches[1]}";
    }

    // validate US DID.
    if(preg_match('/^\+?1?([2-9][0-9]{9})$/', $phone, $matches)) {
      return "+1{$matches[1]}";
    }

    // validate INTL DID.
    if(preg_match('/^\+?([2-9][0-9]{8,14})$/', $phone, $matches)) {
      return "+{$matches[1]}";
    }

    // premium US DID.
    if(preg_match('/^\+?1?([2-9]11)$/', $phone, $matches)) {
      return "+1{$matches[1]}";
    }

    return false;
  }

  /**
   * Analyze phone number.
   *
   * @param string $phone
   *   The phone number.
   *
   * @return int
   */
  protected function analyzePhoneNumber($phone) {
    if (strpos($phone, 'sip:') !== false) {
      return self::TYPE_SIP;
    }

    // Normalize for letters.
    $phone = $this->normalizePhoneNumberToE164($phone);

    // if it`s a china phone number.
    if ($this->isChinaDID($phone)) {
      return self::TYPE_DOMESTIC;
    }

    if($this->isShortPremium($phone)) {
      return self::TYPE_PREMIUM;
    }

    // If it's a north american number.
    if($this->isNorthAmericanDID($phone))  {
      // Check that it's not premium.
      if($this->isNorthAmericanPremiumDID($phone)) {
        return self::TYPE_PREMIUM;
      }

      // Check if it's toll free.
      if(preg_match('/^\+?1?(8([0-9])\2)|(88[0-9])[0-9]{7}$/', $phone)) {
        return self::TYPE_TOLLFREE;
      }

      return self::TYPE_AMERICAN;
    }

    // Wasn't north american, check for international.
    if($tmp = $this->isInternationalDID($phone)) {
      return self::TYPE_INTERNATIONAL;
    }

    // UAnkonwn type.
    return self::TYPE_UNKNOWN;
  }

  /**
   * Check it`s a north american DID.
   *
   * @param string $phone
   *   The phone number.
   *
   * @return bool
   */
  protected function isNorthAmericanDID($phone) {
    // Get rid of any non (digit, + character).
    $phone = preg_replace('/[^0-9+]/', '', $phone);

    // Validate North American DID.
    if(preg_match('/^\+1([2-9][0-9]{9})$/', $phone, $matches)) {
      return true;
    }
    return false;
  }

  /**
   * Check it`s a china DID.
   */
  protected function isChinaDID($phone) {
    if (preg_match("/^(13[0-9]|14[0-9]|15[0-9]|17[0-9]|18[0-9])\d{8}$/", $phone)) {
      return true;
    }
    return false;
  }

  /**
   * Emergency and info services.
   */
  protected function isShortPremium($phone) {
    if(preg_match('/^\+?1?([0-9]11)$/', $phone, $matches)) {
      return true;
    }
    return false;
  }

  /**
   * Check the phone number is north american DID.
   */
  protected function isNorthAmericanPremiumDID($phone) {
    // Get rid of any non (digit, + character).
    $phone = preg_replace('/[^0-9+]/', '', $phone);

    // Validate North American Premium DID (toll services).
    if(preg_match('/^\+?1?(((900)|(976))[0-9]{7})$/', $phone, $matches)) {
      return true;
    }
    // Full dial information services.
    if(preg_match('/^\+?1?([2-9][0-9]{2}5551212)$/', $phone, $matches)) {
      return true;
    }
    return false;
  }

  /**
   * Check the phone number is a international DID.
   */
  protected function isInternationalDID($phone) {
    // Get rid of any non (digit, + character).
    $phone = preg_replace('/[^0-9+]/', '', $phone);

    // Validate INTL DID.
    if(preg_match('/^\+?([2-9][0-9]{8,14})$/', $phone, $matches)) {
      return true;
    }
    return false;
  }

  /**
   * Get type.
   */
  protected function decodeType($type) {
    switch($type) {
      case self::TYPE_DOMESTIC:
        return "china";
        break;
      
      case self::TYPE_AMERICAN:
        return "Domestic";
        break;

      case self::TYPE_INTERNATIONAL:
        return "International";
        break;

      case self::TYPE_PREMIUM:
        return "Domestic Premium";
        break;

      case self::TYPE_TOLLFREE:
        return "Toll Free";
        break;

      case self::TYPE_AMERICAN_INVALID:
        return "Domestic Invalid";
        break;

      default:
        return "Unkown";
        break;
    }
  }

  /**
   * Get E164 number.
   */
  protected function normalizeE164ForDisplay($e164Number) {
    preg_match("/^\+?1?([2-9][0-9]{8,13})$/",$e164Number,$match);
    if(strlen($match[1])) {
      return $match[1];
    }
    else {
      return $e164Number;
    }
  }

}
