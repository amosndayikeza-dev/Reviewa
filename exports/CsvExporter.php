<?php
/**
 * Simple CSV exporter utility.
 * Accepts an array of associative arrays and returns CSV string.
 */
class CsvExporter {
    /**
     * Convert rows (array of assoc arrays) to CSV string.
     * @param array $rows
     * @param array|null $headers Optional explicit headers order
     * @param string $delimiter
     * @return string
     */
    public static function toCsv(array $rows, array $headers = null, string $delimiter = ',') : string {
        if (empty($rows)) {
            // return UTF-8 BOM only so Excel recognizes encoding even for empty files
            return "\xEF\xBB\xBF";
        }

        // Determine headers from first row if not provided
        if ($headers === null) {
            $headers = array_keys(reset($rows));
        }

        $out = fopen('php://memory', 'r+');
        // write header
        fputcsv($out, $headers, $delimiter);

        foreach ($rows as $row) {
            $line = [];
            foreach ($headers as $h) {
                $line[] = isset($row[$h]) ? $row[$h] : '';
            }
            fputcsv($out, $line, $delimiter);
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);
        // prepend UTF-8 BOM to help Excel detect UTF-8 encoding
        return "\xEF\xBB\xBF" . $csv;
    }
}
