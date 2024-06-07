<?php

require_once __DIR__ . '../../src/PayfastCommon.php';

use Payfast\PayfastCommon\PayfastCommon;

$pfError       = false;
$pfErrMsg      = '';
$pfData        = array();
$pfParamString = '';
$pfHost        = "sandbox.payfast.co.za";
$pfPassphrase  = "";

// Debug mode
$payfastCommon = new PayfastCommon(true);

// Module parameters for pfValidData
$moduleInfo = [
    "pfSoftwareName"       => 'PayFast Software CO',
    "pfSoftwareVer"        => '1.1.0)',
    "pfSoftwareModuleName" => 'PayFast Testing Module',
    "pfModuleVer"          => '1.1.0',
];

$payfastCommon->pflog('Payfast ITN call received');

//// Notify PayFast that information has been received
header('HTTP/1.0 200 OK');
flush();

//// Get data sent by PayFast
$payfastCommon->pflog('Get posted data');

// Posted variables from ITN
$pfData = PayfastCommon::pfGetData();

$payfastCommon->pflog('PayFast Data: ' . json_encode($pfData));

if ($pfData === false) {
    $pfError  = true;
    $pfErrMsg = PayfastCommon::PF_ERR_BAD_ACCESS;
}

/**
 * Validate callback authenticity.
 *
 */

// Verify security signature
if (!$pfError) {
    $payfastCommon->pflog('Verify security signature');

    $passphrase = null;

    // If signature different, log for debugging
    if (!$payfastCommon->pfValidSignature($pfData, $pfParamString, $passphrase)) {
        $pfError  = true;
        $pfErrMsg = PayfastCommon::PF_ERR_INVALID_SIGNATURE;
    }
}

// Verify data received
if (!$pfError) {
    $payfastCommon->pflog('Verify data received');

    $pfValid = $payfastCommon->pfValidData($pfHost, $pfParamString);

    if (!$pfValid) {
        $pfError  = true;
        $pfErrMsg = PayfastCommon::PF_ERR_BAD_ACCESS;
    }
}

//// Check data against internal order & Check order amount
if (!$pfError && (!$payfastCommon->pfAmountsEqual($pfData['amount_gross'], 10.00))) {
    $pfError  = true;
    $pfErrMsg = PayfastCommon::PF_ERR_AMOUNT_MISMATCH;
}

//// Check status and update order
if (!$pfError) {
    $payfastCommon->pflog('Check status and update order');

    $transaction_id = $pfData['pf_payment_id'];

    switch ($pfData['payment_status']) {
        case 'COMPLETE':
            $payfastCommon->pflog('- Complete');
            break;

        case 'FAILED':
            $payfastCommon->pflog('- Failed');
            break;

        case 'PENDING':
            $payfastCommon->pflog('- Pending');
            break;

        default:
            // If unknown status, do nothing (the safest course of action)
            break;
    }
}

//// Create order
if (!$pfError && $pfData['payment_status'] == "COMPLETE") {
    $payfastCommon->pflog("ORDER COMPLETE");
}

