<?php
/**
 * MoMo Transaction Query Page
 *
 * This page provides a form to query the status of a transaction
 * and displays the raw JSON response from MoMo.
 */

header('Content-type: text/html; charset=utf-8');
require_once(__DIR__ . '/Momo.php');

$response = "";
$responseJson = null; // To hold the decoded JSON
$isValid = false;
$rawHashDebug = '';
$partnerSignatureDebug = '';
$m2signatureDebug = '';
$orderId = $_POST["orderId"] ?? '';

if (!empty($_POST['orderId'])) {
    try {
        $momo = new Momo();
        $responseJson = $momo->queryTransaction($orderId);
        $response = json_encode($responseJson, JSON_PRETTY_PRINT);
        
        // Validate the response signature from MoMo
        $isValid = $momo->validateQuerySignature($responseJson);

        // --- Debugging Info ---
        if($responseJson) {
            $m2signatureDebug = $responseJson['signature'] ?? 'N/A';
            $rawHashDebug = "partnerCode=" . ($responseJson["partnerCode"]??"") .
                "&accessKey=" . ($responseJson["accessKey"]??"") .
                "&requestId=" . ($responseJson["requestId"]??"") .
                "&orderId=" . ($responseJson["orderId"]??"") .
                "&resultCode=" . ($responseJson["resultCode"]??"") . // Note: errorCode in older docs
                "&transId=" . ($responseJson["transId"]??"") .
                "&amount=" . ($responseJson["amount"]??"") .
                "&message=" . ($responseJson["message"]??"") .
                "&localMessage=" . ($responseJson["localMessage"]??"") .
                "&requestType=" . ($responseJson["requestType"]??"") .
                "&payType=" . ($responseJson["payType"]??"") .
                "&extraData=" . ($responseJson["extraData"]??"");
            $partnerSignatureDebug = hash_hmac("sha256", $rawHashDebug, MOMO_SECRET_KEY);
        }

    } catch (Exception $e) {
        $response = json_encode(['error' => $e->getMessage()], JSON_PRETTY_PRINT);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>MoMo Sandbox - Query Transaction</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.4.1/css/bootstrap.min.css"/>
</head>
<body>
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Kiểm tra trạng thái giao dịch thanh toán</h3>
                </div>
                <div class="panel-body">
                    <form class="" method="POST" target="" enctype="application/x-www-form-urlencoded"
                          action="">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="col-form-label">Partner Code</label>
                                    <div class='input-group'>
                                        <input type='text' value="<?php echo MOMO_PARTNER_CODE; ?>" class="form-control" readonly/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-form-label">Mã đơn hàng (orderId)</label>
                                    <div class='input-group'>
                                        <input type='text' name="orderId" value="<?php echo htmlspecialchars($orderId); ?>" class="form-control"/>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p>
                        <div style="margin-top: 1em;">
                            <button type="submit" class="btn btn-primary btn-block">Check Payment</button>
                        </div>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"> Debugger</h3>
                </div>
                <div class="panel-body">
                    <b> Response: </b><pre><?php echo $response; ?></pre></br>
                    
                    <?php if (!empty($_POST['orderId'])): ?>
                        <hr>
                        <b>RawData (from MoMo Response): </b><pre><?php echo htmlspecialchars($rawHashDebug); ?></pre></br>
                        <b>MoMo signature: </b><pre><?php echo htmlspecialchars($m2signatureDebug); ?></pre></br>
                        <b>Partner signature (Calculated): </b><pre><?php echo htmlspecialchars($partnerSignatureDebug); ?></pre></br>
                        <?php
                        if ($isValid) {
                            echo '<div class="alert alert-success"><strong>INFO: </strong>Pass Checksum (Response from MoMo is valid)</div>';
                        } else {
                            echo '<div class="alert alert-danger" role="alert"> <strong>ERROR!:</strong> Fail checksum (Response from MoMo may be tampered)</div>';
                        }
                        ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>