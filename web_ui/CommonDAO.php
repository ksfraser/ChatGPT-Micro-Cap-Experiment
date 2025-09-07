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
            $this->pdo = $this->dbConfigClass::createConnection();
        } catch (Exception $e) {
            $this->pdo = null;
            $this->logError('DB connection failed: ' . $e->getMessage());
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
