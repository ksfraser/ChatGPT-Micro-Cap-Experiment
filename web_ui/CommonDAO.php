<?php
/**
 * CommonDAO: Base class for all DAOs, handles DB connection, error logging, and config.
 */
abstract class CommonDAO {
    protected $pdo;
    protected $errors = [];
    protected $dbConfigClass;

    public function __construct($dbConfigClass) {
        $this->dbConfigClass = $dbConfigClass;
        $this->connectDb();
    }

    protected function connectDb() {
        try {
            require_once __DIR__ . '/DbConfigClasses.php';
            
            // Set a connection timeout to prevent hanging
            $originalTimeout = ini_get('default_socket_timeout');
            ini_set('default_socket_timeout', 5); // 5 second timeout
            
            $this->pdo = $this->dbConfigClass::createConnection();
            
            // Restore original timeout
            ini_set('default_socket_timeout', $originalTimeout);
            
        } catch (Exception $e) {
            $this->pdo = null;
            $this->logError('DB connection failed: ' . $e->getMessage());
            
            // Restore original timeout in case of exception
            if (isset($originalTimeout)) {
                ini_set('default_socket_timeout', $originalTimeout);
            }
        }
    }

    // Generic CSV read
    protected function readCsv($csvPath) {
        if (!file_exists($csvPath)) return [];
        $rows = array_map('str_getcsv', file($csvPath));
        if (count($rows) < 2) return [];
        $header = $rows[0];
        $data = [];
        for ($i = 1; $i < count($rows); $i++) {
            $data[] = array_combine($header, $rows[$i]);
        }
        return $data;
    }

    // Generic CSV write
    protected function writeCsv($csvPath, $rows) {
        try {
            if (empty($rows)) return false;
            $header = array_keys($rows[0]);
            $fp = fopen($csvPath, 'w');
            fputcsv($fp, $header);
            foreach ($rows as $row) fputcsv($fp, $row);
            fclose($fp);
            return true;
        } catch (Exception $e) {
            $this->logError('CSV write failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getErrors() {
        return $this->errors;
    }

    // Public accessor for PDO connection
    public function getPdo() {
        return $this->pdo;
    }

    protected function logError($msg) {
        $this->errors[] = $msg;
    }
}
