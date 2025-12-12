<?php
namespace MyApp;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class NotificationServer implements MessageComponentInterface {
    protected $clients;
    protected $adminConnections; // Lưu trữ các kết nối của Admin

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->adminConnections = new \SplObjectStorage;
        echo "Notification Server đã khởi động!\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "Kết nối mới! ({$conn->resourceId})\n";

        // Gửi ID kết nối đến client để client biết đây là Admin hay User
        $conn->send(json_encode(['type' => 'conn_id', 'id' => $conn->resourceId]));
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        if (isset($data['type'])) {
            switch ($data['type']) {
                case 'register_admin':
                    // Đăng ký kết nối này là Admin
                    if (!$this->adminConnections->contains($from)) {
                        $this->adminConnections->attach($from);
                        echo "Kết nối {$from->resourceId} đã đăng ký là Admin.\n";
                    }
                    // Bỏ qua việc gửi thông báo nếu Admin mới kết nối
                    break;

                case 'new_order':
                    // Nhận thông báo đơn hàng mới từ trang Thanh toán (Client)
                    echo "Nhận được thông báo đơn hàng mới từ {$from->resourceId}: ID ĐH #{$data['order_id']}\n";

                    // Chuẩn bị thông báo để gửi đến Admin
                    $notification = [
                        'type' => 'alert',
                        'message' => 'Đơn hàng mới: #' . $data['order_id'] . ' từ khách hàng ID ' . $data['user_id'],
                        'order_id' => $data['order_id'],
                        'timestamp' => time()
                    ];

                    // Gửi thông báo đến TẤT CẢ Admin đang online
                    $this->notifyAdmins(json_encode($notification));
                    break;
                
                default:
                    // Xử lý các loại tin nhắn khác nếu cần
                    break;
            }
        }
    }
    
    // Hàm gửi thông báo đến tất cả các kết nối Admin
    protected function notifyAdmins($message) {
        foreach ($this->adminConnections as $adminConn) {
            $adminConn->send($message);
        }
        echo "Đã gửi thông báo tới " . count($this->adminConnections) . " Admin.\n";
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        $this->adminConnections->detach($conn); // Đảm bảo gỡ kết nối Admin nếu có
        echo "Kết nối {$conn->resourceId} đã ngắt\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Đã xảy ra lỗi: {$e->getMessage()}\n";
        $conn->close();
    }
}