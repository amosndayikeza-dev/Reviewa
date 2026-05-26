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
            return '';
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
        return $csv;
    }
}
