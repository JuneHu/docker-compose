<?php

/**
 * @file
 */

/**
 *
 */
class HTMLPurifier_DefinitionCache_Serializer extends HTMLPurifier_DefinitionCache {

  /**
   * @param HTMLPurifier_Definition $def
   * @param HTMLPurifier_Config $config
   * @return int|bool
   */
  public function add($def, $config) {

    if (!$this->checkDefType($def)) {
      return;
    }
    $file = $this->generateFilePath($config);
    if (file_exists($file)) {
      return FALSE;
    }
    if (!$this->_prepareDir($config)) {
      return FALSE;
    }
    return $this->_write($file, serialize($def), $config);
  }

  /**
   * @param HTMLPurifier_Definition $def
   * @param HTMLPurifier_Config $config
   * @return int|bool
   */
  public function set($def, $config) {

    if (!$this->checkDefType($def)) {
      return;
    }
    $file = $this->generateFilePath($config);
    if (!$this->_prepareDir($config)) {
      return FALSE;
    }
    return $this->_write($file, serialize($def), $config);
  }

  /**
   * @param HTMLPurifier_Definition $def
   * @param HTMLPurifier_Config $config
   * @return int|bool
   */
  public function replace($def, $config) {

    if (!$this->checkDefType($def)) {
      return;
    }
    $file = $this->generateFilePath($config);
    if (!file_exists($file)) {
      return FALSE;
    }
    if (!$this->_prepareDir($config)) {
      return FALSE;
    }
    return $this->_write($file, serialize($def), $config);
  }

  /**
   * @param HTMLPurifier_Config $config
   * @return bool|HTMLPurifier_Config
   */
  public function get($config) {

    $file = $this->generateFilePath($config);
    if (!file_exists($file)) {
      return FALSE;
    }
    return unserialize(file_get_contents($file));
  }

  /**
   * @param HTMLPurifier_Config $config
   * @return bool
   */
  public function remove($config) {

    $file = $this->generateFilePath($config);
    if (!file_exists($file)) {
      return FALSE;
    }
    return unlink($file);
  }

  /**
   * @param HTMLPurifier_Config $config
   * @return bool
   */
  public function flush($config) {

    if (!$this->_prepareDir($config)) {
      return FALSE;
    }
    $dir = $this->generateDirectoryPath($config);
    $dh = opendir($dir);
    // Apparently, on some versions of PHP, readdir will return
    // an empty string if you pass an invalid argument to readdir.
    // So you need this test.  See #49.
    if (FALSE === $dh) {
      return FALSE;
    }
    while (FALSE !== ($filename = readdir($dh))) {
      if (empty($filename)) {
        continue;
      }
      if ($filename[0] === '.') {
        continue;
      }
      unlink($dir . '/' . $filename);
    }
    return TRUE;
  }

  /**
   * @param HTMLPurifier_Config $config
   * @return bool
   */
  public function cleanup($config) {

    if (!$this->_prepareDir($config)) {
      return FALSE;
    }
    $dir = $this->generateDirectoryPath($config);
    $dh = opendir($dir);
    // See #49 (and above).
    if (FALSE === $dh) {
      return FALSE;
    }
    while (FALSE !== ($filename = readdir($dh))) {
      if (empty($filename)) {
        continue;
      }
      if ($filename[0] === '.') {
        continue;
      }
      $key = substr($filename, 0, strlen($filename) - 4);
      if ($this->isOld($key, $config)) {
        unlink($dir . '/' . $filename);
      }
    }
    return TRUE;
  }

  /**
   * Generates the file path to the serial file corresponding to
   * the configuration and definition name.
   *
   * @param HTMLPurifier_Config $config
   *
   * @return string
   *
   * @todo Make protected
   */
  public function generateFilePath($config) {

    $key = $this->generateKey($config);
    return $this->generateDirectoryPath($config) . '/' . $key . '.ser';
  }

  /**
   * Generates the path to the directory contain this cache's serial files.
   *
   * @param HTMLPurifier_Config $config
   *
   * @return string
   *
   * @note No trailing slash
   *
   * @todo Make protected
   */
  public function generateDirectoryPath($config) {

    $base = $this->generateBaseDirectoryPath($config);
    return $base . '/' . $this->type;
  }

  /**
   * Generates path to base directory that contains all definition type
   * serials.
   *
   * @param HTMLPurifier_Config $config
   *
   * @return mixed|string
   *
   * @todo Make protected
   */
  public function generateBaseDirectoryPath($config) {

    $base = $config->get('Cache.SerializerPath');
    $base = is_null($base) ? HTMLPURIFIER_PREFIX . '/HTMLPurifier/DefinitionCache/Serializer' : $base;
    return $base;
  }

  /**
   * Convenience wrapper function for file_put_contents.
   *
   * @param string $file
   *   File name to write to
   * @param string $data
   *   Data to write into file
   * @param HTMLPurifier_Config $config
   *
   * @return int|bool Number of bytes written if success, or false if failure.
   */
  private function _write($file, $data, $config) {

    $result = file_put_contents($file, $data);
    if ($result !== FALSE) {
      // Set permissions of the new file (no execute)
      $chmod = $config->get('Cache.SerializerPermissions');
      if ($chmod === NULL) {
        // don't do anything.
      }
      else {
        $chmod = $chmod & 0666;
        chmod($file, $chmod);
      }
    }
    return $result;
  }

  /**
   * Prepares the directory that this type stores the serials in.
   *
   * @param HTMLPurifier_Config $config
   *
   * @return bool True if successful
   */
  private function _prepareDir($config) {

    $directory = $this->generateDirectoryPath($config);
    $chmod = $config->get('Cache.SerializerPermissions');
    if (!is_dir($directory)) {
      $base = $this->generateBaseDirectoryPath($config);
      if (!is_dir($base)) {
        trigger_error(
              'Base directory ' . $base . ' does not exist,
                    please create or change using %Cache.SerializerPath',
              E_USER_WARNING
          );
        return FALSE;
      }
      elseif (!$this->_testPermissions($base, $chmod)) {
        return FALSE;
      }
      if ($chmod === NULL) {
        trigger_error(
              'Base directory ' . $base . ' does not exist,
                    please create or change using %Cache.SerializerPath',
              E_USER_WARNING
          );
        return FALSE;
      }
      if ($chmod !== NULL) {
        mkdir($directory, $chmod);
      }
      else {
        mkdir($directory);
      }
      if (!$this->_testPermissions($directory, $chmod)) {
        trigger_error(
              'Base directory ' . $base . ' does not exist,
                    please create or change using %Cache.SerializerPath',
              E_USER_WARNING
          );
        return FALSE;
      }
    }
    elseif (!$this->_testPermissions($directory, $chmod)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Tests permissions on a directory and throws out friendly
   * error messages and attempts to chmod it itself if possible.
   *
   * @param string $dir
   *   Directory path
   * @param int $chmod
   *   Permissions
   *
   * @return bool True if directory is writable
   */
  private function _testPermissions($dir, $chmod) {

    // Early abort, if it is writable, everything is hunky-dory.
    if (is_writable($dir)) {
      return TRUE;
    }
    if (!is_dir($dir)) {
      // generally, you'll want to handle this beforehand
      // so a more specific error message can be given.
      trigger_error(
            'Directory ' . $dir . ' does not exist',
            E_USER_WARNING
        );
      return FALSE;
    }
    if (function_exists('posix_getuid') && $chmod !== NULL) {
      // POSIX system, we can give more specific advice.
      if (fileowner($dir) === posix_getuid()) {
        // We can chmod it ourselves.
        $chmod = $chmod | 0700;
        if (chmod($dir, $chmod)) {
          return TRUE;
        }
      }
      elseif (filegroup($dir) === posix_getgid()) {
        $chmod = $chmod | 0070;
      }
      else {
        // PHP's probably running as nobody, so we'll
        // need to give global permissions.
        $chmod = $chmod | 0777;
      }
      trigger_error(
            'Directory ' . $dir . ' not writable, ' .
            'please chmod to ' . decoct($chmod),
            E_USER_WARNING
        );
    }
    else {
      // Generic error message.
      trigger_error(
            'Directory ' . $dir . ' not writable, ' .
            'please alter file permissions',
            E_USER_WARNING
        );
    }
    return FALSE;
  }

}

// vim: et sw=4 sts=4.
