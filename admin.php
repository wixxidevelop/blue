<?php
session_start();

// Initialize database files if they don't exist
$dbDir = 'data';
if (!file_exists($dbDir)) {
    mkdir($dbDir, 0777, true);
}

$systemFile = $dbDir . '/system.json';
$transactionsFile = $dbDir . '/transactions.json';
$settingsFile = $dbDir . '/settings.json';

// Initialize system file
if (!file_exists($systemFile)) {
    $systemData = ['isActive' => true];
    file_put_contents($systemFile, json_encode($systemData, JSON_PRETTY_PRINT));
}

// Initialize transactions file
if (!file_exists($transactionsFile)) {
    file_put_contents($transactionsFile, json_encode([], JSON_PRETTY_PRINT));
}

// Initialize settings file with default values
if (!file_exists($settingsFile)) {
    $defaultSettings = [
        'validPins' => [
            'withdrawal' => ['1234', '5678', '9999'],
            'cot' => ['COT123', 'COT456', 'COT789']
        ],
        'fees' => [
            'miningFee' => [
                'enabled' => true,
                'amount' => '25.00',
                'message' => 'This fee covers blockchain transaction costs'
            ],
            'commission' => [
                'enabled' => true,
                'amount' => '15.00',
                'message' => 'Final processing fee'
            ]
        ],
        'taxCodes' => ['TAX001', 'TAX002', 'TAX003', 'EXEMPT']
    ];
    file_put_contents($settingsFile, json_encode($defaultSettings, JSON_PRETTY_PRINT));
}

// Load current data
$systemData = json_decode(file_get_contents($systemFile), true);
$transactions = json_decode(file_get_contents($transactionsFile), true);
$settings = json_decode(file_get_contents($settingsFile), true);
$isSystemActive = $systemData['isActive'] ?? true;

// Handle admin actions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'toggle_system') {
        $systemData['isActive'] = !$systemData['isActive'];
        file_put_contents($systemFile, json_encode($systemData, JSON_PRETTY_PRINT));
        header('Location: admin.php');
        exit;
    }
    
    if ($action === 'clear_logs') {
        file_put_contents($transactionsFile, json_encode([], JSON_PRETTY_PRINT));
        header('Location: admin.php');
        exit;
    }
    
    if ($action === 'update_settings') {
        $settings = json_decode(file_get_contents($settingsFile), true);
        
        // Update withdrawal PINs
        $withdrawalPins = array_filter(array_map('trim', explode(',', $_POST['withdrawal_pins'])));
        $settings['validPins']['withdrawal'] = $withdrawalPins;
        
        // Update COT PINs
        $cotPins = array_filter(array_map('trim', explode(',', $_POST['cot_pins'])));
        $settings['validPins']['cot'] = $cotPins;
        
        // Update tax codes
        $taxCodes = array_filter(array_map('trim', explode(',', $_POST['tax_codes'])));
        $settings['taxCodes'] = $taxCodes;
        
        // Update mining fee settings
        $settings['fees']['miningFee']['enabled'] = isset($_POST['mining_fee_enabled']);
        $settings['fees']['miningFee']['amount'] = $_POST['mining_fee_amount'];
        $settings['fees']['miningFee']['message'] = $_POST['mining_fee_message'];
        
        // Update commission settings
        $settings['fees']['commission']['enabled'] = isset($_POST['commission_enabled']);
        $settings['fees']['commission']['amount'] = $_POST['commission_amount'];
        $settings['fees']['commission']['message'] = $_POST['commission_message'];
        
        file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT));
        $success = 'Settings updated successfully!';
    }
    
    if ($action === 'export_data') {
        $transactions = json_decode(file_get_contents($transactionsFile), true);
        $systemData = json_decode(file_get_contents($systemFile), true);
        $settings = json_decode(file_get_contents($settingsFile), true);
        
        $exportData = [
            'system' => $systemData,
            'settings' => $settings,
            'transactions' => $transactions,
            'exported_at' => date('Y-m-d H:i:s')
        ];
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="transaction_data_' . date('Y-m-d_H-i-s') . '.json"');
        echo json_encode($exportData, JSON_PRETTY_PRINT);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b p-4">
        <div class="max-w-6xl mx-auto flex justify-between items-center">
            <h1 class="text-lg font-medium text-gray-900">Admin Dashboard</h1>
            <div class="space-x-2">
                <a href="index.php" class="px-3 py-1 text-sm bg-blue-50 text-blue-600 rounded hover:bg-blue-100">User  Page</a>
            </div>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto p-6">
        <!-- Admin Dashboard -->
        <?php if (isset($success)): ?>
            <div class="bg-green-50 text-green-600 p-3 rounded-lg mb-6 text-sm">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column: System Controls & Stats -->
            <div class="space-y-6">
                <!-- System Status -->
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">System Status</h3>
                        <span class="px-2 py-1 text-xs rounded-full <?php echo $isSystemActive ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo $isSystemActive ? 'Active' : 'Offline'; ?>
                        </span>
                    </div>
                    
                    <div class="space-y-3">
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="toggle_system">
                            <button type="submit" class="w-full <?php echo $isSystemActive ? 'bg-red-500 hover:bg-red-600' : 'bg-green-500 hover:bg-green-600'; ?> text-white px-4 py-2 rounded-lg transition-colors">
                                <?php echo $isSystemActive ? 'Turn Off System' : 'Turn On System'; ?>
                            </button>
                        </form>
                        
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="export_data">
                            <button type="submit" class="w-full bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-colors">
                                Export All Data
                            </button>
                        </form>

                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="clear_logs">
                            <button type="submit" class="w-full bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors" onclick="return confirm('Are you sure you want to clear all transaction logs?')">
                                Clear Transaction Logs
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="space-y-4">
                    <div class="bg-white rounded-lg shadow-sm border p-4">
                        <h4 class="text-sm font-medium text-gray-500 mb-1">Total Transactions</h4>
                        <p class="text-2xl font-bold text-gray-900"><?php echo count($transactions); ?></p>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm border p-4">
                        <h4 class="text-sm font-medium text-gray-500 mb-1">Today's Transactions</h4>
                        <p class="text-2xl font-bold text-gray-900">
                            <?php 
                            $today = date('Y-m-d');
                            $todayCount = 0;
                            foreach ($transactions as $transaction) {
                                if (strpos($transaction['timestamp'], $today) === 0) {
                                    $todayCount++;
                                }
                            }
                            echo $todayCount;
                            ?>
                        </p>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm border p-4">
                        <h4 class="text-sm font-medium text-gray-500 mb-1">Valid PINs</h4>
                        <p class="text-lg font-bold text-gray-900"><?php echo count($settings['validPins']['withdrawal']) + count($settings['validPins']['cot']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Middle Column: Settings -->
            <div class="space-y-6">
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="update_settings">
                    
                    <!-- PIN Settings -->
                    <div class="bg-white rounded-lg shadow-sm border p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">PIN Management</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Valid Withdrawal PINs</label>
                                <input type="text" name="withdrawal_pins" value="<?php echo implode(', ', $settings['validPins']['withdrawal']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                <p class="text-xs text-gray-500 mt-1">Separate multiple PINs with commas</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Valid COT PINs</label>
                                <input type="text" name="cot_pins" value="<?php echo implode(', ', $settings['validPins']['cot']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                <p class="text-xs text-gray-500 mt-1">Separate multiple PINs with commas</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Valid Tax Codes</label>
                                <input type="text" name="tax_codes" value="<?php echo implode(', ', $settings['taxCodes']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                <p class="text-xs text-gray-500 mt-1">Separate multiple codes with commas</p>
                            </div>
                        </div>
                    </div>

                    <!-- Fee Settings -->
                    <div class="bg-white rounded-lg shadow-sm border p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Fee Settings</h3>
                        
                        <div class="space-y-6">
                            <!-- Mining Fee -->
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <label class="text-sm font-medium text-gray-700">Mining Fee</label>
                                    <label class="flex items-center">
                                        <input type="checkbox" name="mining_fee_enabled" <?php echo $settings['fees']['miningFee']['enabled'] ? 'checked' : ''; ?> class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-600">Enabled</span>
                                    </label>
                                </div>
                                <div class="space-y-2">
                                    <input type="text" name="mining_fee_amount" value="<?php echo htmlspecialchars($settings['fees']['miningFee']['amount']); ?>" placeholder="Amount (e.g., 25.00)" class="w-full px-3 py-2 border border-gray-300 rounded text-sm">
                                    <textarea name="mining_fee_message" placeholder="Fee message" class="w-full px-3 py-2 border border-gray-300 rounded text-sm h-20"><?php echo htmlspecialchars($settings['fees']['miningFee']['message']); ?></textarea>
                                </div>
                            </div>

                            <!-- Commission Fee -->
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <label class="text-sm font-medium text-gray-700">Commission Fee</label>
                                    <label class="flex items-center">
                                        <input type="checkbox" name="commission_enabled" <?php echo $settings['fees']['commission']['enabled'] ? 'checked' : ''; ?> class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-600">Enabled</span>
                                    </label>
                                </div>
                                <div class="space-y-2">
                                    <input type="text" name="commission_amount" value="<?php echo htmlspecialchars($settings['fees']['commission']['amount']); ?>" placeholder="Amount (e.g., 15.00)" class="w-full px-3 py-2 border border-gray-300 rounded text-sm">
                                    <textarea name="commission_message" placeholder="Fee message" class="w-full px-3 py-2 border border-gray-300 rounded text-sm h-20"><?php echo htmlspecialchars($settings['fees']['commission']['message']); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-green-500 text-white py-3 rounded-lg hover:bg-green-600 transition-colors font-medium">
                        Save All Settings
                    </button>
                </form>
            </div>

            <!-- Right Column: Transaction Logs -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Transactions</h3>
                
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    <?php if (empty($transactions)): ?>
                        <p class="text-gray-500 text-sm">No transactions yet</p>
                    <?php else: ?>
                        <?php foreach (array_reverse(array_slice($transactions, -10)) as $index => $transaction): ?>
                            <div class="border border-gray-200 rounded-lg p-3">
                                <div class="flex justify-between items-start mb-2">
                                    <span class="text-xs font-medium text-gray-900">Transaction #<?php echo count($transactions) - $index; ?></span>
                                    <span class="text-xs text-gray-500"><?php echo date('m/d H:i', strtotime($transaction['timestamp'])); ?></span>
                                </div>
                                
                                <div class="text-xs text-gray-600 space-y-1">
                                    <div class="flex justify-between">
                                        <span>PIN:</span>
                                        <span class="font-mono">****</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>COT:</span>
                                        <span class="font-mono"><?php echo htmlspecialchars(substr($transaction['cotPin'], 0, 3) . '***'); ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Tax:</span>
                                        <span class="font-mono"><?php echo htmlspecialchars($transaction['taxCode']); ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Fees:</span>
                                        <span class="font-mono">$<?php echo htmlspecialchars($transaction['miningFee']); ?> + $<?php echo htmlspecialchars($transaction['commission']); ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>IP:</span>
                                        <span class="font-mono text-xs"><?php echo htmlspecialchars($transaction['ip']); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Database Info -->
        <div class="mt-6 bg-white rounded-lg shadow-sm border p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Database Files</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-600">
                <div class="space-y-1">
                    <div class="font-medium text-gray-900">System Status</div>
                    <div class="font-mono text-xs"><?php echo $systemFile; ?></div>
                    <div class="text-xs">Modified: <?php echo date('Y-m-d H:i:s', filemtime($systemFile)); ?></div>
                </div>
                <div class="space-y-1">
                    <div class="font-medium text-gray-900">Settings</div>
                    <div class="font-mono text-xs"><?php echo $settingsFile; ?></div>
                    <div class="text-xs">Modified: <?php echo date('Y-m-d H:i:s', filemtime($settingsFile)); ?></div>
                </div>
                <div class="space-y-1">
                    <div class="font-medium text-gray-900">Transactions</div>
                    <div class="font-mono text-xs"><?php echo $transactionsFile; ?></div>
                    <div class="text-xs">Modified: <?php echo date('Y-m-d H:i:s', filemtime($transactionsFile)); ?></div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
