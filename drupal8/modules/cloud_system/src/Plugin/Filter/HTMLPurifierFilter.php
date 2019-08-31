<?php

/**
 * @file
 * Contains \Drupal\cloud_system\Plugin\Filter\HTMLPurifierFilter.
 */

namespace Drupal\cloud_system\Plugin\Filter;

use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterProcessResult;

/**
 * A filter that removes malicious HTML and ensures standards compliant output.
 *
 * @Filter(
 *   id = "htmlpurifier",
 *   module = "htmlpurifier",
 *   title = @Translation("HTMLPurifier"),
 *   description = @Translation("Removes malicious HTML code and ensures that the output is standards compliant."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   weight = 20
 * )
 */

class HTMLPurifierFilter extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['htmlpurifier'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Choose an HTML Purifier Configuration'),
      '#default_value' => isset($this->settings['htmlpurifier']) ? $this->settings['htmlpurifier'] : '',
      '#description' => $this->t('HTML Purifier text.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $module_path = DRUPAL_ROOT . '/' . drupal_get_path('module', 'cloud_system');
    if(file_exists("$module_path/src/HTMLPurifier/HTMLPurifier.includes.php")) {
      require_once("$module_path/src/HTMLPurifier/HTMLPurifier.includes.php");
      // Create config.
      $config = \HTMLPurifier_Config::createDefault();

      // Init.
      $purifier = new \HTMLPurifier($config);

      $purified_text = $purifier->purify($text);

      return new FilterProcessResult($purified_text);
    }

    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  public function getHTMLRestrictions() {
    // @todo: Figure how to return the structured array of restricted HTML.
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return t('HTML tags will be transformed to conform to HTML standards.');
  }

}
