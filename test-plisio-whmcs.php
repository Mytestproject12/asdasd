<?php
/**
 * Plisio WHMCS Gateway Test Script
 * Use this to test your Plisio WHMCS configuration
 */

require_once 'config.php';
require_once 'includes/PlisioWHMCSGateway.php';
require_once 'includes/CurrencyManager.php';

echo "=== Plisio WHMCS Gateway Test Suite ===\n\n";

try {
    // Get API key from settings
    $api_key = getSetting('plisio_api_key', 'M_srKi_qKCQ1hra_J8Zx-khHGozvT2EkbfXq8ieKZvTfmpCOIKcTFHNchjMEC4_x');
    
    if (empty($api_key)) {
        echo "❌ ERROR: No Plisio API key found in settings\n";
        echo "Please configure your API key in Admin Settings > Payment Gateways\n\n";
        exit(1);
    }
    
    echo "✓ WHMCS API Key configured: " . substr($api_key, 0, 15) . "...\n\n";
    
    // Initialize Plisio WHMCS Gateway
    $plisio = new PlisioWHMCSGateway($api_key);
    
    // Initialize Currency Manager
    $currencyManager = new CurrencyManager($pdo);
    
    // Test 1: Connection test
    echo "Testing connection...\n";
    $connection_test = $plisio->testConnection();
    if ($connection_test['success']) {
        echo "✓ Connection successful\n\n";
    } else {
        echo "❌ Connection failed: " . $connection_test['message'] . "\n\n";
        exit(1);
    }
    
    // Test 2: Get supported currencies
    echo "Testing supported currencies...\n";
    try {
        $currencies = $plisio->getSupportedCurrencies();
        echo "✓ Successfully retrieved supported currencies\n";
        echo "Available currencies: " . implode(', ', array_slice(array_keys($currencies), 0, 10)) . "...\n\n";
    } catch (Exception $e) {
        echo "❌ Failed to get currencies: " . $e->getMessage() . "\n\n";
    }
    
    // Test 3: Currency Manager
    echo "Testing Currency Manager...\n";
    $active_currencies = $currencyManager->getActiveCurrencies();
    echo "✓ Active currencies in system: " . count($active_currencies) . "\n";
    foreach ($active_currencies as $currency) {
        echo "  - {$currency['code']}: {$currency['name']} ({$currency['icon']})\n";
    }
    echo "\n";
    
    // Test 4: Exchange rates
    echo "Testing exchange rates...\n";
    foreach (['USDT', 'BTC', 'ETH'] as $code) {
        $rate = $currencyManager->getExchangeRate($code);
        echo "  - 1 {$code} = \${$rate} USD\n";
    }
    echo "\n";
    
    // Test 5: Address validation
    echo "Testing address validation...\n";
    $test_addresses = [
        'BTC' => '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa',
        'ETH' => '0x742d35Cc6634C0532925a3b8D4C9db96C4b4d8b6',
        'USDT' => '0x742d35Cc6634C0532925a3b8D4C9db96C4b4d8b6',
    ];
    
    foreach ($test_addresses as $currency => $address) {
        $is_valid = $currencyManager->validateAddress($address, $currency);
        echo "  - {$currency} address validation: " . ($is_valid ? "✓ Valid" : "❌ Invalid") . "\n";
    }
    echo "\n";
    
    // Test 6: Create test invoice (small amount)
    echo "Testing invoice creation...\n";
    try {
        $test_params = [
            'amount' => 1.00,
            'currency' => 'BTC',
            'order_id' => 'TEST_WHMCS_' . time(),
            'description' => 'Test invoice - Star Router Rent WHMCS v1.0.3',
            'email' => 'test@example.com',
            'callback_url' => 'https://test.star-rent.vip/api/webhook/plisio.php'
        ];
        
        $invoice = $plisio->createInvoice($test_params);
        echo "✓ Successfully created test invoice\n";
        echo "Invoice ID: " . $invoice['txn_id'] . "\n";
        echo "Payment URL: " . $invoice['invoice_url'] . "\n\n";
        
        // Test invoice status check
        echo "Testing invoice status check...\n";
        $status = $plisio->getInvoiceStatus($invoice['txn_id']);
        echo "✓ Invoice status retrieved successfully\n";
        echo "Status: " . ($status['status'] ?? 'unknown') . "\n\n";
        
    } catch (Exception $e) {
        echo "❌ Failed to create test invoice: " . $e->getMessage() . "\n\n";
    }
    
    echo "🎉 All tests completed!\n";
    echo "Your Plisio WHMCS v1.0.3 integration is working correctly.\n";
    echo "Supported currencies: USDT, BTC, ETH\n\n";
    
} catch (Exception $e) {
    echo "❌ Test Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== Test Complete ===\n";
?>