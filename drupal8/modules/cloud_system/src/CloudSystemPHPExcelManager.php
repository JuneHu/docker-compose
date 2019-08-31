<?php

namespace Drupal\cloud_system;

use Drupal\cloud_system\PHPExcel\IOFactory;
use Drupal\cloud_system\PHPExcel\Cell;
use Drupal\cloud_system\PHPExcel\Spreadsheet;
use \Drupal\Component\Utility\Unicode;
use Drupal\cloud_system\PHPExcel\Style\Alignment;

class CloudSystemPHPExcelManager {

  /**
   * Import an Excel file.
   *
   * Simple API function that will load an Excel file from $path and parse it
   * as a multidimensional array.
   *
   * @param string  $path
   *    The path to the Excel file. Must be readable.
   * @param boolean $keyed_by_headers = TRUE
   *    If TRUE, will key the row array with the header values and will
   *    skip the header row. If FALSE, will contain the headers in the first
   *    row and the rows will be keyed numerically.
   * @param boolean $keyed_by_worksheet = FALSE
   *    If TRUE, will key the data array with the worksheet names. Otherwise,
   *   it will use a numerical key.
   *
   * For example, if you wish to load only a specific
   *   worksheet to save time, you could use:
   *
   * @code
   *    \Drupal::service('php_excel.manager')->import('path/to/file.xls', TRUE,
   *   TRUE);
   * @endcode
   *
   * @return array|int
   *    The parsed data as an array on success,
   *   PHPEXCEL_ERROR_LIBRARY_NOT_FOUND
   *    or PHPEXCEL_ERROR_FILE_NOT_READABLE on error.
   *
   */
  public function import($path, $keyed_by_headers = FALSE, $keyed_by_worksheet = FALSE) {
    if (is_readable($path)) {
      $spreadsheet = IOFactory::load($path);
      $data = $headers = [];
      $i = 0;

      foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
        $j = 0;
        $lastCol = $worksheet->getHighestDataColumn();

        foreach ($worksheet->getRowIterator() as $row) {
          if ($keyed_by_worksheet) {
            $i = $worksheet->getTitle();
          }
          $k = 0;

          $cells = $row->getCellIterator('A', $lastCol);
          $cells->setIterateOnlyExistingCells(FALSE);

          foreach ($cells as $cell) {

            $value = $cell->getValue();
            $value = Unicode::strlen($value) ? trim($value) : '';

            if (!$j && $keyed_by_headers) {
              $value = Unicode::strlen($value) ? $value : $k;

              $headers[$i][] = $value;
            }
            elseif ($keyed_by_headers) {
              $data[$i][$j - 1][$headers[$i][$k]] = $value;
            }
            else {
              $col_index = $k;
              if ($cells->getIterateOnlyExistingCells()) {
                $col_index = Cell::columnIndexFromString($cell->getColumn()) - 1;
              }

              $data[$i][$j][$col_index] = $value;
            }

            $k++;
          }

          $j++;
        }

        if (!$keyed_by_worksheet) {
          $i++;
        }
      }

      // Free up memory.
      $spreadsheet->disconnectWorksheets();
      unset($spreadsheet);
      return $data;
    }
    else {
      \Drupal::logger('phpexcel')
        ->error("The path '@path' is not readable. Excel import aborted.",
          array('@path' => $path));

      return FALSE;
    }
  }

  /**
   * Test export function.
   */
  public function test() {
    for($i = 1; $i<10; $i++) {
      $data[] = array($i, $this->genRandomString(), $this->genRandomString(), $this->genRandomString(), $this->genRandomString(), $this->genRandomString());
    }

    $headers = array('Header 1', 'Header 2', 'Header 3', 'Header 4', 'Header 5', 'Header 6');

    $path = '/Users/panjunliu/Downloads/test_big_excel.xlsx';
    return $this->export($headers, $data, $path);
  }

  private function genRandomString($length = 100) {
    $characters = "0123456789abcdefghijklmnopqrstuvwxyz _";
    $string = "";
    for ($p = 0; $p < $length; $p++) {
      $string .= $characters[mt_rand(0, strlen($characters)-1)];
    }
    return $string;
  }


  /**
   * Simple API function which will generate an XLS file and
   * save it in $path.
   *
   * @param array  $headers
   *    An array containing all headers. If given a two-dimensional array,
   *    each first dimension entry will be on a separate worksheet
   *    ($headers[sheet][header]).
   * @param array  $data
   *    A two-dimensional array containing all data ($data[row][column]).
   *    If given a three-dimensional array, each first dimension
   *    entry will be on a separate worksheet ($data[sheet][row][column]).
   * @param string $path
   *    The path where the file must be saved. Must be writable.
   * @param array  $options
   *    An array which allows to set some specific options.
   *    Used keys:
   *    - ignore_headers: whether the $headers parameter should be ignored or
   *      not. Defaults to false.
   *    - format: The EXCEL format. Can be either 'xls', 'xlsx', 'csv', or
   *   'ods'. Defaults to the extension given in the $path parameter, or 'xls'.
   *    - creator: The name of the creator.
   *    - title: The title.
   *    - subject: The subject.
   *    - description: The description.
   *    - template: A path to a file to use as a template.
   *    - merge_cells: Array with sheets and cell ranges for merge. For
   *   example:
   *      [sheet][0]='A1:C1'.
   *    The options array will always be passed to all the hooks. If
   *    developers need specific information for their own hooks, they
   *    can add any data to this array.
   *
   * @return int
   *    PHPEXCEL_SUCCESS on success, PHPEXCEL_ERROR_NO_HEADERS,
   *    PHPEXCEL_ERROR_NO_DATA, PHPEXCEL_ERROR_PATH_NOT_WRITABLE or
   *    PHPEXCEL_ERROR_LIBRARY_NOT_FOUND on error.
   */
  public function export($headers = [], $data = [], $path = '', $options = NULL) {
    if (empty($headers) && empty($options['ignore_headers'])) {
      \Drupal::logger('phpexcel')->error("No header was provided, and the 'ignore_headers' option was 
not set to TRUE. Excel export aborted.");

      return FALSE;
    }

    // Make sure we have an ignore_headers key to prevent Notices.
    $options['ignore_headers'] = isset($options['ignore_headers']) ? $options['ignore_headers'] : empty($headers);

    if (!count($data)) {
      \Drupal::logger('phpexcel')
        ->error("No data was provided. Excel export aborted.");

      return FALSE;
    }

    if (!(is_writable($path) || (!file_exists($path) && is_writable(dirname($path))))) {
      \Drupal::logger('phpexcel')
        ->error("Path '@path' is not writable. Excel export aborted.",
          array('@path' => $path));

      return FALSE;
    }

    $path = $this->mungeFilename($path);

    // First, see if the file already exists
    if (file_exists($path)) {
      $xls = IOFactory::load($path);
    }
    elseif (!empty($options['template'])) {
      // Must we render from a template file ?
      $xls_reader = IOFactory::createReaderForFile($options['template']);

      $xls = $xls_reader->load($options['template']);
    }
    else {
      $xls = new Spreadsheet();
    }

    $this->setProperties($xls->getProperties(), $options);

    // Must we ignore the headers ?
    if (empty($options['ignore_headers'])) {
      $this->setHeaders($xls, $headers, $options);
    }

    $this->setColumns($xls, $data, empty($options['ignore_headers']) ? $headers : NULL, $options);

    // Merge cells.
    if (!empty($options['merge_cells'])) {
      foreach ($options['merge_cells'] as $sheet_name => $merge_cells_list) {
        foreach ($merge_cells_list as $merge_cells) {
          $sheet = $xls->setActiveSheetIndex($sheet_name);
          $style = array(
            'alignment' => array(
              'horizontal' => Alignment::HORIZONTAL_CENTER,
            ),
          );
          $sheet->getStyle($merge_cells)->applyFromArray($style);
          $xls->getActiveSheet()->mergeCells($merge_cells);
        }
      }
    }

    $format = isset($options['format']) ? drupal_strtolower($options['format']) : @end(explode('.', $path));

    switch ($format) {
      case 'xlsx':
        $writer = IOFactory::createWriter($xls, "Xlsx");
        break;

      case 'xls':
        $writer = IOFactory::createWriter($xls, 'Xls');
        break;

      case 'ods':
        $writer = IOFactory::createWriter($xls, 'Ods');
        break;

      default:
        $writer = IOFactory::createWriter($xls, 'Csv');
    }

    $writer->save($path);
    unset($writer);

    return file_exists($path) ? TRUE : FALSE;
  }

  /**
   * Sets the Excel file properties, like creator, title, etc.
   */
  private function setProperties($properties, $options) {
    if (isset($options['creator'])) {
      $properties->setCreator($options['creator']);
    }
    else {
      $properties->setCreator("Cloud");
    }

    if (isset($options['title'])) {
      $properties->setTitle($options['title']);
    }

    if (isset($options['subject'])) {
      $properties->setSubject($options['subject']);
    }

    if (isset($options['description'])) {
      $properties->setDescription($options['description']);
    }
  }

  /**
   * Sets the Excel file headers.
   */
  private function setHeaders($xls, &$headers, $options) {
    // Prior to PHP 5.3, calling current() on an associative array would not work.
    // Get only array values, just in case.
    if (!is_array(current(array_values($headers)))) {
      $headers = array($headers);
    }

    $sheet_id = 0;
    foreach ($headers as $sheet_name => $sheet_headers) {
      // If the sheet name is just an index, assume to create a string name
      if (is_numeric($sheet_name)) {
        $sheet_name = 'Worksheet ' . ($sheet_id + 1);
      }
      // First, attempt to open an existing sheet by the given name.
      if (($sheet = $xls->getSheetByName($sheet_name)) === NULL) {
        if ($sheet_id) {
          $xls->createSheet($sheet_id);
          $sheet = $xls->setActiveSheetIndex($sheet_id);
        }
        else {
          // PHPExcel always creates one sheet.
          $sheet = $xls->getSheet(0);
        }

        $sheet->setTitle($sheet_name);

      }

      for ($i = 0, $len = count($sheet_headers); $i < $len; $i++) {
        $value = trim($sheet_headers[$i]);

        $sheet->setCellValueByColumnAndRow($i, 1, $value);
      }

      $sheet_id++;
    }
  }

  /**
   * Adds the data to the Excel file.
   */
  private function setColumns($xls, &$data, $headers = NULL, $options = []) {
    // Prior to PHP 5.3, calling current() on an associative array would not work.
    // Get only array values, just in case.
    if (!is_array(current(current(array_values($data))))) {
      $data = array($data);
    }

    $sheet_id = 0;
    foreach ($data as $sheet_name => $sheet_data) {
      // If the sheet name is just an index, assume to create a string name
      if (is_numeric($sheet_name)) {
        $sheet_name = t('Worksheet !id', array('!id' => ($sheet_id + 1)));
      }
      // First, attempt to open an existing sheet by the given name.
      if (($sheet = $xls->getSheetByName($sheet_name)) === NULL) {
        // If the headers are not set, we haven't created any sheets yet.
        // Create them now.
        if (!isset($headers)) {
          if ($sheet_id) {
            $xls->createSheet($sheet_id);
            $sheet = $xls->setActiveSheetIndex($sheet_id);
          }
          else {
            // PHPExcel always creates one sheet.
            $sheet = $xls->getSheet();
          }

          $sheet->setTitle($sheet_name);
        }
        else {
          $sheet = $xls->setActiveSheetIndex($sheet_id);
        }
      }

      // Get the highest row of the sheet to calculate the offset so that rows are
      // simply appended rather than overwritten if the file is built in multiple
      // passes.
      $offset = $sheet->getHighestRow() + ($options['ignore_headers'] ? 0 : 1);

      for ($i = 0, $len = count($sheet_data); $i < $len; $i++) {
        for ($j = 0; $j < count($sheet_data[$i]); $j++) {
          $value = isset($sheet_data[$i][$j]) ? $sheet_data[$i][$j] : '';

          // We must offset the row count (by 2 if the first row is used by the
          // headers, because PHPExcel starts the count at 1, not 0).
          $sheet->setCellValueByColumnAndRow($j, $i + $offset, $value);
        }
      }

      $sheet_id++;
    }
  }

  /**
   * Munges the filename in the path.
   *
   * We can't use drupals file_munge_filename() directly because the $path
   * variable contains the path as well. Separate the filename from the
   * directory structure, munge it and return.
   *
   * @param string $path
   *
   * @return string
   */
  private function mungeFilename($path) {
    $parts = explode(DIRECTORY_SEPARATOR, $path);

    $filename = array_pop($parts);

    return implode(DIRECTORY_SEPARATOR, $parts) . DIRECTORY_SEPARATOR . file_munge_filename($filename, 'xls xlsx csv ods');
  }

}