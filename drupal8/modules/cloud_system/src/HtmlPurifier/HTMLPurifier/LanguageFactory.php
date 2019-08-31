<?php

/**
 * @file
 * Class responsible for generating HTMLPurifier_Language objects, managing
 * caching and fallbacks.
 *
 * @note Thanks to MediaWiki for the general logic, although this version
 *       has been entirely rewritten
 *
 * @todo Serialized cache for languages
 */

/**
 *
 */
class HTMLPurifier_LanguageFactory {

  /**
   * Cache of language code information used to load HTMLPurifier_Language objects.
   * Structure is: $factory->cache[$language_code][$key] = $value.
   *
   * @type array
   */
  public $cache;

  /**
   * Valid keys in the HTMLPurifier_Language object. Designates which
   * variables to slurp out of a message file.
   *
   * @type array
   */
  public $keys = array('fallback', 'messages', 'errorNames');

  /**
   * Instance to validate language codes.
   *
   * @type HTMLPurifier_AttrDef_Lang
   */
  protected $validator;

  /**
   * Cached copy of dirname(__FILE__), directory of current file without
   * trailing slash.
   *
   * @type string
   */
  protected $dir;

  /**
   * Keys whose contents are a hash map and can be merged.
   *
   * @type array
   */
  protected $mergeable_keys_map = array('messages' => TRUE, 'errorNames' => TRUE);

  /**
   * Keys whose contents are a list and can be merged.
   *
   * @value array lookup
   */
  protected $mergeable_keys_list = [];

  /**
   * Retrieve sole instance of the factory.
   *
   * @param HTMLPurifier_LanguageFactory $prototype
   *   Optional prototype to overload sole instance with,
   *                   or bool true to reset to default factory.
   *
   * @return HTMLPurifier_LanguageFactory
   */
  public static function instance($prototype = NULL) {

    static $instance = NULL;
    if ($prototype !== NULL) {
      $instance = $prototype;
    }
    elseif ($instance === NULL || $prototype == TRUE) {
      $instance = new HTMLPurifier_LanguageFactory();
      $instance->setup();
    }
    return $instance;
  }

  /**
   * Sets up the singleton, much like a constructor.
   *
   * @note Prevents people from getting this outside of the singleton
   */
  public function setup() {

    $this->validator = new HTMLPurifier_AttrDef_Lang();
    $this->dir = HTMLPURIFIER_PREFIX . '/HTMLPurifier';
  }

  /**
   * Creates a language object, handles class fallbacks.
   *
   * @param HTMLPurifier_Config $config
   * @param HTMLPurifier_Context $context
   * @param bool|string $code
   *   Code to override configuration with. Private parameter.
   *
   * @return HTMLPurifier_Language
   */
  public function create($config, $context, $code = FALSE) {

    // Validate language code.
    if ($code === FALSE) {
      $code = $this->validator->validate(
            $config->get('Core.Language'),
            $config,
            $context
        );
    }
    else {
      $code = $this->validator->validate($code, $config, $context);
    }
    if ($code === FALSE) {
      // Malformed code becomes English.
      $code = 'en';
    }

    // Make valid PHP classname.
    $pcode = str_replace('-', '_', $code);
    // Recursion protection.
    static $depth = 0;

    if ($code == 'en') {
      $lang = new HTMLPurifier_Language($config, $context);
    }
    else {
      $class = 'HTMLPurifier_Language_' . $pcode;
      $file  = $this->dir . '/Language/classes/' . $code . '.php';
      if (file_exists($file) || class_exists($class, FALSE)) {
        $lang = new $class($config, $context);
      }
      else {
        // Go fallback.
        $raw_fallback = $this->getFallbackFor($code);
        $fallback = $raw_fallback ? $raw_fallback : 'en';
        $depth++;
        $lang = $this->create($config, $context, $fallback);
        if (!$raw_fallback) {
          $lang->error = TRUE;
        }
        $depth--;
      }
    }
    $lang->code = $code;
    return $lang;
  }

  /**
   * Returns the fallback language for language.
   *
   * @note Loads the original language into cache
   * @param string $code
   *   language code
   *
   * @return string|bool
   */
  public function getFallbackFor($code) {

    $this->loadLanguage($code);
    return $this->cache[$code]['fallback'];
  }

  /**
   * Loads language into the cache, handles message file and fallbacks.
   *
   * @param string $code
   *   language code
   */
  public function loadLanguage($code) {

    // Recursion guard.
    static $languages_seen = [];

    // Abort if we've already loaded it.
    if (isset($this->cache[$code])) {
      return;
    }

    // Generate filename.
    $filename = $this->dir . '/Language/messages/' . $code . '.php';

    // Default fallback : may be overwritten by the ensuing include.
    $fallback = ($code != 'en') ? 'en' : FALSE;

    // Load primary localisation.
    if (!file_exists($filename)) {
      // Skip the include: will rely solely on fallback.
      $filename = $this->dir . '/Language/messages/en.php';
      $cache = [];
    }
    else {
      include $filename;
      $cache = compact($this->keys);
    }

    // Load fallback localisation.
    if (!empty($fallback)) {

      // Infinite recursion guard.
      if (isset($languages_seen[$code])) {
        trigger_error(
              'Circular fallback reference in language ' .
              $code,
              E_USER_ERROR
          );
        $fallback = 'en';
      }
      $language_seen[$code] = TRUE;

      // Load the fallback recursively.
      $this->loadLanguage($fallback);
      $fallback_cache = $this->cache[$fallback];

      // Merge fallback with current language.
      foreach ($this->keys as $key) {
        if (isset($cache[$key]) && isset($fallback_cache[$key])) {
          if (isset($this->mergeable_keys_map[$key])) {
            $cache[$key] = $cache[$key] + $fallback_cache[$key];
          }
          elseif (isset($this->mergeable_keys_list[$key])) {
            $cache[$key] = array_merge($fallback_cache[$key], $cache[$key]);
          }
        }
        else {
          $cache[$key] = $fallback_cache[$key];
        }
      }
    }

    // Save to cache for later retrieval.
    $this->cache[$code] = $cache;
    return;
  }

}

// vim: et sw=4 sts=4.
