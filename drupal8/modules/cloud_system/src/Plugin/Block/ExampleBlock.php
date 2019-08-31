<?php

namespace Drupal\cloud_system\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'ExampleBlock' block.
 *
 * @Block(
 *  id = "example_block",
 *  admin_label = @Translation("Example block"),
 * )
 */
class ExampleBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['example_block']['#markup'] = 'Implement ExampleBlock.';

    return $build;
  }

}
