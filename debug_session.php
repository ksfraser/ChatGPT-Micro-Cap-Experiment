<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Debug session initialization:\n";

require_once 'web_ui/SessionManager.php';
$sessionManager = SessionManager::getInstance();

echo "Session active: " . ($sessionManager->isSessionActive() ? 'Yes' : 'No') . "\n";
echo "Session status: " . session_status() . " (1=none, 2=active, 3=disabled)\n";
echo "Headers sent: " . (headers_sent($file, $line) ? "Yes at $file:$line" : "No") . "\n";
echo "Session error: " . ($sessionManager->getInitializationError() ?: "None") . "\n";
echo "Session save path: " . session_save_path() . "\n";
echo "Session save path exists: " . (is_dir(session_save_path()) ? "Yes" : "No") . "\n";
echo "Session save path writable: " . (is_writable(session_save_path()) ? "Yes" : "No") . "\n";
?>
