<?php
/**
 * Database Repository Implementation
 * 
 * Handles data persistence for financial information using PDO.
 * Implements the DataRepositoryInterface following the Dependency Inversion Principle.
 */

namespace Ksfraser\Finance\Repositories;

use Ksfraser\Finance\Interfaces\DataRepositoryInterface;
use PDO;
use DateTime;

class DatabaseRepository implements DataRepositoryInterface
{
    private $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function saveStockPrice(array $data): bool
    {
        try {
            $sql = "INSERT INTO stock_prices (symbol, price, change_amount, change_percent, volume, 
                                            open_price, high_price, low_price, previous_close, 
                                            timestamp, source, trading_day) 
                    VALUES (:symbol, :price, :change, :change_percent, :volume, 
                           :open_price, :high_price, :low_price, :previous_close,
                           :timestamp, :source, :trading_day)
                    ON DUPLICATE KEY UPDATE 
                    price = VALUES(price), 
                    change_amount = VALUES(change_amount),
                    change_percent = VALUES(change_percent),
                    volume = VALUES(volume),
                    open_price = VALUES(open_price),
                    high_price = VALUES(high_price),
                    low_price = VALUES(low_price),
                    previous_close = VALUES(previous_close),
                    timestamp = VALUES(timestamp),
                    source = VALUES(source),
                    trading_day = VALUES(trading_day)";

            $stmt = $this->connection->prepare($sql);
            return $stmt->execute([
                'symbol' => $data['symbol'],
                'price' => $data['price'],
                'change' => $data['change'],
                'change_percent' => $data['change_percent'],
                'volume' => $data['volume'],
                'open_price' => $data['open'] ?? null,
                'high_price' => $data['high'] ?? null,
                'low_price' => $data['low'] ?? null,
                'previous_close' => $data['previous_close'] ?? null,
                'timestamp' => $data['timestamp']->format('Y-m-d H:i:s'),
                'source' => $data['source'],
                'trading_day' => $data['latest_trading_day'] ?? date('Y-m-d')
            ]);
        } catch (\PDOException $e) {
            error_log("Database error saving stock price: " . $e->getMessage());
            return false;
        }
    }

    public function saveFinancialStatement(array $data): bool
    {
        try {
            $sql = "INSERT INTO financial_statements (symbol, statement_type, period, data, created_at) 
                    VALUES (:symbol, :statement_type, :period, :data, NOW())
                    ON DUPLICATE KEY UPDATE 
                    data = VALUES(data), 
                    updated_at = NOW()";

            $stmt = $this->connection->prepare($sql);
            return $stmt->execute([
                'symbol' => $data['symbol'],
                'statement_type' => $data['type'],
                'period' => $data['period'],
                'data' => json_encode($data['data'])
            ]);
        } catch (\PDOException $e) {
            error_log("Database error saving financial statement: " . $e->getMessage());
            return false;
        }
    }

    public function getStockPrice(string $symbol, ?DateTime $date = null): ?array
    {
        try {
            if ($date) {
                $sql = "SELECT * FROM stock_prices WHERE symbol = :symbol AND DATE(timestamp) = :date ORDER BY timestamp DESC LIMIT 1";
                $params = ['symbol' => $symbol, 'date' => $date->format('Y-m-d')];
            } else {
                $sql = "SELECT * FROM stock_prices WHERE symbol = :symbol ORDER BY timestamp DESC LIMIT 1";
                $params = ['symbol' => $symbol];
            }

            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\PDOException $e) {
            error_log("Database error getting stock price: " . $e->getMessage());
            return null;
        }
    }

    public function getCompany(string $symbol): ?array
    {
        try {
            $sql = "SELECT * FROM companies WHERE symbol = :symbol";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute(['symbol' => $symbol]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\PDOException $e) {
            error_log("Database error getting company: " . $e->getMessage());
            return null;
        }
    }

    public function getHistoricalPrices(string $symbol, DateTime $startDate, DateTime $endDate): array
    {
        try {
            $sql = "SELECT * FROM stock_prices 
                    WHERE symbol = :symbol 
                    AND DATE(timestamp) BETWEEN :start_date AND :end_date 
                    ORDER BY timestamp ASC";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([
                'symbol' => $symbol,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d')
            ]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Database error getting historical prices: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all unique symbols in the database
     */
    public function getAllSymbols(): array
    {
        try {
            $sql = "SELECT DISTINCT symbol FROM stock_prices ORDER BY symbol";
            $stmt = $this->connection->query($sql);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (\PDOException $e) {
            error_log("Database error getting all symbols: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get latest prices for all symbols
     */
    public function getLatestPrices(): array
    {
        try {
            $sql = "SELECT sp1.* FROM stock_prices sp1
                    INNER JOIN (
                        SELECT symbol, MAX(timestamp) as max_timestamp
                        FROM stock_prices
                        GROUP BY symbol
                    ) sp2 ON sp1.symbol = sp2.symbol AND sp1.timestamp = sp2.max_timestamp
                    ORDER BY sp1.symbol";
            
            $stmt = $this->connection->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Database error getting latest prices: " . $e->getMessage());
            return [];
        }
    }
}
