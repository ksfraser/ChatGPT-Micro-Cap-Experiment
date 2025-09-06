<?php
// run_python_command.php
if (!isset($_POST['command'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No command provided']);
    exit;
}
$command = $_POST['command'];

// Detect OS and adjust python command
if (stripos(PHP_OS, 'WIN') === 0) {
    $python = 'python';
} else {
    $python = 'python3';
}

// Only allow safe commands (basic check)
$allowed = [
    'enhanced_automation.py',
    'simple_automation.py',
    'enhanced_automation.py --market_cap micro',
    'enhanced_automation.py --market_cap small',
    'enhanced_automation.py --market_cap mid',
    '-c "from enhanced_automation import *; engine = EnhancedAutomationEngine(\'micro\'); engine.start_session()"',
    '-c "from database_architect import *; arch = DatabaseArchitect(); arch.show_sessions()"',
    '-c "import os; os.system(\'taskkill /f /im python.exe\')"',
    '-c "from enhanced_automation import *; engine = EnhancedAutomationEngine(\'micro\'); engine.show_positions()"',
    '-c "from database_architect import *; arch = DatabaseArchitect(); arch.test_connections()"',
    '-c "import yaml; print(yaml.safe_load(open(\'db_config_refactored.yml\')))"',
    '-c "import pandas as pd; print(pd.read_csv(\'chatgpt_trade_log.csv\').tail())"',
];

$safe = false;
foreach ($allowed as $a) {
    if (trim($command) === $a) {
        $safe = true;
        break;
    }
}
if (!$safe) {
    http_response_code(403);
    echo json_encode(['error' => 'Command not allowed']);
    exit;
}

$full = $python . ' ' . $command;
$output = shell_exec($full . ' 2>&1');
echo json_encode(['output' => $output]);
