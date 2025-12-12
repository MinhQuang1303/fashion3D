<?php
/**
 * MoMo API Class
 *
 * Encapsulates all logic for interacting with the MoMo API.
 */
class Momo
{
    private $secretKey = '';

    public function __construct()
    {
        // Require the config file to get constants
        require_once(__DIR__ . '/config.php');
        $this->secretKey = MOMO_SECRET_KEY;
    }

    /**
     * Creates a new payment request.
     *
     * @param string $orderId
     * @param string $amount
     * @param string $orderInfo
     * @param string $requestType 'captureWallet' or 'payWithATM'
     * @param string $extraData
     * @return array Decoded JSON response from MoMo
     */
    public function createPaymentRequest($orderId, $amount, $orderInfo, $requestType = "captureWallet", $extraData = "")
    {
        $requestId = time() . "";
        $partnerCode = MOMO_PARTNER_CODE;
        $accessKey = MOMO_ACCESS_KEY;
        $redirectUrl = MOMO_RETURN_URL;
        $ipnUrl = MOMO_IPN_URL;

        // Raw hash string before signing
        $rawHash = "accessKey=" . $accessKey .
            "&amount=" . $amount .
            "&extraData=" . $extraData .
            "&ipnUrl=" . $ipnUrl .
            "&orderId=" . $orderId .
            "&orderInfo=" . $orderInfo .
            "&partnerCode=" . $partnerCode .
            "&redirectUrl=" . $redirectUrl .
            "&requestId=" . $requestId .
            "&requestType=" . $requestType;

        // Create signature
        $signature = hash_hmac("sha256", $rawHash, $this->secretKey);

        $data = array(
            'partnerCode' => $partnerCode,
            'partnerName' => "Test", // Optional
            "storeId" => "MomoTestStore", // Optional
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $redirectUrl,
            'ipnUrl' => $ipnUrl,
            'lang' => 'vi',
            'extraData' => $extraData,
            'requestType' => $requestType,
            'signature' => $signature
        );

        $result = $this->execPostRequest(MOMO_ENDPOINT_CREATE, json_encode($data));
        return json_decode($result, true); // decode json
    }

    /**
     * Queries the status of a transaction.
     *
     * @param string $orderId
     * @return array Decoded JSON response from MoMo
     */
    public function queryTransaction($orderId)
    {
        $requestId = time() . "";
        $partnerCode = MOMO_PARTNER_CODE;
        $accessKey = MOMO_ACCESS_KEY;

        // Raw hash string before signing
        $rawHash = "accessKey=" . $accessKey .
            "&orderId=" . $orderId .
            "&partnerCode=" . $partnerCode .
            "&requestId=" . $requestId;

        // Create signature
        $signature = hash_hmac("sha256", $rawHash, $this->secretKey);

        $data = array(
            'partnerCode' => $partnerCode,
            'requestId' => $requestId,
            'orderId' => $orderId,
            'signature' => $signature,
            'lang' => 'vi'
        );

        $result = $this->execPostRequest(MOMO_ENDPOINT_QUERY, json_encode($data));
        return json_decode($result, true); // decode json
    }

    /**
     * Validates the signature from a MoMo IPN (Instant Payment Notification).
     *
     * @param array $postData The $_POST data from MoMo.
     * @return bool True if the signature is valid, false otherwise.
     */
    public function validateIpnSignature($postData)
    {
        // Ensure all required keys exist
        $requiredKeys = [
            'accessKey', 'amount', 'extraData', 'message', 'orderId',
            'orderInfo', 'orderType', 'partnerCode', 'payType', 'requestId',
            'responseTime', 'resultCode', 'transId', 'signature'
        ];

        foreach ($requiredKeys as $key) {
            if (!isset($postData[$key])) {
                return false;
            }
        }

        $m2signature = $postData["signature"];

        // Raw hash string
        $rawHash = "accessKey=" . $postData["accessKey"] .
            "&amount=" . $postData["amount"] .
            "&extraData=" . $postData["extraData"] .
            "&message=" . $postData["message"] .
            "&orderId=" . $postData["orderId"] .
            "&orderInfo=" . $postData["orderInfo"] .
            "&orderType=" . $postData["orderType"] .
            "&partnerCode=" . $postData["partnerCode"] .
            "&payType=" . $postData["payType"] .
            "&requestId=" . $postData["requestId"] .
            "&responseTime=" . $postData["responseTime"] .
            "&resultCode=" . $postData["resultCode"] .
            "&transId=" . $postData["transId"];

        $partnerSignature = hash_hmac("sha256", $rawHash, $this->secretKey);

        return $m2signature == $partnerSignature;
    }

    /**
     * Validates the signature from the MoMo Return URL.
     *
     * @param array $getData The $_GET data from MoMo.
     * @return bool True if the signature is valid, false otherwise.
     */
    public function validateReturnSignature($getData)
    {
        // Ensure all required keys exist
        $requiredKeys = [
            'accessKey', 'amount', 'extraData', 'message', 'orderId',
            'orderInfo', 'orderType', 'partnerCode', 'payType', 'requestId',
            'responseTime', 'resultCode', 'transId', 'signature'
        ];

        foreach ($requiredKeys as $key) {
            if (!isset($getData[$key])) {
                return false;
            }
        }

        $m2signature = $getData["signature"];

        // Raw hash string
        $rawHash = "accessKey=" . $getData["accessKey"] .
            "&amount=" . $getData["amount"] .
            "&extraData=" . $getData["extraData"] .
            "&message=" . $getData["message"] .
            "&orderId=" . $getData["orderId"] .
            "&orderInfo=" . $getData["orderInfo"] .
            "&orderType=" . $getData["orderType"] .
            "&partnerCode=" . $getData["partnerCode"] .
            "&payType=" . $getData["payType"] .
            "&requestId=" . $getData["requestId"] .
            "&responseTime=" . $getData["responseTime"] .
            "&resultCode=" . $getData["resultCode"] .
            "&transId=" . $getData["transId"];
            
        $partnerSignature = hash_hmac("sha256", $rawHash, $this->secretKey);

        return $m2signature == $partnerSignature;
    }

    /**
     * Validates the signature from a MoMo Query Transaction response.
     *
     * @param array $responseData The JSON decoded response data from MoMo.
     * @return bool True if the signature is valid, false otherwise.
     */
    public function validateQuerySignature($responseData)
    {
        // Ensure all required keys exist
        $requiredKeys = [
            'partnerCode', 'accessKey', 'requestId', 'orderId', 'resultCode',
            'transId', 'amount', 'message', 'localMessage', 'requestType',
            'payType', 'extraData', 'signature'
        ];

        foreach ($requiredKeys as $key) {
            if (!isset($responseData[$key])) {
                // localMessage and extraData can be empty but must exist
                if($key == 'localMessage' || $key == 'extraData') {
                    $responseData[$key] = "";
                } else {
                    return false;
                }
            }
        }

        $m2signature = $responseData["signature"];

        // Raw hash string
        $rawHash = "partnerCode=" . $responseData["partnerCode"] .
            "&accessKey=" . $responseData["accessKey"] .
            "&requestId=" . $responseData["requestId"] .
            "&orderId=" . $responseData["orderId"] .
            "&resultCode=" . $responseData["resultCode"] .
            "&transId=" . $responseData["transId"] .
            "&amount=" . $responseData["amount"] .
            "&message=" . $responseData["message"] .
            "&localMessage=" . $responseData["localMessage"] .
            "&requestType=" . $responseData["requestType"] .
            "&payType=" . $responseData["payType"] .
            "&extraData=" . $responseData["extraData"];

        $partnerSignature = hash_hmac("sha256", $rawHash, $this->secretKey);

        return $m2signature == $partnerSignature;
    }

    /**
     * Executes a POST request to a given URL.
     *
     * @param string $url The URL to send the request to.
     * @param string $data The JSON encoded data string.
     * @return string The response from the server.
     */
    private function execPostRequest($url, $data)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data)
        ));
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        
        $result = curl_exec($ch);
        curl_close($ch);
        
        return $result;
    }
}
?>