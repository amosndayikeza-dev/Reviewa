<?php
/**
 * Simple Excel 2003 XML exporter.
 * Produces a minimal SpreadsheetML document that Excel can open.
 */
class ExcelExporter {
    /**
     * Convert rows (array of assoc arrays) to Excel 2003 XML string.
     * @param array $rows
     * @param array|null $headers
     * @return string
     */
    public static function toExcelXml(array $rows, array $headers = null) : string {
        if (empty($rows)) {
            // create empty workbook
            $rows = [];
        }

        if ($headers === null && !empty($rows)) {
            $headers = array_keys(reset($rows));
        }

        $xml = [];
        $xml[] = '<?xml version="1.0"?>';
        $xml[] = '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"';
        $xml[] = ' xmlns:o="urn:schemas-microsoft-com:office:office"';
        $xml[] = ' xmlns:x="urn:schemas-microsoft-com:office:excel"';
        $xml[] = ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
        $xml[] = '<Worksheet ss:Name="Sheet1">';
        $xml[] = '<Table>';

        if (!empty($headers)) {
            $xml[] = '<Row>';
            foreach ($headers as $h) {
                $xml[] = '<Cell><Data ss:Type="String">' . self::escape($h) . '</Data></Cell>';
            }
            $xml[] = '</Row>';
        }

        foreach ($rows as $row) {
            $xml[] = '<Row>';
            if ($headers !== null) {
                foreach ($headers as $h) {
                    $val = isset($row[$h]) ? $row[$h] : '';
                    $type = is_numeric($val) ? 'Number' : 'String';
                    $xml[] = '<Cell><Data ss:Type="' . $type . '">' . self::escape($val) . '</Data></Cell>';
                }
            } else {
                foreach ($row as $val) {
                    $type = is_numeric($val) ? 'Number' : 'String';
                    $xml[] = '<Cell><Data ss:Type="' . $type . '">' . self::escape($val) . '</Data></Cell>';
                }
            }
            $xml[] = '</Row>';
        }

        $xml[] = '</Table>';
        $xml[] = '</Worksheet>';
        $xml[] = '</Workbook>';

        return implode("\n", $xml);
    }

    protected static function escape($v) {
        return htmlspecialchars((string)$v, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
