<?php
/**
 * Test Currency Rates and Plisio Integration
 * Use this to test the new currency system
 */

require_once 'config.php';
require_once 'includes/CurrencyManager.php';
require_once 'includes/PaymentManager.php';

echo "=== Currency System Test ===\n\n";

try {
    // Initialize managers
    $currencyManager = new CurrencyManager($pdo);
    $paymentManager = new PaymentManager($pdo);
    
    echo "✓ Managers initialized successfully\n\n";
    
    // Test 1: Get active currencies
    echo "Testing active currencies...\n";
    $currencies = $currencyManager->getActiveCurrencies();
    echo "✓ Found " . count($currencies) . " active currencies:\n";
    foreach ($currencies as $currency) {
        echo "  - {$currency['code']}: {$currency['name']} ({$currency['icon']})\n";
    }
    echo "\n";
    
    // Test 2: Get live exchange rates
    echo "Testing live exchange rates...\n";
    foreach ($currencies as $currency) {
        $rate = $currencyManager->getExchangeRate($currency['code']);
        echo "  - {$currency['code']}: \${$rate}\n";
    }
    echo "\n";
    
    // Test 3: Update rates from Plisio
    echo "Testing rate update from Plisio...\n";
    $success = $currencyManager->updateExchangeRates();
    if ($success) {
        echo "✓ Rates updated successfully\n";
    } else {
        echo "⚠ Rate update failed (check API key)\n";
    }
    echo "\n";
    
    // Test 4: Test address validation
    echo "Testing address validation...\n";
    $test_addresses = [
        'BTC' => '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa',
        'ETH' => '0x742d35Cc6634C0532925a3b8D4C9db96C4b4d8b6',
        'BTC' => '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa',
        'TRX' => 'TRX9aJEznxW7fzVHvP4nBkCpSP8SvQrn4g'
    ];
    
    foreach ($test_addresses as $currency => $address) {
        $is_valid = $currencyManager->validateAddress($address, $currency);
        echo "  - {$currency}: " . ($is_valid ? "✓ Valid" : "❌ Invalid") . "\n";
    }
    echo "\n";
    
    // Test 5: Test Plisio supported currencies
    echo "Testing Plisio supported currencies...\n";
    $plisio_key = getSetting('plisio_api_key');
    if ($plisio_key) {
        require_once 'includes/PlisioWHMCSGateway.php';
        $plisio = new PlisioWHMCSGateway($plisio_key);
        $supported = $plisio->getSupportedCurrencies();
        
        if (is_array($supported) && !empty($supported)) {
            echo "✓ Plisio supports " . count($supported) . " currencies from our list:\n";
            foreach ($supported as $code => $data) {
                echo "  - {$code}: Available\n";
            }
        } else {
            echo "⚠ No supported currencies found or API error\n";
        }
    } else {
        echo "⚠ No Plisio API key configured\n";
    }
    
    echo "\n🎉 Currency system test completed!\n";
    
} catch (Exception $e) {
    echo "❌ Test Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
?>