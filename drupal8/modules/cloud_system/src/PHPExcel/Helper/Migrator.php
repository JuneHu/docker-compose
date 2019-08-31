<?php

namespace Drupal\cloud_system\PHPExcel\Helper;

class Migrator
{
    /**
     * Return the ordered mapping from old PHPExcel class names to new PhpSpreadsheet one.
     *
     * @return string[]
     */
    public function getMapping()
    {
        // Order matters here, we should have the deepest namespaces first (the most "unique" strings)
        $mapping = [
            'PHPExcel_Shared_Escher_DggContainer_BstoreContainer_BSE_Blip' => '\\Drupal\\cloud_system\\PHPExcel\\Shared\\Escher\\DggContainer\\BstoreContainer\\BSE\\Blip',
            'PHPExcel_Shared_Escher_DgContainer_SpgrContainer_SpContainer' => '\\Drupal\\cloud_system\\PHPExcel\\Shared\\Escher\\DgContainer\\SpgrContainer\\SpContainer',
            'PHPExcel_Shared_Escher_DggContainer_BstoreContainer_BSE' => '\\Drupal\\cloud_system\\PHPExcel\\Shared\\Escher\\DggContainer\\BstoreContainer\\BSE',
            'PHPExcel_Shared_Escher_DgContainer_SpgrContainer' => '\\Drupal\\cloud_system\\PHPExcel\\Shared\\Escher\\DgContainer\\SpgrContainer',
            'PHPExcel_Shared_Escher_DggContainer_BstoreContainer' => '\\Drupal\\cloud_system\\PHPExcel\\Shared\\Escher\\DggContainer\\BstoreContainer',
            'PHPExcel_Shared_OLE_PPS_File' => '\\Drupal\\cloud_system\\PHPExcel\\Shared\\OLE\\PPS\\File',
            'PHPExcel_Shared_OLE_PPS_Root' => '\\Drupal\\cloud_system\\PHPExcel\\Shared\\OLE\\PPS\\Root',
            'PHPExcel_Worksheet_AutoFilter_Column_Rule' => '\\Drupal\\cloud_system\\PHPExcel\\Worksheet\\AutoFilter\\Column\\Rule',
            'PHPExcel_Writer_OpenDocument_Cell_Comment' => '\\Drupal\\cloud_system\\PHPExcel\\Writer\\Ods\\Cell\\Comment',
            'PHPExcel_Calculation_Token_Stack' => '\\Drupal\\cloud_system\\PHPExcel\\Calculation\\Token\\Stack',
            'PHPExcel_Chart_Renderer_jpgraph' => '\\Drupal\\cloud_system\\PHPExcel\\Chart\\Renderer\\JpGraph',
            'PHPExcel_Reader_Excel5_Escher' => '\\Drupal\\cloud_system\\PHPExcel\\Reader\\Xls\\Escher',
            'PHPExcel_Reader_Excel5_MD5' => '\\Drupal\\cloud_system\\PHPExcel\\Reader\\Xls\\MD5',
            'PHPExcel_Reader_Excel5_RC4' => '\\Drupal\\cloud_system\\PHPExcel\\Reader\\Xls\\RC4',
            'PHPExcel_Reader_Excel2007_Chart' => '\\Drupal\\cloud_system\\PHPExcel\\Reader\\Xlsx\\Chart',
            'PHPExcel_Reader_Excel2007_Theme' => '\\Drupal\\cloud_system\\PHPExcel\\Reader\\Xlsx\\Theme',
            'PHPExcel_Shared_Escher_DgContainer' => '\\Drupal\\cloud_system\\PHPExcel\\Shared\\Escher\\DgContainer',
            'PHPExcel_Shared_Escher_DggContainer' => '\\Drupal\\cloud_system\\PHPExcel\\Shared\\Escher\\DggContainer',
            'CholeskyDecomposition' => '\\Drupal\\cloud_system\\PHPExcel\\Shared\\JAMA\\CholeskyDecomposition',
            'EigenvalueDecomposition' => '\\Drupal\\cloud_system\\PHPExcel\\Shared\\JAMA\\EigenvalueDecomposition',
            'PHPExcel_Shared_JAMA_LUDecomposition' => '\\Drupal\\cloud_system\\PHPExcel\\Shared\\JAMA\\LUDecomposition',
            'PHPExcel_Shared_JAMA_Matrix' => '\\Drupal\\cloud_system\\PHPExcel\\Shared\\JAMA\\Matrix',
            'QRDecomposition' => '\\Drupal\\cloud_system\\PHPExcel\\Shared\\JAMA\\QRDecomposition',
            'PHPExcel_Shared_JAMA_QRDecomposition' => '\\Drupal\\cloud_system\\PHPExcel\\Shared\\JAMA\\QRDecomposition',
            'SingularValueDecomposition' => '\\Drupal\\cloud_system\\PHPExcel\\Shared\\JAMA\\SingularValueDecomposition',
            'PHPExcel_Shared_OLE_ChainedBlockStream' => '\\Drupal\\cloud_system\\PHPExcel\\Shared\\OLE\\ChainedBlockStream',
            'PHPExcel_Shared_OLE_PPS' => '\\Drupal\\cloud_system\\PHPExcel\\Shared\\OLE\\PPS',
            'PHPExcel_Best_Fit' => '\\Drupal\\cloud_system\\PHPExcel\\Shared\\Trend\\BestFit',
            'PHPExcel_Exponential_Best_Fit' => '\\Drupal\\cloud_system\\PHPExcel\\Shared\\Trend\\ExponentialBestFit',
            'PHPExcel_Linear_Best_Fit' => '\\Drupal\\cloud_system\\PHPExcel\\Shared\\Trend\\LinearBestFit',
            'PHPExcel_Logarithmic_Best_Fit' => '\\Drupal\\cloud_system\\PHPExcel\\Shared\\Trend\\LogarithmicBestFit',
            'polynomialBestFit' => '\\Drupal\\cloud_system\\PHPExcel\\Shared\\Trend\\PolynomialBestFit',
            'PHPExcel_Polynomial_Best_Fit' => '\\Drupal\\cloud_system\\PHPExcel\\Shared\\Trend\\PolynomialBestFit',
            'powerBestFit' => '\\Drupal\\cloud_system\\PHPExcel\\Shared\\Trend\\PowerBestFit',
            'PHPExcel_Power_Best_Fit' => '\\Drupal\\cloud_system\\PHPExcel\\Shared\\Trend\\PowerBestFit',
            'trendClass' => '\\Drupal\\cloud_system\\PHPExcel\\Shared\\Trend\\Trend',
            'PHPExcel_Worksheet_AutoFilter_Column' => '\\Drupal\\cloud_system\\PHPExcel\\Worksheet\\AutoFilter\\Column',
            'PHPExcel_Worksheet_Drawing_Shadow' => '\\Drupal\\cloud_system\\PHPExcel\\Worksheet\\Drawing\\Shadow',
            'PHPExcel_Writer_OpenDocument_Content' => '\\Drupal\\cloud_system\\PHPExcel\\Writer\\Ods\\Content',
            'PHPExcel_Writer_OpenDocument_Meta' => '\\Drupal\\cloud_system\\PHPExcel\\Writer\\Ods\\Meta',
            'PHPExcel_Writer_OpenDocument_MetaInf' => '\\Drupal\\cloud_system\\PHPExcel\\Writer\\Ods\\MetaInf',
            'PHPExcel_Writer_OpenDocument_Mimetype' => '\\Drupal\\cloud_system\\PHPExcel\\Writer\\Ods\\Mimetype',
            'PHPExcel_Writer_OpenDocument_Settings' => '\\Drupal\\cloud_system\\PHPExcel\\Writer\\Ods\\Settings',
            'PHPExcel_Writer_OpenDocument_Styles' => '\\Drupal\\cloud_system\\PHPExcel\\Writer\\Ods\\Styles',
            'PHPExcel_Writer_OpenDocument_Thumbnails' => '\\Drupal\\cloud_system\\PHPExcel\\Writer\\Ods\\Thumbnails',
            'PHPExcel_Writer_OpenDocument_WriterPart' => '\\Drupal\\cloud_system\\PHPExcel\\Writer\\Ods\\WriterPart',
            'PHPExcel_Writer_PDF_Core' => '\\Drupal\\cloud_system\\PHPExcel\\Writer\\Pdf\\Core',
            'PHPExcel_Writer_PDF_DomPDF' => '\\Drupal\\cloud_system\\PHPExcel\\Writer\\Pdf\\DomPDF',
            'PHPExcel_Writer_PDF_mPDF' => '\\Drupal\\cloud_system\\PHPExcel\\Writer\\Pdf\\MPDF',
            'PHPExcel_Writer_PDF_tcPDF' => '\\Drupal\\cloud_system\\PHPExcel\\Writer\\Pdf\\TcPDF',
            'PHPExcel_Writer_Excel5_BIFFwriter' => '\\Drupal\\cloud_system\\PHPExcel\\Writer\\Xls\\BIFFwriter',
            'PHPExcel_Writer_Excel5_Escher' => '\\Drupal\\cloud_system\\PHPExcel\\Writer\\Xls\\Escher',
            'PHPExcel_Writer_Excel5_Font' => '\\Drupal\\cloud_system\\PHPExcel\\Writer\\Xls\\Font',
            'PHPExcel_Writer_Excel5_Parser' => '\\Drupal\\cloud_system\\PHPExcel\\Writer\\Xls\\Parser',
            'PHPExcel_Writer_Excel5_Workbook' => '\\Drupal\\cloud_system\\PHPExcel\\Writer\\Xls\\Workbook',
            'PHPExcel_Writer_Excel5_Worksheet' => '\\Drupal\\cloud_system\\PHPExcel\\Writer\\Xls\\Worksheet',
            'PHPExcel_Writer_Excel5_Xf' => '\\Drupal\\cloud_system\\PHPExcel\\Writer\\Xls\\Xf',
            'PHPExcel_Writer_Excel2007_Chart' => '\\Drupal\\cloud_system\\PHPExcel\\Writer\\Xlsx\\Chart',
            'PHPExcel_Writer_Excel2007_Comments' => '\\Drupal\\cloud_system\\PHPExcel\\Writer\\Xlsx\\Comments',
            'PHPExcel_Writer_Excel2007_ContentTypes' => '\\Drupal\\cloud_system\\PHPExcel\\Writer\\Xlsx\\ContentTypes',
            'PHPExcel_Writer_Excel2007_DocProps' => '\\Drupal\\cloud_system\\PHPExcel\\Writer\\Xlsx\\DocProps',
            'PHPExcel_Writer_Excel2007_Drawing' => '\\Drupal\\cloud_system\\PHPExcel\\Writer\\Xlsx\\Drawing',
            'PHPExcel_Writer_Excel2007_Rels' => '\\Drupal\\cloud_system\\PHPExcel\\Writer\\Xlsx\\Rels',
            'PHPExcel_Writer_Excel2007_RelsRibbon' => '\\Drupal\\cloud_system\\PHPExcel\\Writer\\Xlsx\\RelsRibbon',
            'PHPExcel_Writer_Excel2007_RelsVBA' => '\\Drupal\\cloud_system\\PHPExcel\\Writer\\Xlsx\\RelsVBA',
            'PHPExcel_Writer_Excel2007_StringTable' => '\\Drupal\\cloud_system\\PHPExcel\\Writer\\Xlsx\\StringTable',
            'PHPExcel_Writer_Excel2007_Style' => '\\Drupal\\cloud_system\\PHPExcel\\Writer\\Xlsx\\Style',
            'PHPExcel_Writer_Excel2007_Theme' => '\\Drupal\\cloud_system\\PHPExcel\\Writer\\Xlsx\\Theme',
            'PHPExcel_Writer_Excel2007_Workbook' => '\\Drupal\\cloud_system\\PHPExcel\\Writer\\Xlsx\\Workbook',
            'PHPExcel_Writer_Excel2007_Worksheet' => '\\Drupal\\cloud_system\\PHPExcel\\Writer\\Xlsx\\Worksheet',
            'PHPExcel_Writer_Excel2007_WriterPart' => '\\Drupal\\cloud_system\\PHPExcel\\Writer\\Xlsx\\WriterPart',
            'PHPExcel_CachedObjectStorage_CacheBase' => '\\Drupal\\cloud_system\\PHPExcel\\Collection\\Cells',
            'PHPExcel_CalcEngine_CyclicReferenceStack' => '\\Drupal\\cloud_system\\PHPExcel\\CalcEngine\\CyclicReferenceStack',
            'PHPExcel_CalcEngine_Logger' => '\\Drupal\\cloud_system\\PHPExcel\\CalcEngine\\Logger',
            'PHPExcel_Calculation_Functions' => '\\Drupal\\cloud_system\\PHPExcel\\Calculation\\Functions',
            'PHPExcel_Calculation_Function' => '\\Drupal\\cloud_system\\PHPExcel\\Calculation\\Category',
            'PHPExcel_Calculation_Database' => '\\Drupal\\cloud_system\\PHPExcel\\Calculation\\Database',
            'PHPExcel_Calculation_DateTime' => '\\Drupal\\cloud_system\\PHPExcel\\Calculation\\DateTime',
            'PHPExcel_Calculation_Engineering' => '\\Drupal\\cloud_system\\PHPExcel\\Calculation\\Engineering',
            'PHPExcel_Calculation_Exception' => '\\Drupal\\cloud_system\\PHPExcel\\Calculation\\Exception',
            'PHPExcel_Calculation_ExceptionHandler' => '\\Drupal\\cloud_system\\PHPExcel\\Calculation\\ExceptionHandler',
            'PHPExcel_Calculation_Financial' => '\\Drupal\\cloud_system\\PHPExcel\\Calculation\\Financial',
            'PHPExcel_Calculation_FormulaParser' => '\\Drupal\\cloud_system\\PHPExcel\\Calculation\\FormulaParser',
            'PHPExcel_Calculation_FormulaToken' => '\\Drupal\\cloud_system\\PHPExcel\\Calculation\\FormulaToken',
            'PHPExcel_Calculation_Logical' => '\\Drupal\\cloud_system\\PHPExcel\\Calculation\\Logical',
            'PHPExcel_Calculation_LookupRef' => '\\Drupal\\cloud_system\\PHPExcel\\Calculation\\LookupRef',
            'PHPExcel_Calculation_MathTrig' => '\\Drupal\\cloud_system\\PHPExcel\\Calculation\\MathTrig',
            'PHPExcel_Calculation_Statistical' => '\\Drupal\\cloud_system\\PHPExcel\\Calculation\\Statistical',
            'PHPExcel_Calculation_TextData' => '\\Drupal\\cloud_system\\PHPExcel\\Calculation\\TextData',
            'PHPExcel_Cell_AdvancedValueBinder' => '\\Drupal\\cloud_system\\PHPExcel\\Cell\\AdvancedValueBinder',
            'PHPExcel_Cell_DataType' => '\\Drupal\\cloud_system\\PHPExcel\\Cell\\DataType',
            'PHPExcel_Cell_DataValidation' => '\\Drupal\\cloud_system\\PHPExcel\\Cell\\DataValidation',
            'PHPExcel_Cell_DefaultValueBinder' => '\\Drupal\\cloud_system\\PHPExcel\\Cell\\DefaultValueBinder',
            'PHPExcel_Cell_Hyperlink' => '\\Drupal\\cloud_system\\PHPExcel\\Cell\\Hyperlink',
            'PHPExcel_Cell_IValueBinder' => '\\Drupal\\cloud_system\\PHPExcel\\Cell\\IValueBinder',
            'PHPExcel_Chart_Axis' => '\\Drupal\\cloud_system\\PHPExcel\\Chart\\Axis',
            'PHPExcel_Chart_DataSeries' => '\\Drupal\\cloud_system\\PHPExcel\\Chart\\DataSeries',
            'PHPExcel_Chart_DataSeriesValues' => '\\Drupal\\cloud_system\\PHPExcel\\Chart\\DataSeriesValues',
            'PHPExcel_Chart_Exception' => '\\Drupal\\cloud_system\\PHPExcel\\Chart\\Exception',
            'PHPExcel_Chart_GridLines' => '\\Drupal\\cloud_system\\PHPExcel\\Chart\\GridLines',
            'PHPExcel_Chart_Layout' => '\\Drupal\\cloud_system\\PHPExcel\\Chart\\Layout',
            'PHPExcel_Chart_Legend' => '\\Drupal\\cloud_system\\PHPExcel\\Chart\\Legend',
            'PHPExcel_Chart_PlotArea' => '\\Drupal\\cloud_system\\PHPExcel\\Chart\\PlotArea',
            'PHPExcel_Properties' => '\\Drupal\\cloud_system\\PHPExcel\\Chart\\Properties',
            'PHPExcel_Chart_Title' => '\\Drupal\\cloud_system\\PHPExcel\\Chart\\Title',
            'PHPExcel_DocumentProperties' => '\\Drupal\\cloud_system\\PHPExcel\\Document\\Properties',
            'PHPExcel_DocumentSecurity' => '\\Drupal\\cloud_system\\PHPExcel\\Document\\Security',
            'PHPExcel_Helper_HTML' => '\\Drupal\\cloud_system\\PHPExcel\\Helper\\Html',
            'PHPExcel_Reader_Abstract' => '\\Drupal\\cloud_system\\PHPExcel\\Reader\\BaseReader',
            'PHPExcel_Reader_CSV' => '\\Drupal\\cloud_system\\PHPExcel\\Reader\\Csv',
            'PHPExcel_Reader_DefaultReadFilter' => '\\Drupal\\cloud_system\\PHPExcel\\Reader\\DefaultReadFilter',
            'PHPExcel_Reader_Excel2003XML' => '\\Drupal\\cloud_system\\PHPExcel\\Reader\\Xml',
            'PHPExcel_Reader_Exception' => '\\Drupal\\cloud_system\\PHPExcel\\Reader\\Exception',
            'PHPExcel_Reader_Gnumeric' => '\\Drupal\\cloud_system\\PHPExcel\\Reader\\Gnumeric',
            'PHPExcel_Reader_HTML' => '\\Drupal\\cloud_system\\PHPExcel\\Reader\\Html',
            'PHPExcel_Reader_IReadFilter' => '\\Drupal\\cloud_system\\PHPExcel\\Reader\\IReadFilter',
            'PHPExcel_Reader_IReader' => '\\Drupal\\cloud_system\\PHPExcel\\Reader\\IReader',
            'PHPExcel_Reader_OOCalc' => '\\Drupal\\cloud_system\\PHPExcel\\Reader\\Ods',
            'PHPExcel_Reader_SYLK' => '\\Drupal\\cloud_system\\PHPExcel\\Reader\\Slk',
            'PHPExcel_Reader_Excel5' => '\\Drupal\\cloud_system\\PHPExcel\\Reader\\Xls',
            'PHPExcel_Reader_Excel2007' => '\\Drupal\\cloud_system\\PHPExcel\\Reader\\Xlsx',
            'PHPExcel_RichText_ITextElement' => '\\Drupal\\cloud_system\\PHPExcel\\RichText\\ITextElement',
            'PHPExcel_RichText_Run' => '\\Drupal\\cloud_system\\PHPExcel\\RichText\\Run',
            'PHPExcel_RichText_TextElement' => '\\Drupal\\cloud_system\\PHPExcel\\RichText\\TextElement',
            'PHPExcel_Shared_CodePage' => '\\Drupal\\cloud_system\\PHPExcel\\Shared\\CodePage',
            'PHPExcel_Shared_Date' => '\\Drupal\\cloud_system\\PHPExcel\\Shared\\Date',
            'PHPExcel_Shared_Drawing' => '\\Drupal\\cloud_system\\PHPExcel\\Shared\\Drawing',
            'PHPExcel_Shared_Escher' => '\\Drupal\\cloud_system\\PHPExcel\\Shared\\Escher',
            'PHPExcel_Shared_File' => '\\Drupal\\cloud_system\\PHPExcel\\Shared\\File',
            'PHPExcel_Shared_Font' => '\\Drupal\\cloud_system\\PHPExcel\\Shared\\Font',
            'PHPExcel_Shared_OLE' => '\\Drupal\\cloud_system\\PHPExcel\\Shared\\OLE',
            'PHPExcel_Shared_OLERead' => '\\Drupal\\cloud_system\\PHPExcel\\Shared\\OLERead',
            'PHPExcel_Shared_PasswordHasher' => '\\Drupal\\cloud_system\\PHPExcel\\Shared\\PasswordHasher',
            'PHPExcel_Shared_String' => '\\Drupal\\cloud_system\\PHPExcel\\Shared\\StringHelper',
            'PHPExcel_Shared_TimeZone' => '\\Drupal\\cloud_system\\PHPExcel\\Shared\\TimeZone',
            'PHPExcel_Shared_XMLWriter' => '\\Drupal\\cloud_system\\PHPExcel\\Shared\\XMLWriter',
            'PHPExcel_Shared_Excel5' => '\\Drupal\\cloud_system\\PHPExcel\\Shared\\Xls',
            'PHPExcel_Style_Alignment' => '\\Drupal\\cloud_system\\PHPExcel\\Style\\Alignment',
            'PHPExcel_Style_Border' => '\\Drupal\\cloud_system\\PHPExcel\\Style\\Border',
            'PHPExcel_Style_Borders' => '\\Drupal\\cloud_system\\PHPExcel\\Style\\Borders',
            'PHPExcel_Style_Color' => '\\Drupal\\cloud_system\\PHPExcel\\Style\\Color',
            'PHPExcel_Style_Conditional' => '\\Drupal\\cloud_system\\PHPExcel\\Style\\Conditional',
            'PHPExcel_Style_Fill' => '\\Drupal\\cloud_system\\PHPExcel\\Style\\Fill',
            'PHPExcel_Style_Font' => '\\Drupal\\cloud_system\\PHPExcel\\Style\\Font',
            'PHPExcel_Style_NumberFormat' => '\\Drupal\\cloud_system\\PHPExcel\\Style\\NumberFormat',
            'PHPExcel_Style_Protection' => '\\Drupal\\cloud_system\\PHPExcel\\Style\\Protection',
            'PHPExcel_Style_Supervisor' => '\\Drupal\\cloud_system\\PHPExcel\\Style\\Supervisor',
            'PHPExcel_Worksheet_AutoFilter' => '\\Drupal\\cloud_system\\PHPExcel\\Worksheet\\AutoFilter',
            'PHPExcel_Worksheet_BaseDrawing' => '\\Drupal\\cloud_system\\PHPExcel\\Worksheet\\BaseDrawing',
            'PHPExcel_Worksheet_CellIterator' => '\\Drupal\\cloud_system\\PHPExcel\\Worksheet\\CellIterator',
            'PHPExcel_Worksheet_Column' => '\\Drupal\\cloud_system\\PHPExcel\\Worksheet\\Column',
            'PHPExcel_Worksheet_ColumnCellIterator' => '\\Drupal\\cloud_system\\PHPExcel\\Worksheet\\ColumnCellIterator',
            'PHPExcel_Worksheet_ColumnDimension' => '\\Drupal\\cloud_system\\PHPExcel\\Worksheet\\ColumnDimension',
            'PHPExcel_Worksheet_ColumnIterator' => '\\Drupal\\cloud_system\\PHPExcel\\Worksheet\\ColumnIterator',
            'PHPExcel_Worksheet_Drawing' => '\\Drupal\\cloud_system\\PHPExcel\\Worksheet\\Drawing',
            'PHPExcel_Worksheet_HeaderFooter' => '\\Drupal\\cloud_system\\PHPExcel\\Worksheet\\HeaderFooter',
            'PHPExcel_Worksheet_HeaderFooterDrawing' => '\\Drupal\\cloud_system\\PHPExcel\\Worksheet\\HeaderFooterDrawing',
            'PHPExcel_WorksheetIterator' => '\\Drupal\\cloud_system\\PHPExcel\\Worksheet\\Iterator',
            'PHPExcel_Worksheet_MemoryDrawing' => '\\Drupal\\cloud_system\\PHPExcel\\Worksheet\\MemoryDrawing',
            'PHPExcel_Worksheet_PageMargins' => '\\Drupal\\cloud_system\\PHPExcel\\Worksheet\\PageMargins',
            'PHPExcel_Worksheet_PageSetup' => '\\Drupal\\cloud_system\\PHPExcel\\Worksheet\\PageSetup',
            'PHPExcel_Worksheet_Protection' => '\\Drupal\\cloud_system\\PHPExcel\\Worksheet\\Protection',
            'PHPExcel_Worksheet_Row' => '\\Drupal\\cloud_system\\PHPExcel\\Worksheet\\Row',
            'PHPExcel_Worksheet_RowCellIterator' => '\\Drupal\\cloud_system\\PHPExcel\\Worksheet\\RowCellIterator',
            'PHPExcel_Worksheet_RowDimension' => '\\Drupal\\cloud_system\\PHPExcel\\Worksheet\\RowDimension',
            'PHPExcel_Worksheet_RowIterator' => '\\Drupal\\cloud_system\\PHPExcel\\Worksheet\\RowIterator',
            'PHPExcel_Worksheet_SheetView' => '\\Drupal\\cloud_system\\PHPExcel\\Worksheet\\SheetView',
            'PHPExcel_Writer_Abstract' => '\\Drupal\\cloud_system\\PHPExcel\\Writer\\BaseWriter',
            'PHPExcel_Writer_CSV' => '\\Drupal\\cloud_system\\PHPExcel\\Writer\\Csv',
            'PHPExcel_Writer_Exception' => '\\Drupal\\cloud_system\\PHPExcel\\Writer\\Exception',
            'PHPExcel_Writer_HTML' => '\\Drupal\\cloud_system\\PHPExcel\\Writer\\Html',
            'PHPExcel_Writer_IWriter' => '\\Drupal\\cloud_system\\PHPExcel\\Writer\\IWriter',
            'PHPExcel_Writer_OpenDocument' => '\\Drupal\\cloud_system\\PHPExcel\\Writer\\Ods',
            'PHPExcel_Writer_PDF' => '\\Drupal\\cloud_system\\PHPExcel\\Writer\\Pdf',
            'PHPExcel_Writer_Excel5' => '\\Drupal\\cloud_system\\PHPExcel\\Writer\\Xls',
            'PHPExcel_Writer_Excel2007' => '\\Drupal\\cloud_system\\PHPExcel\\Writer\\Xlsx',
            'PHPExcel_CachedObjectStorageFactory' => '\\Drupal\\cloud_system\\PHPExcel\\Collection\\CellsFactory',
            'PHPExcel_Calculation' => '\\Drupal\\cloud_system\\PHPExcel\\Calculation',
            'PHPExcel_Cell' => '\\Drupal\\cloud_system\\PHPExcel\\Cell',
            'PHPExcel_Chart' => '\\Drupal\\cloud_system\\PHPExcel\\Chart',
            'PHPExcel_Comment' => '\\Drupal\\cloud_system\\PHPExcel\\Comment',
            'PHPExcel_Exception' => '\\Drupal\\cloud_system\\PHPExcel\\Exception',
            'PHPExcel_HashTable' => '\\Drupal\\cloud_system\\PHPExcel\\HashTable',
            'PHPExcel_IComparable' => '\\Drupal\\cloud_system\\PHPExcel\\IComparable',
            'PHPExcel_IOFactory' => '\\Drupal\\cloud_system\\PHPExcel\\IOFactory',
            'PHPExcel_NamedRange' => '\\Drupal\\cloud_system\\PHPExcel\\NamedRange',
            'PHPExcel_ReferenceHelper' => '\\Drupal\\cloud_system\\PHPExcel\\ReferenceHelper',
            'PHPExcel_RichText' => '\\Drupal\\cloud_system\\PHPExcel\\RichText',
            'PHPExcel_Settings' => '\\Drupal\\cloud_system\\PHPExcel\\Settings',
            'PHPExcel_Style' => '\\Drupal\\cloud_system\\PHPExcel\\Style',
            'PHPExcel_Worksheet' => '\\Drupal\\cloud_system\\PHPExcel\\Worksheet',
            'PHPExcel' => '\\Drupal\\cloud_system\\PHPExcel\\Spreadsheet',
            // methods
            'MINUTEOFHOUR' => 'MINUTE',
            'SECONDOFMINUTE' => 'SECOND',
            'DAYOFWEEK' => 'WEEKDAY',
            'WEEKOFYEAR' => 'WEEKNUM',
        ];

        return $mapping;
    }

    /**
     * Search in all files in given directory.
     *
     * @param string $path
     */
    private function recursiveReplace($path)
    {
        $patterns = [
            '/*.md',
            '/*.php',
            '/*.txt',
            '/*.TXT',
        ];

        $from = array_keys($this->getMapping());
        $to = array_values($this->getMapping());

        foreach ($patterns as $pattern) {
            foreach (glob($path . $pattern) as $file) {
                $original = file_get_contents($file);
                $converted = str_replace($from, $to, $original);

                if ($original !== $converted) {
                    echo $file . " converted\n";
                    file_put_contents($file, $converted);
                }
            }
        }

        // Do the recursion in subdirectory
        foreach (glob($path . '/*', GLOB_ONLYDIR) as $subpath) {
            if (strpos($subpath, $path . '/') === 0) {
                $this->recursiveReplace($subpath);
            }
        }
    }

    public function migrate()
    {
        $path = realpath(getcwd());
        echo 'This will search and replace recursively in ' . $path . PHP_EOL;
        echo 'You MUST backup your files first, or you risk losing data.' . PHP_EOL;
        echo 'Are you sure ? (y/n)';

        $confirm = fread(STDIN, 1);
        if ($confirm === 'y') {
            $this->recursiveReplace($path);
        }
    }
}
