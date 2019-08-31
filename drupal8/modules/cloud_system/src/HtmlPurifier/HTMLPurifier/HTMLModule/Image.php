<?php

/**
 * @file
 * XHTML 1.1 Image Module provides basic image embedding.
 *
 * @note There is specialized code for removing empty images in
 *       HTMLPurifier_Strategy_RemoveForeignElements
 */

/**
 *
 */
class HTMLPurifier_HTMLModule_Image extends HTMLPurifier_HTMLModule {

  /**
   * @type string
   */
  public $name = 'Image';

  /**
   * @param HTMLPurifier_Config $config
   */
  public function setup($config) {

    $max = $config->get('HTML.MaxImgLength');
    $img = $this->addElement(
          'img',
          'Inline',
          'Empty',
          'Common',
          array(
            'alt*' => 'Text',
              // According to the spec, it's Length, but percents can
              // be abused, so we allow only Pixels.
            'height' => 'Pixels#' . $max,
            'width' => 'Pixels#' . $max,
            'longdesc' => 'URI',
    // Embedded.
            'src*' => new HTMLPurifier_AttrDef_URI(TRUE),
          )
      );
    if ($max === NULL || $config->get('HTML.Trusted')) {
      $img->attr['height'] =
            $img->attr['width'] = 'Length';
    }

    // Kind of strange, but splitting things up would be inefficient.
    $img->attr_transform_pre[] =
        $img->attr_transform_post[] =
            new HTMLPurifier_AttrTransform_ImgRequired();
  }

}

// vim: et sw=4 sts=4.
