<?php
// Hashed Password Generator
// Copyright (C) 2025 EmmeV. All rights reserved.
// Usage: php generate_hash.php password_to_hash
// Example: php generate_hash.php admin

if ($argc > 1) {
    $primoArgomento = $argv[1];
    $password = password_hash($primoArgomento, PASSWORD_DEFAULT);
    echo "Hash della password '" . $primoArgomento . "': " . $password . "\n";
} else {
    $password = password_hash("admin", PASSWORD_DEFAULT);
    echo "Hash della password 'admin': " . $password . "\n";
}

?>