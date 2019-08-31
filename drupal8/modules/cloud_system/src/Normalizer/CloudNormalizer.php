<?php

namespace Drupal\cloud_system\Normalizer;

use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * Custom normalizer.
 */
class CloudNormalizer extends AbstractNormalizer {

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    return is_object($data) && !$data instanceof \Traversable;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    return (array) $object;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsDenormalization($data, $type, $format = NULL) {
    return $type == 'Array';
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    $array = (array) $data;
    return $array;
  }

}
