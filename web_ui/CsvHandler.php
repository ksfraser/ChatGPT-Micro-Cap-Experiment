<?php
/**
 * CsvHandler: Centralized CSV operations for the entire application
 * Handles all CSV reading and writing with error handling and validation
 */
class CsvHandler {
    private $errors = [];
    
    /**
     * Read CSV file and return associative array
     */
    public function read($csvPath) {
        $this->errors = [];
        
        if (!file_exists($csvPath)) {
            $this->logError("CSV file not found: $csvPath");
            return [];
        }
        
        if (!is_readable($csvPath)) {
            $this->logError("CSV file not readable: $csvPath");
            return [];
        }
        
        try {
            $handle = fopen($csvPath, 'r');
            if (!$handle) {
                $this->logError("Cannot open CSV file: $csvPath");
                return [];
            }
            
            $header = fgetcsv($handle);
            if (!$header) {
                $this->logError("CSV file has no header: $csvPath");
                fclose($handle);
                return [];
            }
            
            $data = [];
            $lineNumber = 2; // Start from line 2 (after header)
            
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) !== count($header)) {
                    $this->logError("Line $lineNumber has " . count($row) . " columns, expected " . count($header) . " in $csvPath");
                    $lineNumber++;
                    continue;
                }
                
                $data[] = array_combine($header, $row);
                $lineNumber++;
            }
            
            fclose($handle);
            return $data;
            
        } catch (Exception $e) {
            $this->logError('CSV read failed: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Write data to CSV file
     */
    public function write($csvPath, $data) {
        $this->errors = [];
        
        if (empty($data)) {
            $this->logError("No data provided for CSV write");
            return false;
        }
        
        try {
            // Ensure directory exists
            $dir = dirname($csvPath);
            if (!is_dir($dir)) {
                if (!mkdir($dir, 0755, true)) {
                    $this->logError("Cannot create directory: $dir");
                    return false;
                }
            }
            
            $handle = fopen($csvPath, 'w');
            if (!$handle) {
                $this->logError("Cannot open CSV file for writing: $csvPath");
                return false;
            }
            
            // Write header
            $header = array_keys($data[0]);
            if (!fputcsv($handle, $header)) {
                $this->logError("Failed to write CSV header to: $csvPath");
                fclose($handle);
                return false;
            }
            
            // Write data rows
            foreach ($data as $row) {
                if (!fputcsv($handle, $row)) {
                    $this->logError("Failed to write CSV row to: $csvPath");
                    fclose($handle);
                    return false;
                }
            }
            
            fclose($handle);
            return true;
            
        } catch (Exception $e) {
            $this->logError('CSV write failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Append data to existing CSV file
     */
    public function append($csvPath, $data) {
        $this->errors = [];
        
        if (empty($data)) {
            $this->logError("No data provided for CSV append");
            return false;
        }
        
        try {
            // If file doesn't exist, create it with header
            if (!file_exists($csvPath)) {
                return $this->write($csvPath, $data);
            }
            
            $handle = fopen($csvPath, 'a');
            if (!$handle) {
                $this->logError("Cannot open CSV file for appending: $csvPath");
                return false;
            }
            
            // Write data rows (no header needed for append)
            foreach ($data as $row) {
                if (!fputcsv($handle, $row)) {
                    $this->logError("Failed to append CSV row to: $csvPath");
                    fclose($handle);
                    return false;
                }
            }
            
            fclose($handle);
            return true;
            
        } catch (Exception $e) {
            $this->logError('CSV append failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Validate CSV file structure
     */
    public function validate($csvPath, $expectedColumns = null) {
        $this->errors = [];
        
        if (!file_exists($csvPath)) {
            $this->logError("CSV file not found: $csvPath");
            return false;
        }
        
        try {
            $handle = fopen($csvPath, 'r');
            if (!$handle) {
                $this->logError("Cannot open CSV file: $csvPath");
                return false;
            }
            
            $header = fgetcsv($handle);
            if (!$header) {
                $this->logError("CSV file has no header: $csvPath");
                fclose($handle);
                return false;
            }
            
            // Check expected columns if provided
            if ($expectedColumns !== null) {
                $missing = array_diff($expectedColumns, $header);
                if (!empty($missing)) {
                    $this->logError("CSV missing expected columns: " . implode(', ', $missing));
                    fclose($handle);
                    return false;
                }
            }
            
            fclose($handle);
            return true;
            
        } catch (Exception $e) {
            $this->logError('CSV validation failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get errors from last operation
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Check if last operation had errors
     */
    public function hasErrors() {
        return !empty($this->errors);
    }
    
    /**
     * Clear errors
     */
    public function clearErrors() {
        $this->errors = [];
    }
    
    private function logError($message) {
        $this->errors[] = $message;
    }
}
