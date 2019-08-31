<?php

/**
 * @file
 * XHTML 1.1 Bi-directional Text Module, defines elements that
 * declare directionality of content. Text Extension Module.
 */

/**
 *
 */
class HTMLPurifier_HTMLModule_Bdo extends HTMLPurifier_HTMLModule {

  /**
   * @type string
   */
  public $name = 'Bdo';

  /**
   * @type array
   */
  public $attr_collections = array(
    'I18N' => array('dir' => FALSE),
  );

  /**
   * @param HTMLPurifier_Config $config
   */
  public function setup($config) {

    $bdo = $this->addElement(
          'bdo',
          'Inline',
          'Inline',
          array('Core', 'Lang'),
          array(
    // Required.
            'dir' => 'Enum#ltr,rtl',
              // The Abstract Module specification has the attribute
              // inclusions wrong for bdo: bdo allows Lang.
          )
      );
    $bdo->attr_transform_post[] = new HTMLPurifier_AttrTransform_BdoDir();

    $this->attr_collections['I18N']['dir'] = 'Enum#ltr,rtl';
  }

}

// vim: et sw=4 sts=4.
