<?php
/**
 * PortfolioDAO: Handles portfolio data for any type (micro, blue-chip, small-cap, etc.) with DB-first read, CSV fallback, and dual-write.
 * On write: writes CSV first, then DB. On read: tries DB, falls back to CSV. Logs errors and stores failed data in session for retry.
 */

require_once __DIR__ . '/CommonDAO.php';
class PortfolioDAO extends CommonDAO {
    private $csvPath;
    private $sessionKey;
    private $tableName;

    public function __construct($csvPath, $tableName, $dbConfigClass) {
        parent::__construct($dbConfigClass);
        $this->csvPath = $csvPath;
        $this->tableName = $tableName;
        $this->sessionKey = 'portfolio_retry_' . $tableName;
        if (session_status() === PHP_SESSION_NONE) session_start();
    }

    public function readPortfolio() {
        // Try DB first
        if ($this->pdo) {
            try {
                $stmt = $this->pdo->query("SELECT * FROM {$this->tableName} WHERE date = (SELECT MAX(date) FROM {$this->tableName})");
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if ($rows) return $rows;
            } catch (Exception $e) {
                $this->logError('DB read failed: ' . $e->getMessage());
            }
        }
        // Fallback to CSV
        return $this->readPortfolioCsv();
    }

    public function writePortfolio($rows) {
        $csvOk = $this->writePortfolioCsv($rows);
        $dbOk = $this->writePortfolioDb($rows);
        if (!$csvOk || !$dbOk) {
            $_SESSION[$this->sessionKey] = $rows;
        } else {
            unset($_SESSION[$this->sessionKey]);
        }
        return $csvOk && $dbOk;
    }


    private function readPortfolioCsv() {
        $data = $this->readCsv($this->csvPath);
        if (empty($data)) return [];
        // Get latest date
        $dates = array_column($data, 'Date');
        $latest = max($dates);
        return array_values(array_filter($data, function($r) use ($latest) { return $r['Date'] === $latest; }));
    }

    private function writePortfolioCsv($rows) {
        return $this->writeCsv($this->csvPath, $rows);
    }

    private function writePortfolioDb($rows) {
        if (!$this->pdo) return false;
        try {
            foreach ($rows as $row) {
                $stmt = $this->pdo->prepare("REPLACE INTO {$this->tableName} (symbol, date, position_size, avg_cost, current_price, market_value, unrealized_pnl) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $row['Ticker'] ?? $row['symbol'] ?? '',
                    $row['Date'],
                    $row['Shares'] ?? $row['position_size'] ?? 0,
                    $row['Buy Price'] ?? $row['avg_cost'] ?? 0,
                    $row['Current Price'] ?? $row['current_price'] ?? 0,
                    $row['Total Value'] ?? $row['market_value'] ?? 0,
                    $row['PnL'] ?? $row['unrealized_pnl'] ?? 0
                ]);
            }
            return true;
        } catch (Exception $e) {
            $this->logError('DB write failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getErrors() {
        return $this->errors;
    }
    protected function logError($msg) {
        $this->errors[] = $msg;
    }
    public function getRetryData() {
        return $_SESSION[$this->sessionKey] ?? null;
    }
    public function clearRetryData() {
        unset($_SESSION[$this->sessionKey]);
    }
}
