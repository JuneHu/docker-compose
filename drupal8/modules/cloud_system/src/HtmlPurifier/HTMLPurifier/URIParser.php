<?php

/**
 * @file
 * Parses a URI into the components and fragment identifier as specified
 * by RFC 3986.
 */

/**
 *
 */
class HTMLPurifier_URIParser {

  /**
   * Instance of HTMLPurifier_PercentEncoder to do normalization with.
   */
  protected $percentEncoder;

  /**
   *
   */
  public function __construct() {

    $this->percentEncoder = new HTMLPurifier_PercentEncoder();
  }

  /**
   * Parses a URI.
   *
   * @param $uri string URI to parse
   *
   * @return HTMLPurifier_URI representation of URI. This representation has
   *         not been validated yet and may not conform to RFC.
   */
  public function parse($uri) {

    $uri = $this->percentEncoder->normalize($uri);

    // Regexp is as per Appendix B.
    // Note that ["<>] are an addition to the RFC's recommended
    // characters, because they represent external delimeters.
    $r_URI = '!' .
    // 2. Scheme.
            '(([a-zA-Z0-9\.\+\-]+):)?' .
    // 4. Authority.
            '(//([^/?#"<>]*))?' .
    // 5. Path.
            '([^?#"<>]*)' .
    // 7. Query.
            '(\?([^#"<>]*))?' .
    // 8. Fragment.
            '(#([^"<>]*))?' .
            '!';

    $matches = [];
    $result = preg_match($r_URI, $uri, $matches);

    if (!$result) {
      // *really* invalid URI.
      return FALSE;
    }
    // Seperate out parts.
    $scheme     = !empty($matches[1]) ? $matches[2] : NULL;
    $authority  = !empty($matches[3]) ? $matches[4] : NULL;
    // Always present, can be empty.
    $path       = $matches[5];
    $query      = !empty($matches[6]) ? $matches[7] : NULL;
    $fragment   = !empty($matches[8]) ? $matches[9] : NULL;

    // Further parse authority.
    if ($authority !== NULL) {
      $r_authority = "/^((.+?)@)?(\[[^\]]+\]|[^:]*)(:(\d*))?/";
      $matches = [];
      preg_match($r_authority, $authority, $matches);
      $userinfo   = !empty($matches[1]) ? $matches[2] : NULL;
      $host       = !empty($matches[3]) ? $matches[3] : '';
      $port       = !empty($matches[4]) ? (int) $matches[5] : NULL;
    }
    else {
      $port = $host = $userinfo = NULL;
    }

    return new HTMLPurifier_URI(
          $scheme, $userinfo, $host, $port, $path, $query, $fragment);
  }

}

// vim: et sw=4 sts=4.
