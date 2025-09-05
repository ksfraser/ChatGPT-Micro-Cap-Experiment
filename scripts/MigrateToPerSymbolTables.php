<?php
require_once __DIR__ . '/../src/MigrateSymbolsCliHandler.php';

$handler = new MigrateSymbolsCliHandler();
$handler->run($argv);
