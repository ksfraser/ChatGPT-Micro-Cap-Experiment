<?php

require_once __DIR__ . '/CoreInterfaces.php';
require_once __DIR__ . '/CsvParser.php';
require_once __DIR__ . '/Validators.php';
require_once __DIR__ . '/ImprovedDAO.php';
require_once __DIR__ . '/SecureFileUploadHandler.php';

/**
 * Improved import service using dependency injection and SOLID principles
 */
class ImportService
{
    private $csvParser;
    private $fileUploadHandler;
    private $transactionDAO;
    private $portfolioDAO;
    private $csvValidator;
    private $transactionValidator;
    private $logger;

    public function __construct(
        CsvParser $csvParser,
        SecureFileUploadHandler $fileUploadHandler,
        TransactionDAO $transactionDAO,
        ImprovedPortfolioDAO $portfolioDAO,
        CsvFileValidator $csvValidator,
        TransactionDataValidator $transactionValidator,
        LoggerInterface $logger
    ) {
        $this->csvParser = $csvParser;
        $this->fileUploadHandler = $fileUploadHandler;
        $this->transactionDAO = $transactionDAO;
        $this->portfolioDAO = $portfolioDAO;
        $this->csvValidator = $csvValidator;
        $this->transactionValidator = $transactionValidator;
        $this->logger = $logger;
    }

    /**
     * Import transactions from uploaded CSV file
     */
    public function importTransactionsFromUpload($fileInputName)
    {
        $this->logger->info('Starting transaction import from file upload');

        try {
            // Handle file upload securely
            $uploadResult = $this->fileUploadHandler->handleUpload($fileInputName);
            
            if (!$uploadResult->isSuccess()) {
                return new ImportResult(false, 0, $uploadResult->getErrors());
            }

            $filePath = $uploadResult->getFilePath();
            return $this->importTransactionsFromFile($filePath);

        } catch (Exception $e) {
            $this->logger->error('Import from upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return new ImportResult(false, 0, ['Import failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Import transactions from existing CSV file
     */
    public function importTransactionsFromFile($filePath)
    {
        $this->logger->info('Starting transaction import from file', ['file' => $filePath]);

        try {
            // Parse CSV file
            $csvData = $this->csvParser->parseFile($filePath);
            
            if (empty($csvData)) {
                return new ImportResult(false, 0, ['No data found in CSV file']);
            }

            // Validate CSV structure
            $csvValidation = $this->csvValidator->validate($csvData);
            if (!$csvValidation->isValid()) {
                return new ImportResult(false, 0, $csvValidation->getErrors());
            }

            return $this->processTransactionData($csvData);

        } catch (Exception $e) {
            $this->logger->error('Import from file failed', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            return new ImportResult(false, 0, ['Import failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Process CSV data and import transactions
     */
    private function processTransactionData(array $csvData)
    {
        $imported = 0;
        $failed = 0;
        $errors = [];
        $warnings = [];

        $this->logger->info('Processing transaction data', ['total_rows' => count($csvData)]);

        // Process each transaction in a database transaction
        $this->transactionDAO->beginTransaction();

        try {
            foreach ($csvData as $index => $row) {
                $rowNumber = $index + 1;

                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                // Normalize transaction data
                $transactionData = $this->normalizeTransactionData($row);

                // Validate individual transaction
                $validation = $this->transactionValidator->validate($transactionData);
                
                if (!$validation->isValid()) {
                    $failed++;
                    $errors[] = "Row $rowNumber: " . implode(', ', $validation->getErrors());
                    continue;
                }

                // Add any warnings
                if ($validation->hasWarnings()) {
                    foreach ($validation->getWarnings() as $warning) {
                        $warnings[] = "Row $rowNumber: $warning";
                    }
                }

                try {
                    // Insert transaction
                    $transactionId = $this->transactionDAO->insertTransaction($transactionData);
                    
                    // Update portfolio position
                    $this->updatePortfolioPosition($transactionData);
                    
                    $imported++;
                    
                } catch (Exception $e) {
                    $failed++;
                    $errors[] = "Row $rowNumber: Failed to import transaction - " . $e->getMessage();
                    
                    $this->logger->warning('Transaction import failed for row', [
                        'row' => $rowNumber,
                        'data' => $transactionData,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Commit transaction if we imported anything
            if ($imported > 0) {
                $this->transactionDAO->commit();
                $this->logger->info('Transaction import completed successfully', [
                    'imported' => $imported,
                    'failed' => $failed
                ]);
            } else {
                $this->transactionDAO->rollback();
                $this->logger->warning('No transactions imported, rolling back');
            }

            return new ImportResult(
                $imported > 0,
                $imported,
                $errors,
                $warnings
            );

        } catch (Exception $e) {
            $this->transactionDAO->rollback();
            $this->logger->error('Transaction import failed, rolled back', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Normalize CSV row data to standard transaction format
     */
    private function normalizeTransactionData(array $row)
    {
        // Map common CSV column variations to standard fields
        $columnMap = [
            'symbol' => ['symbol', 'ticker', 'stock', 'security'],
            'shares' => ['shares', 'quantity', 'qty', 'amount'],
            'price' => ['price', 'cost', 'unit_price', 'share_price'],
            'txn_date' => ['date', 'txn_date', 'transaction_date', 'trade_date'],
            'txn_type' => ['type', 'txn_type', 'transaction_type', 'side']
        ];

        $normalized = [];

        foreach ($columnMap as $standardField => $possibleColumns) {
            $value = null;
            
            foreach ($possibleColumns as $column) {
                if (isset($row[$column]) && trim($row[$column]) !== '') {
                    $value = trim($row[$column]);
                    break;
                }
            }
            
            $normalized[$standardField] = $value;
        }

        // Normalize transaction type
        if (!empty($normalized['txn_type'])) {
            $type = strtoupper($normalized['txn_type']);
            if (in_array($type, ['SELL', 'SALE', 'S'])) {
                $normalized['txn_type'] = 'SELL';
            } else {
                $normalized['txn_type'] = 'BUY';
            }
        } else {
            $normalized['txn_type'] = 'BUY'; // Default to BUY
        }

        // Normalize date format
        if (!empty($normalized['txn_date'])) {
            $date = $this->normalizeDate($normalized['txn_date']);
            $normalized['txn_date'] = $date;
        }

        // Clean numeric values
        if (!empty($normalized['shares'])) {
            $normalized['shares'] = floatval(str_replace(',', '', $normalized['shares']));
        }
        
        if (!empty($normalized['price'])) {
            $normalized['price'] = floatval(str_replace(['$', ','], '', $normalized['price']));
        }

        return $normalized;
    }

    /**
     * Normalize date to Y-m-d format
     */
    private function normalizeDate($dateString)
    {
        // Try different date formats
        $formats = ['Y-m-d', 'm/d/Y', 'd/m/Y', 'Y-m-d H:i:s', 'm/d/Y H:i:s'];
        
        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $dateString);
            if ($date) {
                return $date->format('Y-m-d');
            }
        }

        // If all else fails, try strtotime
        $timestamp = strtotime($dateString);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        // Return original if we can't parse it
        return $dateString;
    }

    /**
     * Update portfolio position based on transaction
     */
    private function updatePortfolioPosition(array $transactionData)
    {
        $symbol = $transactionData['symbol'];
        $shares = $transactionData['shares'];
        $price = $transactionData['price'];
        $type = $transactionData['txn_type'];

        // Get current portfolio position
        $currentPosition = $this->portfolioDAO->getPortfolioBySymbol($symbol);

        if ($currentPosition) {
            // Update existing position
            $currentShares = $currentPosition['current_shares'];
            $currentAvgCost = $currentPosition['avg_cost'];

            if ($type === 'BUY') {
                $newShares = $currentShares + $shares;
                $newAvgCost = (($currentShares * $currentAvgCost) + ($shares * $price)) / $newShares;
            } else { // SELL
                $newShares = $currentShares - $shares;
                $newAvgCost = $currentAvgCost; // Keep same average cost for sells
            }

            $this->portfolioDAO->updatePortfolioPosition($symbol, $newShares, $newAvgCost, $price);
            
        } else {
            // New position
            if ($type === 'BUY') {
                $this->portfolioDAO->updatePortfolioPosition($symbol, $shares, $price, $price);
            }
            // For sells of non-existent positions, we might want to log a warning
        }
    }

    /**
     * Get import statistics
     */
    public function getImportStats()
    {
        $totalTransactions = count($this->transactionDAO->getAllTransactions());
        $portfolioPositions = count($this->portfolioDAO->getCurrentPortfolio());
        
        return [
            'total_transactions' => $totalTransactions,
            'portfolio_positions' => $portfolioPositions,
            'last_import' => null // Could be tracked if needed
        ];
    }
}

/**
 * Factory class to create ImportService with all dependencies
 */
class ImportServiceFactory
{
    public static function create(
        DatabaseConnectionInterface $db,
        LoggerInterface $logger,
        ConfigurationInterface $config
    ) {
        // Create validators
        $requiredColumns = ['symbol', 'shares', 'price', 'txn_date'];
        $csvValidator = new CsvFileValidator($requiredColumns, $logger);
        $transactionValidator = new TransactionDataValidator($logger);

        // Create DAOs
        $transactionDAO = new TransactionDAO($db, $logger, $transactionValidator);
        $portfolioDAO = new ImprovedPortfolioDAO($db, $logger);

        // Create CSV parser
        $csvParser = new CsvParser($logger);

        // Create file upload handler
        $uploadConfig = [
            'allowed_types' => ['csv', 'txt'],
            'max_size' => 5 * 1024 * 1024, // 5MB
            'upload_dir' => $config->get('upload.directory', sys_get_temp_dir())
        ];
        $fileUploadHandler = new SecureFileUploadHandler($uploadConfig, $logger);

        return new ImportService(
            $csvParser,
            $fileUploadHandler,
            $transactionDAO,
            $portfolioDAO,
            $csvValidator,
            $transactionValidator,
            $logger
        );
    }
}
