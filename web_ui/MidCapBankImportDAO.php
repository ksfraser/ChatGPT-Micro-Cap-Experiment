<?php

require_once __DIR__ . '/CommonDAO.php';
require_once __DIR__ . '/SchemaMigrator.php';

class MidCapBankImportDAO extends CommonDAO {
    public function __construct() {
        parent::__construct('LegacyDatabaseConfig');
        // Run schema migrations on instantiation
        $schemaDir = __DIR__ . '/schema';
        $migrator = new SchemaMigrator($this->pdo, $schemaDir);
        $migrator->migrate();
    }
    // Parse Account Holdings CSV
    public function parseAccountHoldingsCSV($filePath) {
        $rows = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            $header = fgetcsv($handle);
            while (($data = fgetcsv($handle)) !== false) {
                $rows[] = array_combine($header, $data);
            }
            fclose($handle);
        }
        return $rows;
    }

    // Parse Transaction History CSV
    public function parseTransactionHistoryCSV($filePath) {
        $rows = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            $header = fgetcsv($handle);
            while (($data = fgetcsv($handle)) !== false) {
                $rows[] = array_combine($header, $data);
            }
            fclose($handle);
        }
        return $rows;
    }

    // Staging: Save parsed data to a temp CSV (not tracked by git)
    public function saveStagingCSV($rows, $type) {
        $file = __DIR__ . "/../bank_imports/staging_{$type}_" . date('Ymd_His') . ".csv";
        $fp = fopen($file, 'w');
        if (!empty($rows)) {
            fputcsv($fp, array_keys($rows[0]));
            foreach ($rows as $row) {
                fputcsv($fp, $row);
            }
        }
        fclose($fp);
        return $file;
    }

    // Insert staged data into mid-cap tables
    public function importToMidCap($rows, $type) {
        // Only transactions are supported for now
        if ($type !== 'holdings') {
            $table = 'midcap_transactions';
            foreach ($rows as $row) {
                $this->insertTransaction($table, $row);
            }
        }
        return true;
    }

    private function insertTransaction($table, $row) {
        $stmt = $this->pdo->prepare("INSERT INTO $table (bank_name, account_number, symbol, txn_type, shares, price, amount, txn_date, settlement_date, currency_subaccount, market, description, currency_of_price, commission, exchange_rate, currency_of_amount, settlement_instruction, exchange_rate_cad, cad_equivalent, extra) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $symbol = $row['Ticker'] ?? $row['Symbol'] ?? $row['symbol'] ?? null;
        $txnType = $row['Type'] ?? $row['Transaction Type'] ?? $row['txn_type'] ?? null;
        $shares = $row['Shares'] ?? $row['Quantity'] ?? $row['shares'] ?? 0;
        $price = $row['Price'] ?? $row['price'] ?? 0;
        $amount = $row['Amount'] ?? $row['Total'] ?? $row['amount'] ?? 0;
        $txnDate = $row['Date'] ?? $row['Transaction Date'] ?? $row['txn_date'] ?? null;
        $settlementDate = $row['Settlement Date'] ?? null;
        $currencySubaccount = $row['Currency of Sub-account Held In'] ?? null;
        $market = $row['Market'] ?? null;
        $description = $row['Description'] ?? null;
        $currencyOfPrice = $row['Currency of Price'] ?? null;
        $commission = $row['Commission'] ?? null;
        $exchangeRate = $row['Exchange Rate'] ?? null;
        $currencyOfAmount = $row['Currency of Amount'] ?? null;
        $settlementInstruction = $row['Settlement Instruction'] ?? null;
        $exchangeRateCad = $row['Exchange Rate (Canadian Equivalent)'] ?? null;
        $cadEquivalent = $row['Canadian Equivalent'] ?? null;
        $extra = json_encode($row);
        $stmt->execute([
            $row['bank_name'] ?? '',
            $row['account_number'] ?? '',
            $symbol,
            $txnType,
            $shares,
            $price,
            $amount,
            $txnDate,
            $settlementDate,
            $currencySubaccount,
            $market,
            $description,
            $currencyOfPrice,
            $commission,
            $exchangeRate,
            $currencyOfAmount,
            $settlementInstruction,
            $exchangeRateCad,
            $cadEquivalent,
            $extra
        ]);
    }

    // Try to identify bank/account from CSV header or data
    public function identifyBankAccount($rows) {
        // TODO: Implement logic to guess bank/account from data
        // Return null if not sure
        return null;
    }
}
