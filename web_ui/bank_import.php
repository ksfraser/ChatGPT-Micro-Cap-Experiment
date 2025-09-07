<?php
require_once __DIR__ . '/MidCapBankImportDAO.php';

$dao = new MidCapBankImportDAO();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $type = $_POST['csv_type'] ?? '';
    $tmpName = $_FILES['csv_file']['tmp_name'];
    if ($type === 'holdings') {
        $rows = $dao->parseAccountHoldingsCSV($tmpName);
    } else {
        $rows = $dao->parseTransactionHistoryCSV($tmpName);
    }
    $stagingFile = $dao->saveStagingCSV($rows, $type);
    $bankInfo = $dao->identifyBankAccount($rows);
    if ($bankInfo === null) {
        $prompt = 'Could not identify bank/account. Please enter details:';
    } else {
        $prompt = 'Bank/account identified: ' . htmlspecialchars(json_encode($bankInfo));
    }
    // Show confirmation form
    echo "<h2>Staging Complete</h2>";
    echo "<p>$prompt</p>";
    echo '<form method="post" action="bank_import.php">';
    echo '<input type="hidden" name="staging_file" value="' . htmlspecialchars($stagingFile) . '">';
    echo '<input type="text" name="bank_name" placeholder="Bank Name" required> ';
    echo '<input type="text" name="account_number" placeholder="Account Number" required> ';
    echo '<input type="hidden" name="csv_type" value="' . htmlspecialchars($type) . '">';
    echo '<button type="submit" name="confirm_import">Import</button>';
    echo '</form>';
    exit;
}

if (isset($_POST['confirm_import'])) {
    $stagingFile = $_POST['staging_file'];
    $type = $_POST['csv_type'];
    $bank = $_POST['bank_name'];
    $acct = $_POST['account_number'];
    $rows = [];
    if (($handle = fopen($stagingFile, 'r')) !== false) {
        $header = fgetcsv($handle);
        while (($data = fgetcsv($handle)) !== false) {
            $rows[] = array_combine($header, $data);
        }
        fclose($handle);
    }
    // Add bank/account info to each row
    foreach ($rows as &$row) {
        $row['bank_name'] = $bank;
        $row['account_number'] = $acct;
    }
    $dao->importToMidCap($rows, $type);
    echo "<h2>Import Complete</h2>";
    exit;
}

// Upload form
?>
<h2>Import Bank CSV</h2>
<form method="post" enctype="multipart/form-data">
    <label>CSV Type:
        <select name="csv_type">
            <option value="holdings">Account Holdings</option>
            <option value="transactions">Transaction History</option>
        </select>
    </label><br><br>
    <input type="file" name="csv_file" required><br><br>
    <button type="submit">Upload</button>
</form>
