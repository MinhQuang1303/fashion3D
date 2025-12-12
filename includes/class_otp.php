<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

class OTP {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Tạo mã OTP mới
     * @param string $email - email người nhận
     * @param string $type - loại OTP ('register', 'forgot_password', 'change_password')
     * @param int $length - độ dài mã OTP
     * @param int $expire_minutes - thời gian hết hạn (phút)
     * @return array|false - thông tin OTP hoặc false nếu lỗi
     */
    public function create($email, $type = 'register', $length = 6, $expire_minutes = 10) {
        $code = str_pad(rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
        $expires_at = date('Y-m-d H:i:s', time() + $expire_minutes * 60);

        // Xóa OTP cũ của cùng loại
        $stmt = $this->pdo->prepare("DELETE FROM Password_OTP WHERE email = ? AND type = ?");
        $stmt->execute([$email, $type]);

        // Lưu OTP mới
        $stmt = $this->pdo->prepare("
            INSERT INTO Password_OTP (email, otp_code, type, expires_at, is_used)
            VALUES (?, ?, ?, ?, 0)
        ");
        $ok = $stmt->execute([$email, $code, $type, $expires_at]);

        if ($ok) {
            $this->sendMail($email, $code, $type);
            return ['code' => $code, 'expires_at' => $expires_at];
        }
        return false;
    }

    /**
     * Xác minh OTP
     * @param string $email
     * @param string $type
     * @param string $code
     * @return bool
     */
    public function verify($email, $type, $code) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM Password_OTP 
            WHERE email = ? AND type = ? AND is_used = 0
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([$email, $type]);
        $otp = $stmt->fetch();

        if (!$otp) return false;
        if ($otp['otp_code'] !== $code) return false;
        if (strtotime($otp['expires_at']) < time()) return false;

        // Đánh dấu đã dùng
        $update = $this->pdo->prepare("UPDATE Password_OTP SET is_used = 1 WHERE otp_id = ?");
        $update->execute([$otp['otp_id']]);
        return true;
    }

    /**
     * Xóa OTP cũ theo email và loại
     */
    public function delete($email, $type) {
        $stmt = $this->pdo->prepare("DELETE FROM Password_OTP WHERE email = ? AND type = ?");
        $stmt->execute([$email, $type]);
    }

    /**
     * Gửi email chứa mã OTP
     */
    private function sendMail($email, $code, $type = 'register') {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'dmq13042003@gmail.com';  // đổi email của bạn
            $mail->Password = 'lvuc sylr hiim ruix';     // đổi app password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('dmq130403@gmail.com', 'Shop Thời Trang');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $subject = 'Mã OTP xác nhận ' . (
                $type === 'register' ? 'đăng ký' :
                ($type === 'forgot_password' ? 'quên mật khẩu' : 'hành động')
            );
            $mail->Subject = $subject;
            $mail->Body = "
                <p>Xin chào,</p>
                <p>Mã OTP của bạn là: <strong>{$code}</strong></p>
                <p>Mã này có hiệu lực trong 10 phút.</p>
                <p>Nếu bạn không yêu cầu, vui lòng bỏ qua email này.</p>
            ";

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('Mail error: ' . $mail->ErrorInfo);
            return false;
        }
    }
}
?>
