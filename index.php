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

// Initialize default files if they don't exist
if (!file_exists($systemFile)) {
    file_put_contents($systemFile, json_encode(['isActive' => true], JSON_PRETTY_PRINT));
}

// Load system status
$systemData = json_decode(file_get_contents($systemFile), true);
$isSystemActive = $systemData['isActive'] ?? false;

// Initialize settings file if it doesn't exist
if (!file_exists($settingsFile)) {
    $defaultSettings = [
        'validPins' => [
            'withdrawal' => ['1234', '5678'],
            'cot' => ['COT123', 'COT456']
        ],
        'taxCodes' => ['TAX001', 'TAX002'],
        'fees' => [
            'miningFee' => [
                'enabled' => true,
                'amount' => '50',
                'message' => 'Mining fee required to process blockchain transaction.'
            ],
            'commission' => [
                'enabled' => true,
                'amount' => '25',
                'message' => 'Commission fee for transaction processing.'
            ]
        ]
    ];
    file_put_contents($settingsFile, json_encode($defaultSettings, JSON_PRETTY_PRINT));
}

// Initialize transactions file if it doesn't exist
if (!file_exists($transactionsFile)) {
    file_put_contents($transactionsFile, json_encode([], JSON_PRETTY_PRINT));
}

// Load settings
$settings = json_decode(file_get_contents($settingsFile), true);

// Handle navigation via query parameter
if (isset($_GET['step']) && in_array($_GET['step'], ['pin_entry', 'cot_entry', 'tax_entry', 'fee_payment', 'commission_payment', 'completed'])) {
    $_SESSION['transaction_step'] = $_GET['step'];
}

// Initialize transaction session if not exists
if (!isset($_SESSION['transaction_step'])) {
    $_SESSION['transaction_step'] = 'pin_entry';
    $_SESSION['transaction_data'] = [];
}

$step = $_SESSION['transaction_step'];
$transactionData = $_SESSION['transaction_data'];
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isSystemActive) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'verify_pin' && $step === 'pin_entry') {
        $pin = $_POST['pin'] ?? '';
        if (in_array($pin, $settings['validPins']['withdrawal'])) {
            $_SESSION['transaction_data']['withdrawalPin'] = $pin;
            $_SESSION['transaction_step'] = 'cot_entry';
            header('Location: index.php?step=cot_entry');
            exit;
        } else {
            $error = 'Invalid withdrawal PIN. Please try again.';
        }
    }
    
    elseif ($action === 'verify_cot' && $step === 'cot_entry') {
        $cotPin = $_POST['cot_pin'] ?? '';
        if (in_array($cotPin, $settings['validPins']['cot'])) {
            $_SESSION['transaction_data']['cotPin'] = $cotPin;
            $_SESSION['transaction_step'] = 'tax_entry';
            header('Location: index.php?step=tax_entry');
            exit;
        } else {
            $error = 'Invalid COT PIN. Please contact support for assistance.';
        }
    }
    
    elseif ($action === 'verify_tax' && $step === 'tax_entry') {
        $taxCode = $_POST['tax_code'] ?? '';
        if (in_array($taxCode, $settings['taxCodes'])) {
            $_SESSION['transaction_data']['taxCode'] = $taxCode;
            $_SESSION['transaction_step'] = 'fee_payment';
            header('Location: index.php?step=fee_payment');
            exit;
        } else {
            $error = 'Invalid tax clearance code. Please verify and try again.';
        }
    }
    
    elseif ($action === 'pay_mining_fee' && $step === 'fee_payment') {
        // Check if mining fee is disabled - if so, advance to next step
        if (!$settings['fees']['miningFee']['enabled']) {
            $_SESSION['transaction_step'] = 'commission_payment';
            header('Location: index.php?step=commission_payment');
            exit;
        } else {
            // If enabled, show payment failed message (original behavior)
            $error = 'Payment failed. Please try again or contact support.';
        }
    }
    
    elseif ($action === 'pay_commission' && $step === 'commission_payment') {
        // Check if commission fee is disabled - if so, advance to completion
        if (!$settings['fees']['commission']['enabled']) {
            $_SESSION['transaction_step'] = 'completed';
            header('Location: index.php?step=completed');
            exit;
        } else {
            // If enabled, show payment failed message (original behavior)
            $error = 'Payment failed. Please try again or contact support.';
        }
    }
    
    elseif ($action === 'start_new') {
        $_SESSION['transaction_step'] = 'pin_entry';
        $_SESSION['transaction_data'] = [];
        header('Location: index.php?step=pin_entry');
        exit;
    }
}

// Get current step data
$currentStepData = [
    'pin_entry' => [
        'title' => 'Withdrawal Authorization',
        'description' => 'Please enter your withdrawal PIN to begin the transaction process.',
        'icon' => '🔐'
    ],
    'cot_entry' => [
        'title' => 'Certificate of Transfer (COT)',
        'description' => 'Enter your Certificate of Transfer PIN to verify transaction eligibility.',
        'icon' => '📋'
    ],
    'tax_entry' => [
        'title' => 'Tax Clearance Verification',
        'description' => 'Provide your tax clearance code to comply with regulatory requirements.',
        'icon' => '📊'
    ],
    'fee_payment' => [
        'title' => 'Mining Fee Payment',
        'description' => $settings['fees']['miningFee']['message'] ?? 'Processing fee required to complete transaction.',
        'icon' => '⛏️'
    ],
    'commission_payment' => [
        'title' => 'Commission Payment',
        'description' => $settings['fees']['commission']['message'] ?? 'Final processing fee to complete your withdrawal.',
        'icon' => '💳'
    ],
    'completed' => [
        'title' => 'Transaction Complete',
        'description' => 'Your withdrawal has been processed successfully.',
        'icon' => '✅'
    ]
];

$stepInfo = $currentStepData[$step] ?? $currentStepData['pin_entry'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Transaction Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Smartsupp Live Chat script -->
<script type="text/javascript">
var _smartsupp = _smartsupp || {};
_smartsupp.key = '90c97f017cc0f4eac2a1c65d67d99245acb0817e';
window.smartsupp||(function(d) {
  var s,c,o=smartsupp=function(){ o._.push(arguments)};o._=[];
  s=d.getElementsByTagName('script')[0];c=d.createElement('script');
  c.type='text/javascript';c.charset='utf-8';c.async=true;
  c.src='https://www.smartsuppchat.com/loader.js?';s.parentNode.insertBefore(c,s);
})(document);
</script>
<noscript> Powered by <a href=“https://www.smartsupp.com” target=“_blank”>Smartsupp</a></noscript>
    <script>
        function confirmPayment(type) {
            // Check if the fee is disabled - if so, submit the form immediately
            <?php if ($step === 'fee_payment' && !$settings['fees']['miningFee']['enabled']): ?>
            if (type === 'mining') {
                // Submit form directly since mining fee is disabled
                document.getElementById('miningFeeForm').submit();
                return true;
            }
            <?php endif; ?>
            
            <?php if ($step === 'commission_payment' && !$settings['fees']['commission']['enabled']): ?>
            if (type === 'commission') {
                // Submit form directly since commission fee is disabled
                document.getElementById('commissionFeeForm').submit();
                return true;
            }
            <?php endif; ?>
            
            // Original behavior for enabled fees - show confirming message and fail
            const button = event.target;
            const originalText = button.textContent;
            button.textContent = 'Confirming...';
            button.disabled = true;
            
            // After 2 seconds, show failed message and re-enable button
            setTimeout(() => {
                button.textContent = 'Failed - Try Again';
                button.disabled = false;
                button.classList.remove('bg-yellow-600', 'hover:bg-yellow-700', 'bg-purple-600', 'hover:bg-purple-700');
                button.classList.add('bg-red-600', 'hover:bg-red-700');
                
                // Reset button after 3 seconds
                setTimeout(() => {
                    button.textContent = originalText;
                    button.classList.remove('bg-red-600', 'hover:bg-red-700');
                    button.classList.add('bg-black', 'hover:bg-gray-800');
                }, 3000);
            }, 2000);
            
            return false; // Prevent form submission for enabled fees
        }
    </script>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
        }
        .step-indicator {
            transition: all 0.3s ease;
        }
        .step-active {
            background: #000000;
            color: white;
        }
        .step-completed {
            background: #374151;
            color: white;
        }
        .step-pending {
            background: #f3f4f6;
            color: #6b7280;
        }
        
        /* Custom responsive adjustments */
        @media (max-width: 640px) {
            .step-indicator {
                width: 2rem;
                height: 2rem;
                font-size: 0.75rem;
            }
            .step-connection {
                width: 1rem;
            }
        }
        
        /* Ensure smooth transitions on all devices */
        * {
            -webkit-tap-highlight-color: transparent;
        }
        
        /* Better touch targets on mobile */
        @media (max-width: 768px) {
            button, input[type="submit"] {
                min-height: 48px;
            }
            input[type="text"], input[type="password"] {
                min-height: 48px;
            }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->


    <div class="max-w-4xl mx-auto px-4 sm:px-6 py-6 sm:py-8">
        <?php if (!$isSystemActive): ?>
            <!-- System Offline Message -->
            <div class="bg-gray-50 border border-gray-300 rounded-lg p-6 sm:p-8 text-center">
                <div class="text-gray-700 text-4xl sm:text-6xl mb-4">🚫</div>
                <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-2">System Temporarily Offline</h2>
                <p class="text-gray-700 mb-4 text-sm sm:text-base">Our transaction system is currently undergoing maintenance. Please try again later.</p>
                <p class="text-xs sm:text-sm text-gray-500">If you need immediate assistance, please contact support.</p>
            </div>
        <?php else: ?>
            <!-- Progress Indicator -->
            <div class="bg-white rounded-lg shadow-sm border p-4 sm:p-6 mb-6">
                <!-- Mobile Step Indicator (Horizontal Scroll) -->
                <div class="sm:hidden">
                    <div class="flex items-center space-x-2 overflow-x-auto pb-2">
                        <?php 
                        $steps = ['pin_entry', 'cot_entry', 'tax_entry', 'fee_payment', 'commission_payment', 'completed'];
                        $stepLabels = ['PIN', 'COT', 'Tax', 'Mining', 'Commission', 'Done'];
                        $currentStepIndex = array_search($step, $steps);
                        
                        foreach ($steps as $index => $stepKey):
                            $isActive = $stepKey === $step;
                            $isCompleted = $index < $currentStepIndex;
                            $isPending = $index > $currentStepIndex;
                            
                            $classes = 'step-indicator w-8 h-8 rounded-full flex items-center justify-center text-xs font-medium flex-shrink-0 ';
                            if ($isActive) $classes .= 'step-active';
                            elseif ($isCompleted) $classes .= 'step-completed';
                            else $classes .= 'step-pending';
                        ?>
                            <div class="flex items-center flex-shrink-0">
                                <div class="<?php echo $classes; ?>">
                                    <?php echo $index + 1; ?>
                                </div>
                                <?php if ($index < count($steps) - 1): ?>
                                    <div class="w-4 h-0.5 step-connection <?php echo $isCompleted ? 'bg-gray-400' : 'bg-gray-300'; ?> mx-1 flex-shrink-0"></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-3">
                        <div class="text-xs text-gray-500">Step <?php echo $currentStepIndex + 1; ?> of <?php echo count($steps); ?></div>
                        <div class="text-sm font-medium text-gray-700"><?php echo $stepLabels[$currentStepIndex] ?? 'Unknown'; ?></div>
                    </div>
                </div>

                <!-- Desktop Step Indicator -->
                <div class="hidden sm:block">
                    <div class="flex items-center justify-center space-x-4">
                        <?php 
                        $steps = ['pin_entry', 'cot_entry', 'tax_entry', 'fee_payment', 'commission_payment', 'completed'];
                        $stepLabels = ['PIN', 'COT', 'Tax', 'Mining Fee', 'Commission', 'Complete'];
                        $currentStepIndex = array_search($step, $steps);
                        
                        foreach ($steps as $index => $stepKey):
                            $isActive = $stepKey === $step;
                            $isCompleted = $index < $currentStepIndex;
                            $isPending = $index > $currentStepIndex;
                            
                            $classes = 'step-indicator w-10 h-10 rounded-full flex items-center justify-center text-sm font-medium ';
                            if ($isActive) $classes .= 'step-active';
                            elseif ($isCompleted) $classes .= 'step-completed';
                            else $classes .= 'step-pending';
                        ?>
                            <div class="flex items-center">
                                <div class="<?php echo $classes; ?>">
                                    <?php echo $index + 1; ?>
                                </div>
                                <?php if ($index < count($steps) - 1): ?>
                                    <div class="w-8 h-0.5 <?php echo $isCompleted ? 'bg-gray-400' : 'bg-gray-300'; ?> mx-2"></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mt-4 text-sm text-gray-600 flex justify-between items-center">
                        <div>
                            Step <?php echo $currentStepIndex + 1; ?> of <?php echo count($steps); ?>: 
                            <span class="font-medium"><?php echo $stepLabels[$currentStepIndex] ?? 'Unknown'; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="bg-white rounded-lg shadow-sm border p-6 sm:p-8">
                <div class="text-center mb-6 sm:mb-8">
                    <div class="text-4xl sm:text-6xl mb-4"><?php echo $stepInfo['icon']; ?></div>
                    <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-2"><?php echo $stepInfo['title']; ?></h2>
                    <p class="text-gray-600 text-sm sm:text-base px-2 sm:px-0"><?php echo $stepInfo['description']; ?></p>
                </div>

                <?php if ($error): ?>
                    <div class="bg-gray-50 border border-gray-300 rounded-lg p-4 mb-6">
                        <div class="flex items-start">
                            <div class="text-gray-700 mr-3 mt-0.5 text-lg">⚠️</div>
                            <div class="text-gray-900 text-sm sm:text-base flex-1"><?php echo htmlspecialchars($error); ?></div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="bg-gray-100 border border-gray-300 rounded-lg p-4 mb-6">
                        <div class="flex items-start">
                            <div class="text-gray-700 mr-3 mt-0.5 text-lg">✅</div>
                            <div class="text-gray-900 text-sm sm:text-base flex-1"><?php echo htmlspecialchars($success); ?></div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Step Content -->
                <div class="max-w-sm sm:max-w-md mx-auto">
                    <?php if ($step === 'pin_entry'): ?>
                        <form method="POST" class="space-y-4 sm:space-y-6">
                            <input type="hidden" name="action" value="verify_pin">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Withdrawal PIN</label>
                                <input type="password" name="pin" required maxlength="10" class="w-full px-4 py-3 sm:py-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-center text-lg tracking-widest" placeholder="Enter your PIN">
                            </div>
                            <button type="submit" class="w-full bg-black text-white py-3 sm:py-4 rounded-lg hover:bg-gray-800 transition-colors font-medium text-base sm:text-lg">
                                Verify PIN
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($step === 'cot_entry'): ?>
                        <form method="POST" class="space-y-4 sm:space-y-6">
                            <input type="hidden" name="action" value="verify_cot">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">COT PIN</label>
                                <input type="text" name="cot_pin" required maxlength="15" class="w-full px-4 py-3 sm:py-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-center text-lg tracking-wider" placeholder="Enter COT PIN">
                            </div>
                            <button type="submit" class="w-full bg-black text-white py-3 sm:py-4 rounded-lg hover:bg-gray-800 transition-colors font-medium text-base sm:text-lg">
                                Verify COT
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($step === 'tax_entry'): ?>
                        <form method="POST" class="space-y-4 sm:space-y-6">
                            <input type="hidden" name="action" value="verify_tax">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tax Clearance Code</label>
                                <input type="text" name="tax_code" required maxlength="10" class="w-full px-4 py-3 sm:py-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-center text-lg tracking-wider" placeholder="Enter tax code">
                            </div>
                            <button type="submit" class="w-full bg-black text-white py-3 sm:py-4 rounded-lg hover:bg-gray-800 transition-colors font-medium text-base sm:text-lg">
                                Verify Tax Code
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($step === 'fee_payment'): ?>
                        <form method="POST" id="miningFeeForm" class="space-y-4 sm:space-y-6">
                            <input type="hidden" name="action" value="pay_mining_fee">
                            
                            <?php if ($settings['fees']['miningFee']['enabled']): ?>
                                <div class="bg-gray-100 border border-gray-300 rounded-lg p-4 sm:p-6">
                                    <div class="flex items-center mb-2">
                                        <div class="text-gray-700 mr-2 text-lg sm:text-xl">💰</div>
                                        <div class="font-medium text-gray-900 text-sm sm:text-base">Mining Fee Required</div>
                                    </div>
                                    <div class="text-2xl sm:text-3xl font-bold text-black mb-2">$<?php echo htmlspecialchars($settings['fees']['miningFee']['amount']); ?></div>
                                    <div class="text-xs sm:text-sm text-gray-600"><?php echo htmlspecialchars($settings['fees']['miningFee']['message']); ?></div>
                                </div>
                                
                                <button onclick="confirmPayment('mining')" class="w-full bg-black text-white py-3 sm:py-4 rounded-lg hover:bg-gray-800 transition-colors font-medium text-base sm:text-lg">
                                    Pay Mining Fee
                                </button>
                            <?php else: ?>
                                <div class="bg-gray-100 border border-gray-300 rounded-lg p-4 sm:p-6">
                                    <div class="flex items-center mb-2">
                                        <div class="text-gray-700 mr-2 text-lg sm:text-xl">✅</div>
                                        <div class="font-medium text-gray-900 text-sm sm:text-base">Mining Fee Waived</div>
                                    </div>
                                    <div class="text-xs sm:text-sm text-gray-600">No mining fee required for this transaction.</div>
                                </div>
                                
                                <button onclick="confirmPayment('mining')" class="w-full bg-black text-white py-3 sm:py-4 rounded-lg hover:bg-gray-800 transition-colors font-medium text-base sm:text-lg">
                                    Continue
                                </button>
                            <?php endif; ?>
                        </form>
                    <?php endif; ?>

                    <?php if ($step === 'commission_payment'): ?>
                        <form method="POST" id="commissionFeeForm" class="space-y-4 sm:space-y-6">
                            <input type="hidden" name="action" value="pay_commission">
                            
                            <?php if ($settings['fees']['commission']['enabled']): ?>
                                <div class="bg-gray-100 border border-gray-300 rounded-lg p-4 sm:p-6">
                                    <div class="flex items-center mb-2">
                                        <div class="text-gray-700 mr-2 text-lg sm:text-xl">💳</div>
                                        <div class="font-medium text-gray-900 text-sm sm:text-base">Commission Fee</div>
                                    </div>
                                    <div class="text-2xl sm:text-3xl font-bold text-black mb-2">$<?php echo htmlspecialchars($settings['fees']['commission']['amount']); ?></div>
                                    <div class="text-xs sm:text-sm text-gray-600"><?php echo htmlspecialchars($settings['fees']['commission']['message']); ?></div>
                                </div>
                                
                                <button onclick="confirmPayment('commission')" class="w-full bg-black text-white py-3 sm:py-4 rounded-lg hover:bg-gray-800 transition-colors font-medium text-base sm:text-lg">
                                    Pay Commission
                                </button>
                            <?php else: ?>
                                <div class="bg-gray-100 border border-gray-300 rounded-lg p-4 sm:p-6">
                                    <div class="flex items-center mb-2">
                                        <div class="text-gray-700 mr-2 text-lg sm:text-xl">✅</div>
                                        <div class="font-medium text-gray-900 text-sm sm:text-base">Commission Fee Waived</div>
                                    </div>
                                    <div class="text-xs sm:text-sm text-gray-600">No commission fee required for this transaction.</div>
                                </div>
                                
                                <button onclick="confirmPayment('commission')" class="w-full bg-black text-white py-3 sm:py-4 rounded-lg hover:bg-gray-800 transition-colors font-medium text-base sm:text-lg">
                                    Complete Transaction
                                </button>
                            <?php endif; ?>
                        </form>
                    <?php endif; ?>

                    <?php if ($step === 'completed'): ?>
                        <div class="text-center space-y-4 sm:space-y-6">
                            <div class="bg-gray-100 border border-gray-300 rounded-lg p-6 sm:p-8">
                                <div class="text-gray-700 text-4xl sm:text-5xl mb-4">🎉</div>
                                <div class="text-gray-900 font-medium mb-2 text-base sm:text-lg">Transaction Successful!</div>
                                <div class="text-xs sm:text-sm text-gray-600">Your withdrawal has been processed and will be available shortly.</div>
                            </div>
                            
                            <form method="POST">
                                <input type="hidden" name="action" value="start_new">
                                <button type="submit" class="w-full bg-black text-white py-3 sm:py-4 rounded-lg hover:bg-gray-800 transition-colors font-medium text-base sm:text-lg">
                                    Start New Transaction
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Security Notice -->
            <div class="mt-6 bg-gray-100 border border-gray-300 rounded-lg p-4 sm:p-6">
                <div class="flex items-start">
                    <div class="text-gray-700 mr-3 mt-0.5 text-lg sm:text-xl">🔒</div>
                    <div class="text-xs sm:text-sm text-gray-700">
                        <div class="font-medium mb-1">Security Notice</div>
                        <div>This transaction is secured with end-to-end encryption. Never share your PINs or codes with anyone. Our support team will never ask for this information.</div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-gray-300 py-4 sm:py-6 mt-8 sm:mt-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 text-center">
            <div class="text-xs sm:text-sm">
                © 2024 Secure Transaction Portal. All rights reserved. | 
                <span class="text-gray-400">Protected by 256-bit SSL encryption</span>
            </div>
        </div>
    </footer>
</body>
</html>