<?php
require_once __DIR__ . '/MidCapBankImportDAO.php';
$dao = new MidCapBankImportDAO();



// Add brokerage
if (isset($_POST['add_brokerage'])) {
    $name = trim($_POST['brokerage_name']);
    $stmt = $dao->getPdo()->prepare('INSERT IGNORE INTO brokerages (name) VALUES (?)');
    $stmt->execute([$name]);
}
// List brokerages
$brokerages = $dao->getPdo()->query('SELECT * FROM brokerages ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
?>
<h2>Brokerages</h2>
<form method="post">
    <input type="text" name="brokerage_name" placeholder="Brokerage Name" required>
    <button type="submit" name="add_brokerage">Add Brokerage</button>
</form>
<ul>
<?php foreach ($brokerages as $b): ?>
    <li><?= htmlspecialchars($b['name']) ?></li>
<?php endforeach; ?>
</ul>
