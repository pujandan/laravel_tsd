<?php

// Simple standalone test without Laravel dependencies
echo "=== Laravel AppSecure Test (Standalone) ===\n";

// Use the same test key as Flutter
$testKey = 'abcdefghijklmnopqrstuvwxyz123456'; // 32 characters for AES-256

echo "Test Key: $testKey\n";
echo "Testing encrypted value from Flutter...\n";
echo "Encrypted: CtSRD/qwZsLQFcVOJ+5zbxftre0v12mpRZAnyVd0vrfsiAmt3/QSLn0Mcniplva1\n";
echo "\nDecrypting...\n";

try {
    $ivLength = 16;
    $method = 'aes-256-cbc';

    // Base64 decode
    $decoded = base64_decode('CtSRD/qwZsLQFcVOJ+5zbxftre0v12mpRZAnyVd0vrfsiAmt3/QSLn0Mcniplva1');

    if ($decoded === false || strlen($decoded) < $ivLength) {
        throw new Exception('Invalid encrypted format');
    }

    // Extract IV (first 16 bytes)
    $iv = substr($decoded, 0, $ivLength);

    // Extract ciphertext (after IV)
    $ciphertext = substr($decoded, $ivLength);

    echo "IV length: " . strlen($iv) . "\n";
    echo "Ciphertext length: " . strlen($ciphertext) . "\n";

    // Decrypt
    $decrypted = openssl_decrypt($ciphertext, $method, $testKey, OPENSSL_RAW_DATA, $iv);

    if ($decrypted === false) {
        throw new Exception('Decryption failed');
    }

    echo "Decrypted: $decrypted\n";

    if ($decrypted === 'Hello from Flutter!') {
        echo "\n✅ SUCCESS: Laravel decrypts Flutter encryption!\n";
    } else {
        echo "\n❌ FAILED: Data mismatch!\n";
        echo "Expected: Hello from Flutter!\n";
        echo "Got: $decrypted\n";
    }

    // Now test Laravel encryption and provide encrypted value for Flutter test
    echo "\n=== Testing Laravel Encryption ===\n";
    $testData = 'Hello from Laravel!';
    echo "Original: $testData\n";

    // Generate random IV
    $iv = openssl_random_pseudo_bytes($ivLength);

    // Encrypt
    $encrypted = openssl_encrypt($testData, $method, $testKey, OPENSSL_RAW_DATA, $iv);

    if ($encrypted === false) {
        throw new Exception('Encryption failed');
    }

    // Combine IV + ciphertext, then base64 encode
    $encryptedResult = base64_encode($iv . $encrypted);

    echo "Encrypted: $encryptedResult\n";
    echo "\nCopy this to Flutter test:\n";
    echo "const laravelEncrypted = '$encryptedResult';\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Manual Flutter Test Instructions ===\n";
echo "1. Copy the encrypted value above\n";
echo "2. Test in Flutter:\n";
echo "   final decrypted = AppSecure.instance.decrypt('$encryptedResult');\n";
echo "   print('Decrypted: \$decrypted');\n";