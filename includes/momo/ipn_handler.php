<?php
/**
 * MoMo IPN (Instant Payment Notification) Handler
 *
 * This file receives server-to-server notifications from MoMo when a payment status changes.
 * It validates the signature and updates the order status in the database.
 */

header('Content-Type: application/json; charset=utf-8');
require_once(__DIR__ . '/Momo.php');

$response = array();
$debugger = array();

try {
    if (empty($_POST)) {
        throw new Exception("No POST data received.");
    }

    $momo = new Momo();
    $isValid = $momo->validateIpnSignature($_POST);

    $debugger['postData'] = $_POST;
    $debugger['isValidSignature'] = $isValid;
    $debugger['secretKey'] = MOMO_SECRET_KEY; // For debugging only, remove in production

    if ($isValid) {
        $response['message'] = "Received payment result success (IPN)";
        $resultCode = $_POST["resultCode"];

        // --- !! IMPORTANT: UPDATE YOUR DATABASE HERE !! ---
        //
        if ($resultCode == '0') {
            // Payment successful
            // 1. Get $orderId = $_POST["orderId"];
            // 2. Check if this $orderId has already been processed (to prevent duplicate updates).
            // 3. If not processed, update your database: mark order as PAID.
            //    Example: updateOrderAsPaid($_POST["orderId"], $_POST["transId"], $_POST["extraData"]);
            $response['db_update'] = 'SUCCESS (simulation)'; // Placeholder
        } else {
            // Payment failed
            // 1. Get $orderId = $_POST["orderId"];
            // 2. Update your database: mark order as FAILED.
            //    Example: updateOrderAsFailed($_POST["orderId"], $resultCode, $_POST["message"]);
            $response['db_update'] = 'FAILED (simulation)'; // Placeholder
        }
        //
        // --- !! END OF DATABASE UPDATE SECTION !! ---

    } else {
        $response['message'] = "ERROR! Fail checksum (IPN)";
    }

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = $e->getMessage();
}

// Respond to MoMo
// IMPORTANT: MoMo expects a 200 OK response to acknowledge receipt.
// Do not output any HTML.
http_response_code(200);
$response['debugger'] = $debugger;
echo json_encode($response);

?>