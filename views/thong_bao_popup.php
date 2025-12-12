<?php
// includes/websocket_sender.php

// Hàm gửi thông báo đơn hàng qua WebSocket
function sendNewOrderNotification($order_id, $order_code, $final_amount, $user_name, $payment_method) {
    $host = '127.0.0.1'; // Cùng IP với nơi chạy websocket_server.php
    $port = 8080;        
    $socket = @fsockopen($host, $port, $errno, $errstr, 5); 

    if ($socket) {
        $message_data = json_encode([
            'type' => 'new_order',
            'order_id' => $order_id, 
            'order_code' => $order_code,
            'user_name' => $user_name,
            'amount' => $final_amount,
            'method' => $payment_method
        ]);

        $len = strlen($message_data);
        $header = chr(0x81);
        
        if ($len <= 125) {
            $header .= chr($len);
        } else if ($len > 125 && $len < 65536) {
            $header .= chr(126) . pack("n", $len);
        } else {
            $header .= chr(127) . pack("N", 0) . pack("N", $len);
        }

        $message_frame = $header . $message_data;
        fwrite($socket, $message_frame);
        fclose($socket);
        return true;
    } else {
        error_log("Lỗi gửi WebSocket từ ket_qua_thanh_toan.php: $errstr ($errno)");
        return false;
    }
}
?>