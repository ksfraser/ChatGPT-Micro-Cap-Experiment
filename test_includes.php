<?php
echo "Testing includes step by step...\n";

echo "1. Testing auth_check.php...\n";
ob_start();
include 'web_ui/auth_check.php';
$output = ob_get_contents();
ob_end_clean();
if (!empty($output)) {
    echo "OUTPUT FOUND from auth_check.php: " . $output . "\n";
} else {
    echo "No output from auth_check.php\n";
}

echo "2. Testing NavigationManager...\n";
ob_start();
include 'web_ui/classes/Navigation/NavigationManager.php';
$output = ob_get_contents();
ob_end_clean();
if (!empty($output)) {
    echo "OUTPUT FOUND from NavigationManager.php: " . $output . "\n";
} else {
    echo "No output from NavigationManager.php\n";
}

echo "3. Testing UiRenderer...\n";
ob_start();
include 'web_ui/classes/Ui/UiRenderer.php';
$output = ob_get_contents();
ob_end_clean();
if (!empty($output)) {
    echo "OUTPUT FOUND from UiRenderer.php: " . $output . "\n";
} else {
    echo "No output from UiRenderer.php\n";
}

echo "Done testing.\n";
?>
