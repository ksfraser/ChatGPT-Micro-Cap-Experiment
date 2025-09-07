<?php

require_once __DIR__ . '/CoreInterfaces.php';

/**
 * Robust CSV parser with error handling and validation
 */
class CsvParser implements CsvParserInterface
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function parse($filePath)
    {
        if (!file_exists($filePath)) {
            $this->logger->error('CSV file not found', ['file' => $filePath]);
            return [];
        }

        if (!is_readable($filePath)) {
            $this->logger->error('CSV file not readable', ['file' => $filePath]);
            return [];
        }

        $rows = [];
        $handle = fopen($filePath, 'r');
        
        if (!$handle) {
            $this->logger->error('Failed to open CSV file', ['file' => $filePath]);
            return [];
        }

        try {
            $header = fgetcsv($handle);
            if (!$header || empty($header)) {
                $this->logger->warning('CSV file has no header or empty header', ['file' => $filePath]);
                fclose($handle);
                return [];
            }

            $lineNumber = 1;
            while (($data = fgetcsv($handle)) !== false) {
                $lineNumber++;
                
                if (count($data) !== count($header)) {
                    $this->logger->warning('CSV line has different column count than header', [
                        'file' => $filePath,
                        'line' => $lineNumber,
                        'expected' => count($header),
                        'actual' => count($data)
                    ]);
                    continue;
                }

                $rows[] = array_combine($header, $data);
            }

            $this->logger->info('CSV parsed successfully', [
                'file' => $filePath,
                'rows' => count($rows)
            ]);

        } catch (Exception $e) {
            $this->logger->error('Error parsing CSV', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            $rows = [];
        } finally {
            fclose($handle);
        }

        return $rows;
    }

    public function write($filePath, array $data)
    {
        if (empty($data)) {
            $this->logger->warning('Attempted to write empty data to CSV', ['file' => $filePath]);
            return false;
        }

        // Ensure directory exists
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $handle = fopen($filePath, 'w');
        if (!$handle) {
            $this->logger->error('Failed to open CSV file for writing', ['file' => $filePath]);
            return false;
        }

        try {
            // Write header
            $header = array_keys($data[0]);
            if (!fputcsv($handle, $header)) {
                throw new Exception('Failed to write CSV header');
            }

            // Write data rows
            foreach ($data as $row) {
                if (!fputcsv($handle, $row)) {
                    throw new Exception('Failed to write CSV row');
                }
            }

            $this->logger->info('CSV written successfully', [
                'file' => $filePath,
                'rows' => count($data)
            ]);

            return true;

        } catch (Exception $e) {
            $this->logger->error('Error writing CSV', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            return false;
        } finally {
            fclose($handle);
        }
    }
}
