<?php

namespace Drupal\cloud_system\PHPExcel\Collection;

use Drupal\Core\Cache\CacheBackendInterface;

/**
 * This is the default implementation for in-memory cell collection.
 *
 * Alternatives implementation should leverage off-memory, non-volatile storage
 * to reduce overall memory usage.
 */
class Memory implements CacheBackendInterface {
  private $cache = [];

  public function clear() {
    $this->cache = [];

    return TRUE;
  }

  public function delete($key) {
    unset($this->cache[$key]);

    return TRUE;
  }

  public function deleteMultiple(array $cids) {
    foreach ($cids as $key) {
      $this->delete($key);
    }

    return TRUE;
  }

  public function get($key, $default = NULL) {
    if ($this->has($key)) {
      return $this->cache[$key];
    }

    return $default;
  }

  public function getMultiple(&$cids, $allow_invalid = FALSE) {
    $results = [];
    foreach ($cids as $key) {
      $results[$key] = $this->get($key, ($allow_invalid ? $allow_invalid : NULL));
    }

    return $results;
  }

  public function has($key) {
    return array_key_exists($key, $this->cache);
  }

  public function set($cid, $data, $expire = 300, array $tags = []) {
    $this->cache[$cid] = $data;

    return TRUE;
  }

  public function setMultiple(array $items) {
    foreach ($items as $key => $value) {
      $this->set($key, $value);
    }

    return TRUE;
  }


  /**
   * Deletes all cache items in a bin.
   *
   * @see \Drupal\Core\Cache\CacheBackendInterface::invalidateAll()
   * @see \Drupal\Core\Cache\CacheBackendInterface::delete()
   * @see \Drupal\Core\Cache\CacheBackendInterface::deleteMultiple()
   */
  public function deleteAll() {
  }

  /**
   * Marks a cache item as invalid.
   *
   * Invalid items may be returned in later calls to get(), if the
   * $allow_invalid argument is TRUE.
   *
   * @param string $cid
   *   The cache ID to invalidate.
   *
   * @see \Drupal\Core\Cache\CacheBackendInterface::delete()
   * @see \Drupal\Core\Cache\CacheBackendInterface::invalidateMultiple()
   * @see \Drupal\Core\Cache\CacheBackendInterface::invalidateAll()
   */
  public function invalidate($cid) {
  }

  /**
   * Marks cache items as invalid.
   *
   * Invalid items may be returned in later calls to get(), if the
   * $allow_invalid argument is TRUE.
   *
   * @param string[] $cids
   *   An array of cache IDs to invalidate.
   *
   * @see \Drupal\Core\Cache\CacheBackendInterface::deleteMultiple()
   * @see \Drupal\Core\Cache\CacheBackendInterface::invalidate()
   * @see \Drupal\Core\Cache\CacheBackendInterface::invalidateAll()
   */
  public function invalidateMultiple(array $cids) {
  }

  /**
   * Marks all cache items as invalid.
   *
   * Invalid items may be returned in later calls to get(), if the
   * $allow_invalid argument is TRUE.
   *
   * @see \Drupal\Core\Cache\CacheBackendInterface::deleteAll()
   * @see \Drupal\Core\Cache\CacheBackendInterface::invalidate()
   * @see \Drupal\Core\Cache\CacheBackendInterface::invalidateMultiple()
   */
  public function invalidateAll() {
  }

  /**
   * Performs garbage collection on a cache bin.
   *
   * The backend may choose to delete expired or invalidated items.
   */
  public function garbageCollection() {
  }

  /**
   * Remove a cache bin.
   */
  public function removeBin() {
  }
}
