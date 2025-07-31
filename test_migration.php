<?php
// Simple script to test migration without interaction
$command = 'php bin/console doctrine:migrations:migrate --no-interaction';
echo "Running: $command\n";
passthru($command, $returnCode);
echo "\nCommand completed with return code: $returnCode\n";
