<?php

require_once __DIR__ . '/CoreInterfaces.php';
require_once __DIR__ . '/DatabaseConnection.php';

/**
 * Base DAO class with dependency injection and improved error handling
 */
abstract class BaseDAO
{
    protected $db;
    protected $logger;
    protected $validator;

    public function __construct(
        DatabaseConnectionInterface $db,
        LoggerInterface $logger,
        ValidatorInterface $validator = null
    ) {
        $this->db = $db;
        $this->logger = $logger;
        $this->validator = $validator;
    }

    /**
     * Execute a query with proper error handling and logging
     */
    protected function executeQuery($sql, array $params = [])
    {
        try {
            $stmt = $this->db->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logger->error('Query execution failed', [
                'sql' => $sql,
                'params' => $params,
                'error' => $e->getMessage(),
                'class' => get_class($this)
            ]);
            throw new RuntimeException('Database operation failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Validate data before database operations
     */
    protected function validateData($data)
    {
        if ($this->validator === null) {
            return new ValidationResult(true, [], []);
        }

        return $this->validator->validate($data);
    }

    /**
     * Begin a database transaction
     */
    protected function beginTransaction()
    {
        return $this->db->beginTransaction();
    }

    /**
     * Commit a database transaction
     */
    protected function commit()
    {
        return $this->db->commit();
    }

    /**
     * Rollback a database transaction
     */
    protected function rollback()
    {
        return $this->db->rollback();
    }

    /**
     * Get the last inserted ID
     */
    protected function getLastInsertId()
    {
        return $this->db->lastInsertId();
    }

    /**
     * Execute multiple queries in a transaction
     */
    protected function executeTransaction(callable $operations)
    {
        $this->beginTransaction();
        
        try {
            $result = $operations();
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollback();
            $this->logger->error('Transaction failed and was rolled back', [
                'error' => $e->getMessage(),
                'class' => get_class($this)
            ]);
            throw $e;
        }
    }

    /**
     * Sanitize column names to prevent SQL injection
     */
    protected function sanitizeColumnName($columnName)
    {
        // Only allow alphanumeric characters and underscores
        return preg_replace('/[^a-zA-Z0-9_]/', '', $columnName);
    }

    /**
     * Build WHERE clause from conditions array
     */
    protected function buildWhereClause(array $conditions)
    {
        if (empty($conditions)) {
            return ['', []];
        }

        $whereParts = [];
        $params = [];

        foreach ($conditions as $column => $value) {
            $sanitizedColumn = $this->sanitizeColumnName($column);
            if ($value === null) {
                $whereParts[] = "$sanitizedColumn IS NULL";
            } else {
                $whereParts[] = "$sanitizedColumn = :$sanitizedColumn";
                $params[$sanitizedColumn] = $value;
            }
        }

        $whereClause = 'WHERE ' . implode(' AND ', $whereParts);
        return [$whereClause, $params];
    }
}

/**
 * Improved Transaction DAO with dependency injection
 */
class TransactionDAO extends BaseDAO
{
    public function __construct(
        DatabaseConnectionInterface $db,
        LoggerInterface $logger,
        ValidatorInterface $validator = null
    ) {
        parent::__construct($db, $logger, $validator);
    }

    public function insertTransaction(array $transactionData)
    {
        // Validate transaction data
        $validation = $this->validateData($transactionData);
        if (!$validation->isValid()) {
            throw new InvalidArgumentException('Invalid transaction data: ' . implode(', ', $validation->getErrors()));
        }

        $sql = "INSERT INTO transactions (symbol, shares, price, txn_date, txn_type) 
                VALUES (:symbol, :shares, :price, :txn_date, :txn_type)";

        $params = [
            'symbol' => $transactionData['symbol'],
            'shares' => $transactionData['shares'],
            'price' => $transactionData['price'],
            'txn_date' => $transactionData['txn_date'],
            'txn_type' => $transactionData['txn_type'] ?? 'BUY'
        ];

        $this->executeQuery($sql, $params);
        $insertId = $this->getLastInsertId();

        $this->logger->info('Transaction inserted', [
            'id' => $insertId,
            'symbol' => $transactionData['symbol'],
            'shares' => $transactionData['shares']
        ]);

        return $insertId;
    }

    public function getTransactionsBySymbol($symbol)
    {
        $sql = "SELECT * FROM transactions WHERE symbol = :symbol ORDER BY txn_date DESC";
        $stmt = $this->executeQuery($sql, ['symbol' => $symbol]);
        return $stmt->fetchAll();
    }

    public function getAllTransactions()
    {
        $sql = "SELECT * FROM transactions ORDER BY txn_date DESC";
        $stmt = $this->executeQuery($sql);
        return $stmt->fetchAll();
    }

    public function getTransactionById($id)
    {
        $sql = "SELECT * FROM transactions WHERE id = :id";
        $stmt = $this->executeQuery($sql, ['id' => $id]);
        return $stmt->fetch();
    }

    public function updateTransaction($id, array $transactionData)
    {
        // Validate transaction data
        $validation = $this->validateData($transactionData);
        if (!$validation->isValid()) {
            throw new InvalidArgumentException('Invalid transaction data: ' . implode(', ', $validation->getErrors()));
        }

        $setParts = [];
        $params = ['id' => $id];

        foreach ($transactionData as $column => $value) {
            $sanitizedColumn = $this->sanitizeColumnName($column);
            $setParts[] = "$sanitizedColumn = :$sanitizedColumn";
            $params[$sanitizedColumn] = $value;
        }

        $sql = "UPDATE transactions SET " . implode(', ', $setParts) . " WHERE id = :id";
        
        $stmt = $this->executeQuery($sql, $params);
        $affectedRows = $stmt->rowCount();

        $this->logger->info('Transaction updated', [
            'id' => $id,
            'affected_rows' => $affectedRows
        ]);

        return $affectedRows > 0;
    }

    public function deleteTransaction($id)
    {
        $sql = "DELETE FROM transactions WHERE id = :id";
        $stmt = $this->executeQuery($sql, ['id' => $id]);
        $affectedRows = $stmt->rowCount();

        $this->logger->info('Transaction deleted', [
            'id' => $id,
            'affected_rows' => $affectedRows
        ]);

        return $affectedRows > 0;
    }

    public function getPortfolioSummary()
    {
        $sql = "SELECT 
                    symbol,
                    SUM(CASE WHEN txn_type = 'BUY' THEN shares ELSE -shares END) as total_shares,
                    AVG(CASE WHEN txn_type = 'BUY' THEN price END) as avg_buy_price,
                    COUNT(*) as transaction_count
                FROM transactions 
                GROUP BY symbol
                HAVING total_shares > 0
                ORDER BY symbol";
        
        $stmt = $this->executeQuery($sql);
        return $stmt->fetchAll();
    }
}

/**
 * Improved Portfolio DAO with dependency injection
 */
class ImprovedPortfolioDAO extends BaseDAO
{
    public function getCurrentPortfolio()
    {
        $sql = "SELECT p.*, 
                       (p.current_shares * p.current_price) as market_value,
                       ((p.current_price - p.avg_cost) * p.current_shares) as unrealized_gain_loss
                FROM portfolio p 
                WHERE p.current_shares > 0
                ORDER BY p.symbol";
        
        $stmt = $this->executeQuery($sql);
        return $stmt->fetchAll();
    }

    public function updatePortfolioPosition($symbol, $shares, $avgCost, $currentPrice)
    {
        $sql = "INSERT INTO portfolio (symbol, current_shares, avg_cost, current_price, last_updated)
                VALUES (:symbol, :shares, :avg_cost, :current_price, NOW())
                ON DUPLICATE KEY UPDATE
                current_shares = :shares,
                avg_cost = :avg_cost,
                current_price = :current_price,
                last_updated = NOW()";

        $params = [
            'symbol' => $symbol,
            'shares' => $shares,
            'avg_cost' => $avgCost,
            'current_price' => $currentPrice
        ];

        $this->executeQuery($sql, $params);

        $this->logger->info('Portfolio position updated', [
            'symbol' => $symbol,
            'shares' => $shares,
            'avg_cost' => $avgCost
        ]);
    }

    public function getPortfolioBySymbol($symbol)
    {
        $sql = "SELECT * FROM portfolio WHERE symbol = :symbol";
        $stmt = $this->executeQuery($sql, ['symbol' => $symbol]);
        return $stmt->fetch();
    }
}
