<?php

// Execute setup commands
echo "Running setup commands...\n";

// Example: Generate application key
echo "Generating application key...\n";
exec('php artisan key:generate --ansi', $output, $exitCode);
if ($exitCode !== 0) {
    echo "Error generating application key.\n";
    exit(1);
}

// Run additional setup commands
//echo "Running additional setup commands...\n";



// Run `php artisan storage:link`
echo "Creating storage symlink...\n";
exec('php artisan storage:link', $output, $exitCode);
if ($exitCode !== 0) {
    echo "Error creating storage symlink.\n";
    exit(1);
}

// echo "Starting Laravel development server...\n";
// exec('php artisan serve', $output, $exitCode);
// if ($exitCode !== 0) {
//     echo "Error starting Laravel development server.\n";
//     exit(1);
// } else {
//     $serverUrl = '';
//     foreach ($output as $line) {
//         if (strpos($line, 'http://') !== false) {
//             $serverUrl = $line;
//             break;
//         }
//     }
//     if ($serverUrl) {
//         echo "Laravel development server started successfully.\n";
//         echo $serverUrl . "\n";
//     } else {
//         echo "Error: Unable to retrieve server URL.\n";
//     }
// }


// Add any other setup commands here

echo "Setup completed successfully.\n";
