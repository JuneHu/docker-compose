<?php

/**
 * @file
 * Represents a document type, contains information on which modules
 * need to be loaded.
 *
 * @note This class is inspected by Printer_HTMLDefinition->renderDoctype.
 *       If structure changes, please update that function.
 */

/**
 *
 */
class HTMLPurifier_Doctype {
  /**
   * Full name of doctype.
   *
   * @type string
   */
  public $name;

  /**
   * List of standard modules (string identifiers or literal objects)
   * that this doctype uses.
   *
   * @type array
   */
  public $modules = [];

  /**
   * List of modules to use for tidying up code.
   *
   * @type array
   */
  public $tidyModules = [];

  /**
   * Is the language derived from XML (i.e. XHTML)?
   *
   * @type bool
   */
  public $xml = TRUE;

  /**
   * List of aliases for this doctype.
   *
   * @type array
   */
  public $aliases = [];

  /**
   * Public DTD identifier.
   *
   * @type string
   */
  public $dtdPublic;

  /**
   * System DTD identifier.
   *
   * @type string
   */
  public $dtdSystem;

  /**
   *
   */
  public function __construct(
        $name = NULL,
        $xml = TRUE,
        $modules = [],
        $tidyModules = [],
        $aliases = [],
        $dtd_public = NULL,
        $dtd_system = NULL
    ) {
    $this->name         = $name;
    $this->xml          = $xml;
    $this->modules      = $modules;
    $this->tidyModules  = $tidyModules;
    $this->aliases      = $aliases;
    $this->dtdPublic    = $dtd_public;
    $this->dtdSystem    = $dtd_system;
  }

}

// vim: et sw=4 sts=4.
