<?php

namespace Drupal\cloud_system\PHPExcel\Writer;

use Drupal\cloud_system\PHPExcel\Calculation;
use Drupal\cloud_system\PHPExcel\Cell;
use Drupal\cloud_system\PHPExcel\Chart;
use Drupal\cloud_system\PHPExcel\RichText;
use Drupal\cloud_system\PHPExcel\Shared\Drawing as SharedDrawing;
use Drupal\cloud_system\PHPExcel\Shared\File;
use Drupal\cloud_system\PHPExcel\Shared\Font as SharedFont;
use Drupal\cloud_system\PHPExcel\Shared\StringHelper;
use Drupal\cloud_system\PHPExcel\Spreadsheet;
use Drupal\cloud_system\PHPExcel\Style;
use Drupal\cloud_system\PHPExcel\Style\Alignment;
use Drupal\cloud_system\PHPExcel\Style\Border;
use Drupal\cloud_system\PHPExcel\Style\Borders;
use Drupal\cloud_system\PHPExcel\Style\Fill;
use Drupal\cloud_system\PHPExcel\Style\Font;
use Drupal\cloud_system\PHPExcel\Style\NumberFormat;
use Drupal\cloud_system\PHPExcel\Worksheet;
use Drupal\cloud_system\PHPExcel\Worksheet\Drawing;
use Drupal\cloud_system\PHPExcel\Worksheet\MemoryDrawing;
use Drupal\cloud_system\PHPExcel\Writer\Exception as WriterException;

/**
 * Copyright (c) 2006 - 2015 Spreadsheet.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA
 *
 * @category   Spreadsheet
 *
 * @copyright  Copyright (c) 2006 - 2015 Spreadsheet (https://github.com/PHPOffice/Spreadsheet)
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt    LGPL
 */
class Html extends BaseWriter implements IWriter
{
    /**
     * Spreadsheet object.
     *
     * @var Spreadsheet
     */
    protected $spreadsheet;

    /**
     * Sheet index to write.
     *
     * @var int
     */
    private $sheetIndex = 0;

    /**
     * Images root.
     *
     * @var string
     */
    private $imagesRoot = '';

    /**
     * embed images, or link to images.
     *
     * @var bool
     */
    private $embedImages = false;

    /**
     * Use inline CSS?
     *
     * @var bool
     */
    private $useInlineCss = false;

    /**
     * Array of CSS styles.
     *
     * @var array
     */
    private $cssStyles;

    /**
     * Array of column widths in points.
     *
     * @var array
     */
    private $columnWidths;

    /**
     * Default font.
     *
     * @var Font
     */
    private $defaultFont;

    /**
     * Flag whether spans have been calculated.
     *
     * @var bool
     */
    private $spansAreCalculated = false;

    /**
     * Excel cells that should not be written as HTML cells.
     *
     * @var array
     */
    private $isSpannedCell = [];

    /**
     * Excel cells that are upper-left corner in a cell merge.
     *
     * @var array
     */
    private $isBaseCell = [];

    /**
     * Excel rows that should not be written as HTML rows.
     *
     * @var array
     */
    private $isSpannedRow = [];

    /**
     * Is the current writer creating PDF?
     *
     * @var bool
     */
    protected $isPdf = false;

    /**
     * Generate the Navigation block.
     *
     * @var bool
     */
    private $generateSheetNavigationBlock = true;

    /**
     * Create a new HTML.
     *
     * @param Spreadsheet $spreadsheet
     */
    public function __construct(Spreadsheet $spreadsheet)
    {
        $this->spreadsheet = $spreadsheet;
        $this->defaultFont = $this->spreadsheet->getDefaultStyle()->getFont();
    }

    /**
     * Save Spreadsheet to file.
     *
     * @param string $pFilename
     *
     * @throws WriterException
     */
    public function save($pFilename)
    {
        // garbage collect
        $this->spreadsheet->garbageCollect();

        $saveDebugLog = Calculation::getInstance($this->spreadsheet)->getDebugLog()->getWriteDebugLog();
        Calculation::getInstance($this->spreadsheet)->getDebugLog()->setWriteDebugLog(false);
        $saveArrayReturnType = Calculation::getArrayReturnType();
        Calculation::setArrayReturnType(Calculation::RETURN_ARRAY_AS_VALUE);

        // Build CSS
        $this->buildCSS(!$this->useInlineCss);

        // Open file
        $fileHandle = fopen($pFilename, 'wb+');
        if ($fileHandle === false) {
            throw new WriterException("Could not open file $pFilename for writing.");
        }

        // Write headers
        fwrite($fileHandle, $this->generateHTMLHeader(!$this->useInlineCss));

        // Write navigation (tabs)
        if ((!$this->isPdf) && ($this->generateSheetNavigationBlock)) {
            fwrite($fileHandle, $this->generateNavigation());
        }

        // Write data
        fwrite($fileHandle, $this->generateSheetData());

        // Write footer
        fwrite($fileHandle, $this->generateHTMLFooter());

        // Close file
        fclose($fileHandle);

        Calculation::setArrayReturnType($saveArrayReturnType);
        Calculation::getInstance($this->spreadsheet)->getDebugLog()->setWriteDebugLog($saveDebugLog);
    }

    /**
     * Map VAlign.
     *
     * @param string $vAlign Vertical alignment
     *
     * @return string
     */
    private function mapVAlign($vAlign)
    {
        switch ($vAlign) {
            case Alignment::VERTICAL_BOTTOM:
                return 'bottom';
            case Alignment::VERTICAL_TOP:
                return 'top';
            case Alignment::VERTICAL_CENTER:
            case Alignment::VERTICAL_JUSTIFY:
                return 'middle';
            default:
                return 'baseline';
        }
    }

    /**
     * Map HAlign.
     *
     * @param string $hAlign Horizontal alignment
     *
     * @return string|false
     */
    private function mapHAlign($hAlign)
    {
        switch ($hAlign) {
            case Alignment::HORIZONTAL_GENERAL:
                return false;
            case Alignment::HORIZONTAL_LEFT:
                return 'left';
            case Alignment::HORIZONTAL_RIGHT:
                return 'right';
            case Alignment::HORIZONTAL_CENTER:
            case Alignment::HORIZONTAL_CENTER_CONTINUOUS:
                return 'center';
            case Alignment::HORIZONTAL_JUSTIFY:
                return 'justify';
            default:
                return false;
        }
    }

    /**
     * Map border style.
     *
     * @param int $borderStyle Sheet index
     *
     * @return string
     */
    private function mapBorderStyle($borderStyle)
    {
        switch ($borderStyle) {
            case Border::BORDER_NONE:
                return 'none';
            case Border::BORDER_DASHDOT:
                return '1px dashed';
            case Border::BORDER_DASHDOTDOT:
                return '1px dotted';
            case Border::BORDER_DASHED:
                return '1px dashed';
            case Border::BORDER_DOTTED:
                return '1px dotted';
            case Border::BORDER_DOUBLE:
                return '3px double';
            case Border::BORDER_HAIR:
                return '1px solid';
            case Border::BORDER_MEDIUM:
                return '2px solid';
            case Border::BORDER_MEDIUMDASHDOT:
                return '2px dashed';
            case Border::BORDER_MEDIUMDASHDOTDOT:
                return '2px dotted';
            case Border::BORDER_MEDIUMDASHED:
                return '2px dashed';
            case Border::BORDER_SLANTDASHDOT:
                return '2px dashed';
            case Border::BORDER_THICK:
                return '3px solid';
            case Border::BORDER_THIN:
                return '1px solid';
            default:
                // map others to thin
                return '1px solid';
        }
    }

    /**
     * Get sheet index.
     *
     * @return int
     */
    public function getSheetIndex()
    {
        return $this->sheetIndex;
    }

    /**
     * Set sheet index.
     *
     * @param int $pValue Sheet index
     *
     * @return HTML
     */
    public function setSheetIndex($pValue)
    {
        $this->sheetIndex = $pValue;

        return $this;
    }

    /**
     * Get sheet index.
     *
     * @return bool
     */
    public function getGenerateSheetNavigationBlock()
    {
        return $this->generateSheetNavigationBlock;
    }

    /**
     * Set sheet index.
     *
     * @param bool $pValue Flag indicating whether the sheet navigation block should be generated or not
     *
     * @return HTML
     */
    public function setGenerateSheetNavigationBlock($pValue)
    {
        $this->generateSheetNavigationBlock = (bool) $pValue;

        return $this;
    }

    /**
     * Write all sheets (resets sheetIndex to NULL).
     */
    public function writeAllSheets()
    {
        $this->sheetIndex = null;

        return $this;
    }

    /**
     * Generate HTML header.
     *
     * @param bool $pIncludeStyles Include styles?
     *
     * @throws WriterException
     *
     * @return string
     */
    public function generateHTMLHeader($pIncludeStyles = false)
    {
        // Spreadsheet object known?
        if (is_null($this->spreadsheet)) {
            throw new WriterException('Internal Spreadsheet object not set to an instance of an object.');
        }

        // Construct HTML
        $properties = $this->spreadsheet->getProperties();
        $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">' . PHP_EOL;
        $html .= '<!-- Generated by Spreadsheet - https://github.com/PHPOffice/Spreadsheet -->' . PHP_EOL;
        $html .= '<html>' . PHP_EOL;
        $html .= '  <head>' . PHP_EOL;
        $html .= '      <meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . PHP_EOL;
        if ($properties->getTitle() > '') {
            $html .= '      <title>' . htmlspecialchars($properties->getTitle()) . '</title>' . PHP_EOL;
        }
        if ($properties->getCreator() > '') {
            $html .= '      <meta name="author" content="' . htmlspecialchars($properties->getCreator()) . '" />' . PHP_EOL;
        }
        if ($properties->getTitle() > '') {
            $html .= '      <meta name="title" content="' . htmlspecialchars($properties->getTitle()) . '" />' . PHP_EOL;
        }
        if ($properties->getDescription() > '') {
            $html .= '      <meta name="description" content="' . htmlspecialchars($properties->getDescription()) . '" />' . PHP_EOL;
        }
        if ($properties->getSubject() > '') {
            $html .= '      <meta name="subject" content="' . htmlspecialchars($properties->getSubject()) . '" />' . PHP_EOL;
        }
        if ($properties->getKeywords() > '') {
            $html .= '      <meta name="keywords" content="' . htmlspecialchars($properties->getKeywords()) . '" />' . PHP_EOL;
        }
        if ($properties->getCategory() > '') {
            $html .= '      <meta name="category" content="' . htmlspecialchars($properties->getCategory()) . '" />' . PHP_EOL;
        }
        if ($properties->getCompany() > '') {
            $html .= '      <meta name="company" content="' . htmlspecialchars($properties->getCompany()) . '" />' . PHP_EOL;
        }
        if ($properties->getManager() > '') {
            $html .= '      <meta name="manager" content="' . htmlspecialchars($properties->getManager()) . '" />' . PHP_EOL;
        }

        if ($pIncludeStyles) {
            $html .= $this->generateStyles(true);
        }

        $html .= '  </head>' . PHP_EOL;
        $html .= '' . PHP_EOL;
        $html .= '  <body>' . PHP_EOL;

        return $html;
    }

    /**
     * Generate sheet data.
     *
     * @throws WriterException
     *
     * @return string
     */
    public function generateSheetData()
    {
        // Spreadsheet object known?
        if (is_null($this->spreadsheet)) {
            throw new WriterException('Internal Spreadsheet object not set to an instance of an object.');
        }

        // Ensure that Spans have been calculated?
        if ($this->sheetIndex !== null || !$this->spansAreCalculated) {
            $this->calculateSpans();
        }

        // Fetch sheets
        $sheets = [];
        if (is_null($this->sheetIndex)) {
            $sheets = $this->spreadsheet->getAllSheets();
        } else {
            $sheets[] = $this->spreadsheet->getSheet($this->sheetIndex);
        }

        // Construct HTML
        $html = '';

        // Loop all sheets
        $sheetId = 0;
        foreach ($sheets as $sheet) {
            // Write table header
            $html .= $this->generateTableHeader($sheet);

            // Get worksheet dimension
            $dimension = explode(':', $sheet->calculateWorksheetDimension());
            $dimension[0] = Cell::coordinateFromString($dimension[0]);
            $dimension[0][0] = Cell::columnIndexFromString($dimension[0][0]) - 1;
            $dimension[1] = Cell::coordinateFromString($dimension[1]);
            $dimension[1][0] = Cell::columnIndexFromString($dimension[1][0]) - 1;

            // row min,max
            $rowMin = $dimension[0][1];
            $rowMax = $dimension[1][1];

            // calculate start of <tbody>, <thead>
            $tbodyStart = $rowMin;
            $theadStart = $theadEnd = 0; // default: no <thead>    no </thead>
            if ($sheet->getPageSetup()->isRowsToRepeatAtTopSet()) {
                $rowsToRepeatAtTop = $sheet->getPageSetup()->getRowsToRepeatAtTop();

                // we can only support repeating rows that start at top row
                if ($rowsToRepeatAtTop[0] == 1) {
                    $theadStart = $rowsToRepeatAtTop[0];
                    $theadEnd = $rowsToRepeatAtTop[1];
                    $tbodyStart = $rowsToRepeatAtTop[1] + 1;
                }
            }

            // Loop through cells
            $row = $rowMin - 1;
            while ($row++ < $rowMax) {
                // <thead> ?
                if ($row == $theadStart) {
                    $html .= '        <thead>' . PHP_EOL;
                    $cellType = 'th';
                }

                // <tbody> ?
                if ($row == $tbodyStart) {
                    $html .= '        <tbody>' . PHP_EOL;
                    $cellType = 'td';
                }

                // Write row if there are HTML table cells in it
                if (!isset($this->isSpannedRow[$sheet->getParent()->getIndex($sheet)][$row])) {
                    // Start a new rowData
                    $rowData = [];
                    // Loop through columns
                    $column = $dimension[0][0] - 1;
                    while ($column++ < $dimension[1][0]) {
                        // Cell exists?
                        if ($sheet->cellExistsByColumnAndRow($column, $row)) {
                            $rowData[$column] = Cell::stringFromColumnIndex($column) . $row;
                        } else {
                            $rowData[$column] = '';
                        }
                    }
                    $html .= $this->generateRow($sheet, $rowData, $row - 1, $cellType);
                }

                // </thead> ?
                if ($row == $theadEnd) {
                    $html .= '        </thead>' . PHP_EOL;
                }
            }
            $html .= $this->extendRowsForChartsAndImages($sheet, $row);

            // Close table body.
            $html .= '        </tbody>' . PHP_EOL;

            // Write table footer
            $html .= $this->generateTableFooter();

            // Writing PDF?
            if ($this->isPdf) {
                if (is_null($this->sheetIndex) && $sheetId + 1 < $this->spreadsheet->getSheetCount()) {
                    $html .= '<div style="page-break-before:always" />';
                }
            }

            // Next sheet
            ++$sheetId;
        }

        return $html;
    }

    /**
     * Generate sheet tabs.
     *
     * @throws WriterException
     *
     * @return string
     */
    public function generateNavigation()
    {
        // Spreadsheet object known?
        if (is_null($this->spreadsheet)) {
            throw new WriterException('Internal Spreadsheet object not set to an instance of an object.');
        }

        // Fetch sheets
        $sheets = [];
        if (is_null($this->sheetIndex)) {
            $sheets = $this->spreadsheet->getAllSheets();
        } else {
            $sheets[] = $this->spreadsheet->getSheet($this->sheetIndex);
        }

        // Construct HTML
        $html = '';

        // Only if there are more than 1 sheets
        if (count($sheets) > 1) {
            // Loop all sheets
            $sheetId = 0;

            $html .= '<ul class="navigation">' . PHP_EOL;

            foreach ($sheets as $sheet) {
                $html .= '  <li class="sheet' . $sheetId . '"><a href="#sheet' . $sheetId . '">' . $sheet->getTitle() . '</a></li>' . PHP_EOL;
                ++$sheetId;
            }

            $html .= '</ul>' . PHP_EOL;
        }

        return $html;
    }

    private function extendRowsForChartsAndImages(Worksheet $pSheet, $row)
    {
        $rowMax = $row;
        $colMax = 'A';
        if ($this->includeCharts) {
            foreach ($pSheet->getChartCollection() as $chart) {
                if ($chart instanceof Chart) {
                    $chartCoordinates = $chart->getTopLeftPosition();
                    $chartTL = Cell::coordinateFromString($chartCoordinates['cell']);
                    $chartCol = Cell::columnIndexFromString($chartTL[0]);
                    if ($chartTL[1] > $rowMax) {
                        $rowMax = $chartTL[1];
                        if ($chartCol > Cell::columnIndexFromString($colMax)) {
                            $colMax = $chartTL[0];
                        }
                    }
                }
            }
        }

        foreach ($pSheet->getDrawingCollection() as $drawing) {
            if ($drawing instanceof Drawing) {
                $imageTL = Cell::coordinateFromString($drawing->getCoordinates());
                $imageCol = Cell::columnIndexFromString($imageTL[0]);
                if ($imageTL[1] > $rowMax) {
                    $rowMax = $imageTL[1];
                    if ($imageCol > Cell::columnIndexFromString($colMax)) {
                        $colMax = $imageTL[0];
                    }
                }
            }
        }

        // Don't extend rows if not needed
        if ($row === $rowMax) {
            return '';
        }

        $html = '';
        ++$colMax;

        while ($row <= $rowMax) {
            $html .= '<tr>';
            for ($col = 'A'; $col != $colMax; ++$col) {
                $html .= '<td>';
                $html .= $this->writeImageInCell($pSheet, $col . $row);
                if ($this->includeCharts) {
                    $html .= $this->writeChartInCell($pSheet, $col . $row);
                }
                $html .= '</td>';
            }
            ++$row;
            $html .= '</tr>';
        }

        return $html;
    }

    /**
     * Generate image tag in cell.
     *
     * @param Worksheet $pSheet \Drupal\cloud_system\PHPExcel\Worksheet
     * @param string $coordinates Cell coordinates
     *
     * @throws WriterException
     *
     * @return string
     */
    private function writeImageInCell(Worksheet $pSheet, $coordinates)
    {
        // Construct HTML
        $html = '';

        // Write images
        foreach ($pSheet->getDrawingCollection() as $drawing) {
            if ($drawing instanceof Drawing) {
                if ($drawing->getCoordinates() == $coordinates) {
                    $filename = $drawing->getPath();

                    // Strip off eventual '.'
                    if (substr($filename, 0, 1) == '.') {
                        $filename = substr($filename, 1);
                    }

                    // Prepend images root
                    $filename = $this->getImagesRoot() . $filename;

                    // Strip off eventual '.'
                    if (substr($filename, 0, 1) == '.' && substr($filename, 0, 2) != './') {
                        $filename = substr($filename, 1);
                    }

                    // Convert UTF8 data to PCDATA
                    $filename = htmlspecialchars($filename);

                    $html .= PHP_EOL;
                    if ((!$this->embedImages) || ($this->isPdf)) {
                        $imageData = $filename;
                    } else {
                        $imageDetails = getimagesize($filename);
                        if ($fp = fopen($filename, 'rb', 0)) {
                            $picture = fread($fp, filesize($filename));
                            fclose($fp);
                            // base64 encode the binary data, then break it
                            // into chunks according to RFC 2045 semantics
                            $base64 = chunk_split(base64_encode($picture));
                            $imageData = 'data:' . $imageDetails['mime'] . ';base64,' . $base64;
                        } else {
                            $imageData = $filename;
                        }
                    }

                    $html .= '<div style="position: relative;">';
                    $html .= '<img style="position: absolute; z-index: 1; left: ' .
                        $drawing->getOffsetX() . 'px; top: ' . $drawing->getOffsetY() . 'px; width: ' .
                        $drawing->getWidth() . 'px; height: ' . $drawing->getHeight() . 'px;" src="' .
                        $imageData . '" border="0" />';
                    $html .= '</div>';
                }
            } elseif ($drawing instanceof MemoryDrawing) {
                if ($drawing->getCoordinates() != $coordinates) {
                    continue;
                }
                ob_start(); //  Let's start output buffering.
                imagepng($drawing->getImageResource()); //  This will normally output the image, but because of ob_start(), it won't.
                $contents = ob_get_contents(); //  Instead, output above is saved to $contents
                ob_end_clean(); //  End the output buffer.

                $dataUri = 'data:image/jpeg;base64,' . base64_encode($contents);

                //  Because of the nature of tables, width is more important than height.
                //  max-width: 100% ensures that image doesnt overflow containing cell
                //  width: X sets width of supplied image.
                //  As a result, images bigger than cell will be contained and images smaller will not get stretched
                $html .= '<img src="' . $dataUri . '" style="max-width:100%;width:' . $drawing->getWidth() . 'px;" />';
            }
        }

        return $html;
    }

    /**
     * Generate chart tag in cell.
     *
     * @param Worksheet $pSheet \Drupal\cloud_system\PHPExcel\Worksheet
     * @param string $coordinates Cell coordinates
     *
     * @throws WriterException
     *
     * @return string
     */
    private function writeChartInCell(Worksheet $pSheet, $coordinates)
    {
        // Construct HTML
        $html = '';

        // Write charts
        foreach ($pSheet->getChartCollection() as $chart) {
            if ($chart instanceof Chart) {
                $chartCoordinates = $chart->getTopLeftPosition();
                if ($chartCoordinates['cell'] == $coordinates) {
                    $chartFileName = File::sysGetTempDir() . '/' . uniqid() . '.png';
                    if (!$chart->render($chartFileName)) {
                        return;
                    }

                    $html .= PHP_EOL;
                    $imageDetails = getimagesize($chartFileName);
                    if ($fp = fopen($chartFileName, 'rb', 0)) {
                        $picture = fread($fp, filesize($chartFileName));
                        fclose($fp);
                        // base64 encode the binary data, then break it
                        // into chunks according to RFC 2045 semantics
                        $base64 = chunk_split(base64_encode($picture));
                        $imageData = 'data:' . $imageDetails['mime'] . ';base64,' . $base64;

                        $html .= '<div style="position: relative;">';
                        $html .= '<img style="position: absolute; z-index: 1; left: ' . $chartCoordinates['xOffset'] . 'px; top: ' . $chartCoordinates['yOffset'] . 'px; width: ' . $imageDetails[0] . 'px; height: ' . $imageDetails[1] . 'px;" src="' . $imageData . '" border="0" />' . PHP_EOL;
                        $html .= '</div>';

                        unlink($chartFileName);
                    }
                }
            }
        }

        // Return
        return $html;
    }

    /**
     * Generate CSS styles.
     *
     * @param bool $generateSurroundingHTML Generate surrounding HTML tags? (&lt;style&gt; and &lt;/style&gt;)
     *
     * @throws WriterException
     *
     * @return string
     */
    public function generateStyles($generateSurroundingHTML = true)
    {
        // Spreadsheet object known?
        if (is_null($this->spreadsheet)) {
            throw new WriterException('Internal Spreadsheet object not set to an instance of an object.');
        }

        // Build CSS
        $css = $this->buildCSS($generateSurroundingHTML);

        // Construct HTML
        $html = '';

        // Start styles
        if ($generateSurroundingHTML) {
            $html .= '    <style type="text/css">' . PHP_EOL;
            $html .= '      html { ' . $this->assembleCSS($css['html']) . ' }' . PHP_EOL;
        }

        // Write all other styles
        foreach ($css as $styleName => $styleDefinition) {
            if ($styleName != 'html') {
                $html .= '      ' . $styleName . ' { ' . $this->assembleCSS($styleDefinition) . ' }' . PHP_EOL;
            }
        }

        // End styles
        if ($generateSurroundingHTML) {
            $html .= '    </style>' . PHP_EOL;
        }

        // Return
        return $html;
    }

    /**
     * Build CSS styles.
     *
     * @param bool $generateSurroundingHTML Generate surrounding HTML style? (html { })
     *
     * @throws WriterException
     *
     * @return array
     */
    public function buildCSS($generateSurroundingHTML = true)
    {
        // Spreadsheet object known?
        if (is_null($this->spreadsheet)) {
            throw new WriterException('Internal Spreadsheet object not set to an instance of an object.');
        }

        // Cached?
        if (!is_null($this->cssStyles)) {
            return $this->cssStyles;
        }

        // Ensure that spans have been calculated
        if (!$this->spansAreCalculated) {
            $this->calculateSpans();
        }

        // Construct CSS
        $css = [];

        // Start styles
        if ($generateSurroundingHTML) {
            // html { }
            $css['html']['font-family'] = 'Calibri, Arial, Helvetica, sans-serif';
            $css['html']['font-size'] = '11pt';
            $css['html']['background-color'] = 'white';
        }

        // table { }
        $css['table']['border-collapse'] = 'collapse';
        if (!$this->isPdf) {
            $css['table']['page-break-after'] = 'always';
        }

        // .gridlines td { }
        $css['.gridlines td']['border'] = '1px dotted black';
        $css['.gridlines th']['border'] = '1px dotted black';

        // .b {}
        $css['.b']['text-align'] = 'center'; // BOOL

        // .e {}
        $css['.e']['text-align'] = 'center'; // ERROR

        // .f {}
        $css['.f']['text-align'] = 'right'; // FORMULA

        // .inlineStr {}
        $css['.inlineStr']['text-align'] = 'left'; // INLINE

        // .n {}
        $css['.n']['text-align'] = 'right'; // NUMERIC

        // .s {}
        $css['.s']['text-align'] = 'left'; // STRING

        // Calculate cell style hashes
        foreach ($this->spreadsheet->getCellXfCollection() as $index => $style) {
            $css['td.style' . $index] = $this->createCSSStyle($style);
            $css['th.style' . $index] = $this->createCSSStyle($style);
        }

        // Fetch sheets
        $sheets = [];
        if (is_null($this->sheetIndex)) {
            $sheets = $this->spreadsheet->getAllSheets();
        } else {
            $sheets[] = $this->spreadsheet->getSheet($this->sheetIndex);
        }

        // Build styles per sheet
        foreach ($sheets as $sheet) {
            // Calculate hash code
            $sheetIndex = $sheet->getParent()->getIndex($sheet);

            // Build styles
            // Calculate column widths
            $sheet->calculateColumnWidths();

            // col elements, initialize
            $highestColumnIndex = Cell::columnIndexFromString($sheet->getHighestColumn()) - 1;
            $column = -1;
            while ($column++ < $highestColumnIndex) {
                $this->columnWidths[$sheetIndex][$column] = 42; // approximation
                $css['table.sheet' . $sheetIndex . ' col.col' . $column]['width'] = '42pt';
            }

            // col elements, loop through columnDimensions and set width
            foreach ($sheet->getColumnDimensions() as $columnDimension) {
                if (($width = SharedDrawing::cellDimensionToPixels($columnDimension->getWidth(), $this->defaultFont)) >= 0) {
                    $width = SharedDrawing::pixelsToPoints($width);
                    $column = Cell::columnIndexFromString($columnDimension->getColumnIndex()) - 1;
                    $this->columnWidths[$sheetIndex][$column] = $width;
                    $css['table.sheet' . $sheetIndex . ' col.col' . $column]['width'] = $width . 'pt';

                    if ($columnDimension->getVisible() === false) {
                        $css['table.sheet' . $sheetIndex . ' col.col' . $column]['visibility'] = 'collapse';
                        $css['table.sheet' . $sheetIndex . ' col.col' . $column]['*display'] = 'none'; // target IE6+7
                    }
                }
            }

            // Default row height
            $rowDimension = $sheet->getDefaultRowDimension();

            // table.sheetN tr { }
            $css['table.sheet' . $sheetIndex . ' tr'] = [];

            if ($rowDimension->getRowHeight() == -1) {
                $pt_height = SharedFont::getDefaultRowHeightByFont($this->spreadsheet->getDefaultStyle()->getFont());
            } else {
                $pt_height = $rowDimension->getRowHeight();
            }
            $css['table.sheet' . $sheetIndex . ' tr']['height'] = $pt_height . 'pt';
            if ($rowDimension->getVisible() === false) {
                $css['table.sheet' . $sheetIndex . ' tr']['display'] = 'none';
                $css['table.sheet' . $sheetIndex . ' tr']['visibility'] = 'hidden';
            }

            // Calculate row heights
            foreach ($sheet->getRowDimensions() as $rowDimension) {
                $row = $rowDimension->getRowIndex() - 1;

                // table.sheetN tr.rowYYYYYY { }
                $css['table.sheet' . $sheetIndex . ' tr.row' . $row] = [];

                if ($rowDimension->getRowHeight() == -1) {
                    $pt_height = SharedFont::getDefaultRowHeightByFont($this->spreadsheet->getDefaultStyle()->getFont());
                } else {
                    $pt_height = $rowDimension->getRowHeight();
                }
                $css['table.sheet' . $sheetIndex . ' tr.row' . $row]['height'] = $pt_height . 'pt';
                if ($rowDimension->getVisible() === false) {
                    $css['table.sheet' . $sheetIndex . ' tr.row' . $row]['display'] = 'none';
                    $css['table.sheet' . $sheetIndex . ' tr.row' . $row]['visibility'] = 'hidden';
                }
            }
        }

        // Cache
        if (is_null($this->cssStyles)) {
            $this->cssStyles = $css;
        }

        // Return
        return $css;
    }

    /**
     * Create CSS style.
     *
     * @param Style $pStyle
     *
     * @return array
     */
    private function createCSSStyle(Style $pStyle)
    {
        // Construct CSS
        $css = '';

        // Create CSS
        $css = array_merge(
            $this->createCSSStyleAlignment($pStyle->getAlignment()),
            $this->createCSSStyleBorders($pStyle->getBorders()),
            $this->createCSSStyleFont($pStyle->getFont()),
            $this->createCSSStyleFill($pStyle->getFill())
        );

        // Return
        return $css;
    }

    /**
     * Create CSS style (\Drupal\cloud_system\PHPExcel\Style\Alignment).
     *
     * @param Alignment $pStyle \Drupal\cloud_system\PHPExcel\Style\Alignment
     *
     * @return array
     */
    private function createCSSStyleAlignment(Alignment $pStyle)
    {
        // Construct CSS
        $css = [];

        // Create CSS
        $css['vertical-align'] = $this->mapVAlign($pStyle->getVertical());
        if ($textAlign = $this->mapHAlign($pStyle->getHorizontal())) {
            $css['text-align'] = $textAlign;
            if (in_array($textAlign, ['left', 'right'])) {
                $css['padding-' . $textAlign] = (string) ((int) $pStyle->getIndent() * 9) . 'px';
            }
        }

        return $css;
    }

    /**
     * Create CSS style (\Drupal\cloud_system\PHPExcel\Style\Font).
     *
     * @param Font $pStyle
     *
     * @return array
     */
    private function createCSSStyleFont(Font $pStyle)
    {
        // Construct CSS
        $css = [];

        // Create CSS
        if ($pStyle->getBold()) {
            $css['font-weight'] = 'bold';
        }
        if ($pStyle->getUnderline() != Font::UNDERLINE_NONE && $pStyle->getStrikethrough()) {
            $css['text-decoration'] = 'underline line-through';
        } elseif ($pStyle->getUnderline() != Font::UNDERLINE_NONE) {
            $css['text-decoration'] = 'underline';
        } elseif ($pStyle->getStrikethrough()) {
            $css['text-decoration'] = 'line-through';
        }
        if ($pStyle->getItalic()) {
            $css['font-style'] = 'italic';
        }

        $css['color'] = '#' . $pStyle->getColor()->getRGB();
        $css['font-family'] = '\'' . $pStyle->getName() . '\'';
        $css['font-size'] = $pStyle->getSize() . 'pt';

        return $css;
    }

    /**
     * Create CSS style (Borders).
     *
     * @param Borders $pStyle Borders
     *
     * @return array
     */
    private function createCSSStyleBorders(Borders $pStyle)
    {
        // Construct CSS
        $css = [];

        // Create CSS
        $css['border-bottom'] = $this->createCSSStyleBorder($pStyle->getBottom());
        $css['border-top'] = $this->createCSSStyleBorder($pStyle->getTop());
        $css['border-left'] = $this->createCSSStyleBorder($pStyle->getLeft());
        $css['border-right'] = $this->createCSSStyleBorder($pStyle->getRight());

        return $css;
    }

    /**
     * Create CSS style (Border).
     *
     * @param Border $pStyle Border
     *
     * @return string
     */
    private function createCSSStyleBorder(Border $pStyle)
    {
        //    Create CSS - add !important to non-none border styles for merged cells
        $borderStyle = $this->mapBorderStyle($pStyle->getBorderStyle());
        $css = $borderStyle . ' #' . $pStyle->getColor()->getRGB() . (($borderStyle == 'none') ? '' : ' !important');

        return $css;
    }

    /**
     * Create CSS style (Fill).
     *
     * @param Fill $pStyle Fill
     *
     * @return array
     */
    private function createCSSStyleFill(Fill $pStyle)
    {
        // Construct HTML
        $css = [];

        // Create CSS
        $value = $pStyle->getFillType() == Fill::FILL_NONE ?
            'white' : '#' . $pStyle->getStartColor()->getRGB();
        $css['background-color'] = $value;

        return $css;
    }

    /**
     * Generate HTML footer.
     */
    public function generateHTMLFooter()
    {
        // Construct HTML
        $html = '';
        $html .= '  </body>' . PHP_EOL;
        $html .= '</html>' . PHP_EOL;

        return $html;
    }

    /**
     * Generate table header.
     *
     * @param Worksheet $pSheet The worksheet for the table we are writing
     *
     * @throws WriterException
     *
     * @return string
     */
    private function generateTableHeader($pSheet)
    {
        $sheetIndex = $pSheet->getParent()->getIndex($pSheet);

        // Construct HTML
        $html = '';
        $html .= $this->setMargins($pSheet);

        if (!$this->useInlineCss) {
            $gridlines = $pSheet->getShowGridlines() ? ' gridlines' : '';
            $html .= '    <table border="0" cellpadding="0" cellspacing="0" id="sheet' . $sheetIndex . '" class="sheet' . $sheetIndex . $gridlines . '">' . PHP_EOL;
        } else {
            $style = isset($this->cssStyles['table']) ?
                $this->assembleCSS($this->cssStyles['table']) : '';

            if ($this->isPdf && $pSheet->getShowGridlines()) {
                $html .= '    <table border="1" cellpadding="1" id="sheet' . $sheetIndex . '" cellspacing="1" style="' . $style . '">' . PHP_EOL;
            } else {
                $html .= '    <table border="0" cellpadding="1" id="sheet' . $sheetIndex . '" cellspacing="0" style="' . $style . '">' . PHP_EOL;
            }
        }

        // Write <col> elements
        $highestColumnIndex = Cell::columnIndexFromString($pSheet->getHighestColumn()) - 1;
        $i = -1;
        while ($i++ < $highestColumnIndex) {
            if (!$this->isPdf) {
                if (!$this->useInlineCss) {
                    $html .= '        <col class="col' . $i . '">' . PHP_EOL;
                } else {
                    $style = isset($this->cssStyles['table.sheet' . $sheetIndex . ' col.col' . $i]) ?
                        $this->assembleCSS($this->cssStyles['table.sheet' . $sheetIndex . ' col.col' . $i]) : '';
                    $html .= '        <col style="' . $style . '">' . PHP_EOL;
                }
            }
        }

        return $html;
    }

    /**
     * Generate table footer.
     *
     * @throws WriterException
     */
    private function generateTableFooter()
    {
        $html = '    </table>' . PHP_EOL;

        return $html;
    }

    /**
     * Generate row.
     *
     * @param Worksheet $pSheet \Drupal\cloud_system\PHPExcel\Worksheet
     * @param array $pValues Array containing cells in a row
     * @param int $pRow Row number (0-based)
     * @param mixed $cellType eg: 'td'
     *
     * @throws WriterException
     *
     * @return string
     */
    private function generateRow(Worksheet $pSheet, array $pValues, $pRow, $cellType)
    {
        // Construct HTML
        $html = '';

        // Sheet index
        $sheetIndex = $pSheet->getParent()->getIndex($pSheet);

        // DomPDF and breaks
        if ($this->isPdf && count($pSheet->getBreaks()) > 0) {
            $breaks = $pSheet->getBreaks();

            // check if a break is needed before this row
            if (isset($breaks['A' . $pRow])) {
                // close table: </table>
                $html .= $this->generateTableFooter();

                // insert page break
                $html .= '<div style="page-break-before:always" />';

                // open table again: <table> + <col> etc.
                $html .= $this->generateTableHeader($pSheet);
            }
        }

        // Write row start
        if (!$this->useInlineCss) {
            $html .= '          <tr class="row' . $pRow . '">' . PHP_EOL;
        } else {
            $style = isset($this->cssStyles['table.sheet' . $sheetIndex . ' tr.row' . $pRow])
                ? $this->assembleCSS($this->cssStyles['table.sheet' . $sheetIndex . ' tr.row' . $pRow]) : '';

            $html .= '          <tr style="' . $style . '">' . PHP_EOL;
        }

        // Write cells
        $colNum = 0;
        foreach ($pValues as $cellAddress) {
            $cell = ($cellAddress > '') ? $pSheet->getCell($cellAddress) : '';
            $coordinate = Cell::stringFromColumnIndex($colNum) . ($pRow + 1);
            if (!$this->useInlineCss) {
                $cssClass = '';
                $cssClass = 'column' . $colNum;
            } else {
                $cssClass = [];
                if ($cellType == 'th') {
                    if (isset($this->cssStyles['table.sheet' . $sheetIndex . ' th.column' . $colNum])) {
                        $this->cssStyles['table.sheet' . $sheetIndex . ' th.column' . $colNum];
                    }
                } else {
                    if (isset($this->cssStyles['table.sheet' . $sheetIndex . ' td.column' . $colNum])) {
                        $this->cssStyles['table.sheet' . $sheetIndex . ' td.column' . $colNum];
                    }
                }
            }
            $colSpan = 1;
            $rowSpan = 1;

            // initialize
            $cellData = '&nbsp;';

            // Cell
            if ($cell instanceof Cell) {
                $cellData = '';
                if (is_null($cell->getParent())) {
                    $cell->attach($pSheet);
                }
                // Value
                if ($cell->getValue() instanceof RichText) {
                    // Loop through rich text elements
                    $elements = $cell->getValue()->getRichTextElements();
                    foreach ($elements as $element) {
                        // Rich text start?
                        if ($element instanceof RichText\Run) {
                            $cellData .= '<span style="' . $this->assembleCSS($this->createCSSStyleFont($element->getFont())) . '">';

                            if ($element->getFont()->getSuperScript()) {
                                $cellData .= '<sup>';
                            } elseif ($element->getFont()->getSubScript()) {
                                $cellData .= '<sub>';
                            }
                        }

                        // Convert UTF8 data to PCDATA
                        $cellText = $element->getText();
                        $cellData .= htmlspecialchars($cellText);

                        if ($element instanceof RichText\Run) {
                            if ($element->getFont()->getSuperScript()) {
                                $cellData .= '</sup>';
                            } elseif ($element->getFont()->getSubScript()) {
                                $cellData .= '</sub>';
                            }

                            $cellData .= '</span>';
                        }
                    }
                } else {
                    if ($this->preCalculateFormulas) {
                        $cellData = NumberFormat::toFormattedString(
                            $cell->getCalculatedValue(),
                            $pSheet->getParent()->getCellXfByIndex($cell->getXfIndex())->getNumberFormat()->getFormatCode(),
                            [$this, 'formatColor']
                        );
                    } else {
                        $cellData = NumberFormat::toFormattedString(
                            $cell->getValue(),
                            $pSheet->getParent()->getCellXfByIndex($cell->getXfIndex())->getNumberFormat()->getFormatCode(),
                            [$this, 'formatColor']
                        );
                    }
                    $cellData = htmlspecialchars($cellData);
                    if ($pSheet->getParent()->getCellXfByIndex($cell->getXfIndex())->getFont()->getSuperScript()) {
                        $cellData = '<sup>' . $cellData . '</sup>';
                    } elseif ($pSheet->getParent()->getCellXfByIndex($cell->getXfIndex())->getFont()->getSubScript()) {
                        $cellData = '<sub>' . $cellData . '</sub>';
                    }
                }

                // Converts the cell content so that spaces occuring at beginning of each new line are replaced by &nbsp;
                // Example: "  Hello\n to the world" is converted to "&nbsp;&nbsp;Hello\n&nbsp;to the world"
                $cellData = preg_replace('/(?m)(?:^|\\G) /', '&nbsp;', $cellData);

                // convert newline "\n" to '<br>'
                $cellData = nl2br($cellData);

                // Extend CSS class?
                if (!$this->useInlineCss) {
                    $cssClass .= ' style' . $cell->getXfIndex();
                    $cssClass .= ' ' . $cell->getDataType();
                } else {
                    if ($cellType == 'th') {
                        if (isset($this->cssStyles['th.style' . $cell->getXfIndex()])) {
                            $cssClass = array_merge($cssClass, $this->cssStyles['th.style' . $cell->getXfIndex()]);
                        }
                    } else {
                        if (isset($this->cssStyles['td.style' . $cell->getXfIndex()])) {
                            $cssClass = array_merge($cssClass, $this->cssStyles['td.style' . $cell->getXfIndex()]);
                        }
                    }

                    // General horizontal alignment: Actual horizontal alignment depends on dataType
                    $sharedStyle = $pSheet->getParent()->getCellXfByIndex($cell->getXfIndex());
                    if ($sharedStyle->getAlignment()->getHorizontal() == Alignment::HORIZONTAL_GENERAL
                        && isset($this->cssStyles['.' . $cell->getDataType()]['text-align'])
                    ) {
                        $cssClass['text-align'] = $this->cssStyles['.' . $cell->getDataType()]['text-align'];
                    }
                }
            }

            // Hyperlink?
            if ($pSheet->hyperlinkExists($coordinate) && !$pSheet->getHyperlink($coordinate)->isInternal()) {
                $cellData = '<a href="' . htmlspecialchars($pSheet->getHyperlink($coordinate)->getUrl()) . '" title="' . htmlspecialchars($pSheet->getHyperlink($coordinate)->getTooltip()) . '">' . $cellData . '</a>';
            }

            // Should the cell be written or is it swallowed by a rowspan or colspan?
            $writeCell = !(isset($this->isSpannedCell[$pSheet->getParent()->getIndex($pSheet)][$pRow + 1][$colNum])
                && $this->isSpannedCell[$pSheet->getParent()->getIndex($pSheet)][$pRow + 1][$colNum]);

            // Colspan and Rowspan
            $colspan = 1;
            $rowspan = 1;
            if (isset($this->isBaseCell[$pSheet->getParent()->getIndex($pSheet)][$pRow + 1][$colNum])) {
                $spans = $this->isBaseCell[$pSheet->getParent()->getIndex($pSheet)][$pRow + 1][$colNum];
                $rowSpan = $spans['rowspan'];
                $colSpan = $spans['colspan'];

                //    Also apply style from last cell in merge to fix borders -
                //        relies on !important for non-none border declarations in createCSSStyleBorder
                $endCellCoord = Cell::stringFromColumnIndex($colNum + $colSpan - 1) . ($pRow + $rowSpan);
                if (!$this->useInlineCss) {
                    $cssClass .= ' style' . $pSheet->getCell($endCellCoord)->getXfIndex();
                }
            }

            // Write
            if ($writeCell) {
                // Column start
                $html .= '            <' . $cellType;
                if (!$this->useInlineCss) {
                    $html .= ' class="' . $cssClass . '"';
                } else {
                    //** Necessary redundant code for the sake of \Drupal\cloud_system\PHPExcel\Writer\Pdf **
                    // We must explicitly write the width of the <td> element because TCPDF
                    // does not recognize e.g. <col style="width:42pt">
                    $width = 0;
                    $i = $colNum - 1;
                    $e = $colNum + $colSpan - 1;
                    while ($i++ < $e) {
                        if (isset($this->columnWidths[$sheetIndex][$i])) {
                            $width += $this->columnWidths[$sheetIndex][$i];
                        }
                    }
                    $cssClass['width'] = $width . 'pt';

                    // We must also explicitly write the height of the <td> element because TCPDF
                    // does not recognize e.g. <tr style="height:50pt">
                    if (isset($this->cssStyles['table.sheet' . $sheetIndex . ' tr.row' . $pRow]['height'])) {
                        $height = $this->cssStyles['table.sheet' . $sheetIndex . ' tr.row' . $pRow]['height'];
                        $cssClass['height'] = $height;
                    }
                    //** end of redundant code **

                    $html .= ' style="' . $this->assembleCSS($cssClass) . '"';
                }
                if ($colSpan > 1) {
                    $html .= ' colspan="' . $colSpan . '"';
                }
                if ($rowSpan > 1) {
                    $html .= ' rowspan="' . $rowSpan . '"';
                }
                $html .= '>';

                // Image?
                $html .= $this->writeImageInCell($pSheet, $coordinate);

                // Chart?
                if ($this->includeCharts) {
                    $html .= $this->writeChartInCell($pSheet, $coordinate);
                }

                // Cell data
                $html .= $cellData;

                // Column end
                $html .= '</' . $cellType . '>' . PHP_EOL;
            }

            // Next column
            ++$colNum;
        }

        // Write row end
        $html .= '          </tr>' . PHP_EOL;

        // Return
        return $html;
    }

    /**
     * Takes array where of CSS properties / values and converts to CSS string.
     *
     * @param array
     * @param mixed $pValue
     *
     * @return string
     */
    private function assembleCSS($pValue = [])
    {
        $pairs = [];
        foreach ($pValue as $property => $value) {
            $pairs[] = $property . ':' . $value;
        }
        $string = implode('; ', $pairs);

        return $string;
    }

    /**
     * Get images root.
     *
     * @return string
     */
    public function getImagesRoot()
    {
        return $this->imagesRoot;
    }

    /**
     * Set images root.
     *
     * @param string $pValue
     *
     * @return HTML
     */
    public function setImagesRoot($pValue)
    {
        $this->imagesRoot = $pValue;

        return $this;
    }

    /**
     * Get embed images.
     *
     * @return bool
     */
    public function getEmbedImages()
    {
        return $this->embedImages;
    }

    /**
     * Set embed images.
     *
     * @param bool $pValue
     *
     * @return HTML
     */
    public function setEmbedImages($pValue)
    {
        $this->embedImages = $pValue;

        return $this;
    }

    /**
     * Get use inline CSS?
     *
     * @return bool
     */
    public function getUseInlineCss()
    {
        return $this->useInlineCss;
    }

    /**
     * Set use inline CSS?
     *
     * @param bool $pValue
     *
     * @return HTML
     */
    public function setUseInlineCss($pValue)
    {
        $this->useInlineCss = $pValue;

        return $this;
    }

    /**
     * Add color to formatted string as inline style.
     *
     * @param string $pValue Plain formatted value without color
     * @param string $pFormat Format code
     *
     * @return string
     */
    public function formatColor($pValue, $pFormat)
    {
        // Color information, e.g. [Red] is always at the beginning
        $color = null; // initialize
        $matches = [];

        $color_regex = '/^\\[[a-zA-Z]+\\]/';
        if (preg_match($color_regex, $pFormat, $matches)) {
            $color = str_replace('[', '', $matches[0]);
            $color = str_replace(']', '', $color);
            $color = strtolower($color);
        }

        // convert to PCDATA
        $value = htmlspecialchars($pValue);

        // color span tag
        if ($color !== null) {
            $value = '<span style="color:' . $color . '">' . $value . '</span>';
        }

        return $value;
    }

    /**
     * Calculate information about HTML colspan and rowspan which is not always the same as Excel's.
     */
    private function calculateSpans()
    {
        // Identify all cells that should be omitted in HTML due to cell merge.
        // In HTML only the upper-left cell should be written and it should have
        //   appropriate rowspan / colspan attribute
        $sheetIndexes = $this->sheetIndex !== null ?
            [$this->sheetIndex] : range(0, $this->spreadsheet->getSheetCount() - 1);

        foreach ($sheetIndexes as $sheetIndex) {
            $sheet = $this->spreadsheet->getSheet($sheetIndex);

            $candidateSpannedRow = [];

            // loop through all Excel merged cells
            foreach ($sheet->getMergeCells() as $cells) {
                list($cells) = Cell::splitRange($cells);
                $first = $cells[0];
                $last = $cells[1];

                list($fc, $fr) = Cell::coordinateFromString($first);
                $fc = Cell::columnIndexFromString($fc) - 1;

                list($lc, $lr) = Cell::coordinateFromString($last);
                $lc = Cell::columnIndexFromString($lc) - 1;

                // loop through the individual cells in the individual merge
                $r = $fr - 1;
                while ($r++ < $lr) {
                    // also, flag this row as a HTML row that is candidate to be omitted
                    $candidateSpannedRow[$r] = $r;

                    $c = $fc - 1;
                    while ($c++ < $lc) {
                        if (!($c == $fc && $r == $fr)) {
                            // not the upper-left cell (should not be written in HTML)
                            $this->isSpannedCell[$sheetIndex][$r][$c] = [
                                'baseCell' => [$fr, $fc],
                            ];
                        } else {
                            // upper-left is the base cell that should hold the colspan/rowspan attribute
                            $this->isBaseCell[$sheetIndex][$r][$c] = [
                                'xlrowspan' => $lr - $fr + 1, // Excel rowspan
                                'rowspan' => $lr - $fr + 1, // HTML rowspan, value may change
                                'xlcolspan' => $lc - $fc + 1, // Excel colspan
                                'colspan' => $lc - $fc + 1, // HTML colspan, value may change
                            ];
                        }
                    }
                }
            }

            // Identify which rows should be omitted in HTML. These are the rows where all the cells
            //   participate in a merge and the where base cells are somewhere above.
            $countColumns = Cell::columnIndexFromString($sheet->getHighestColumn());
            foreach ($candidateSpannedRow as $rowIndex) {
                if (isset($this->isSpannedCell[$sheetIndex][$rowIndex])) {
                    if (count($this->isSpannedCell[$sheetIndex][$rowIndex]) == $countColumns) {
                        $this->isSpannedRow[$sheetIndex][$rowIndex] = $rowIndex;
                    }
                }
            }

            // For each of the omitted rows we found above, the affected rowspans should be subtracted by 1
            if (isset($this->isSpannedRow[$sheetIndex])) {
                foreach ($this->isSpannedRow[$sheetIndex] as $rowIndex) {
                    $adjustedBaseCells = [];
                    $c = -1;
                    $e = $countColumns - 1;
                    while ($c++ < $e) {
                        $baseCell = $this->isSpannedCell[$sheetIndex][$rowIndex][$c]['baseCell'];

                        if (!in_array($baseCell, $adjustedBaseCells)) {
                            // subtract rowspan by 1
                            --$this->isBaseCell[$sheetIndex][$baseCell[0]][$baseCell[1]]['rowspan'];
                            $adjustedBaseCells[] = $baseCell;
                        }
                    }
                }
            }

            // TODO: Same for columns
        }

        // We have calculated the spans
        $this->spansAreCalculated = true;
    }

    private function setMargins(Worksheet $pSheet)
    {
        $htmlPage = '@page { ';
        $htmlBody = 'body { ';

        $left = StringHelper::formatNumber($pSheet->getPageMargins()->getLeft()) . 'in; ';
        $htmlPage .= 'margin-left: ' . $left;
        $htmlBody .= 'margin-left: ' . $left;
        $right = StringHelper::formatNumber($pSheet->getPageMargins()->getRight()) . 'in; ';
        $htmlPage .= 'margin-right: ' . $right;
        $htmlBody .= 'margin-right: ' . $right;
        $top = StringHelper::formatNumber($pSheet->getPageMargins()->getTop()) . 'in; ';
        $htmlPage .= 'margin-top: ' . $top;
        $htmlBody .= 'margin-top: ' . $top;
        $bottom = StringHelper::formatNumber($pSheet->getPageMargins()->getBottom()) . 'in; ';
        $htmlPage .= 'margin-bottom: ' . $bottom;
        $htmlBody .= 'margin-bottom: ' . $bottom;

        $htmlPage .= "}\n";
        $htmlBody .= "}\n";

        return "<style>\n" . $htmlPage . $htmlBody . "</style>\n";
    }
}