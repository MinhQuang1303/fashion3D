<?php
// includes/class_thanh_toan.php
class ThanhToanMoMo
{
    private $partnerCode;
    private $accessKey;
    private $secretKey;
    private $endpoint;
    private $redirectUrl;
    private $ipnUrl;

    public function __construct()
    {
        require_once __DIR__ . '/momo_config.php';

        $this->partnerCode = MOMO_PARTNER_CODE;
        $this->accessKey = MOMO_ACCESS_KEY;
        $this->secretKey = MOMO_SECRET_KEY;
        $this->endpoint = MOMO_ENDPOINT;
        $this->redirectUrl = MOMO_RETURN_URL;
        $this->ipnUrl = MOMO_IPN_URL;
    }

    /**
     * Hàm tạo thanh toán MoMo
     * @param string $type  Loại thanh toán ('qr' hoặc 'atm')
     * @param float  $amount Số tiền
     * @param int|string $orderId Mã đơn hàng
     * @return void
     */
    public function thanhToan($type = 'qr', $amount = 10000, $orderId = null)
    {
        if (!$orderId) {
            $orderId = time();
        }

        // Xác định loại thanh toán
        $requestType = ($type === 'atm') ? 'payWithATM' : 'captureWallet';
        $orderInfo = "Thanh toán MoMo ($type) cho đơn hàng #" . $orderId;
        $requestId = time() . "";

        // Chuỗi ký chữ ký
        $rawHash = "accessKey={$this->accessKey}&amount={$amount}&extraData=&ipnUrl={$this->ipnUrl}&orderId={$orderId}&orderInfo={$orderInfo}&partnerCode={$this->partnerCode}&redirectUrl={$this->redirectUrl}&requestId={$requestId}&requestType={$requestType}";
        $signature = hash_hmac("sha256", $rawHash, $this->secretKey);

        $data = [
            'partnerCode' => $this->partnerCode,
            'partnerName' => 'ShopThoiTrang',
            'storeId' => 'ShopThoiTrang2025',
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $this->redirectUrl,
            'ipnUrl' => $this->ipnUrl,
            'lang' => 'vi',
            'extraData' => '',
            'requestType' => $requestType,
            'signature' => $signature
        ];

        // Gửi đến MoMo
        $ch = curl_init($this->endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($data)
        ]);

        $result = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($result, true);

        if (isset($response['payUrl'])) {
            header('Location: ' . $response['payUrl']);
            exit;
        } else {
            echo "<h3>Lỗi khi tạo thanh toán MoMo ($type):</h3><pre>";
            print_r($response);
            echo "</pre>";
        }
    }
}
?>
