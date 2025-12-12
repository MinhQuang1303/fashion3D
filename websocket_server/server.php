<?php
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use MyApp\NotificationServer; // Đã đổi tên class

// Load thư viện từ vendor của thư mục gốc
require dirname(__DIR__) . '/vendor/autoload.php';

// Load file Chat.php (nay chứa NotificationServer)
require __DIR__ . '/Chat.php';

// Chạy Server ở port 8080
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new NotificationServer() // Khởi tạo class mới
        )
    ),
    8080
);

echo "WebSocket Notification Server đang chạy tại port 8080...\n";

$server->run();