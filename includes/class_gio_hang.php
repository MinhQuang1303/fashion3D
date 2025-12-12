<?php
require_once __DIR__ . '/ket_noi_db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

class Cart {
    private $pdo;
    private $voucher = null;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        if (isset($_SESSION['voucher_code'])) {
            $this->voucher = $this->getVoucher($_SESSION['voucher_code']);
        }
    }

    public function add($variant_id, $qty = 1) {
        $vid = (int)$variant_id;
        if ($qty <= 0) return;
        if (isset($_SESSION['cart'][$vid])) $_SESSION['cart'][$vid] += $qty;
        else $_SESSION['cart'][$vid] = $qty;
    }

    public function update($variant_id, $qty) {
        $vid = (int)$variant_id;
        if ($qty <= 0) unset($_SESSION['cart'][$vid]);
        else $_SESSION['cart'][$vid] = $qty;
    }

    public function remove($variant_id) {
        $vid = (int)$variant_id;
        unset($_SESSION['cart'][$vid]);
    }

    public function clear() {
        $_SESSION['cart'] = [];
        $this->removeVoucher();
    }

    // 🧮 Đếm tổng số lượng sản phẩm trong giỏ hàng
    public function countItems() {
        if (empty($_SESSION['cart'])) return 0;

        $total = 0;
        foreach ($_SESSION['cart'] as $qty) {
            $total += (int)$qty;
        }
        return $total;
    }

    public function items() {
        $items = [];
        if (empty($_SESSION['cart'])) return $items;
        
        $ids = array_keys($_SESSION['cart']);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        
        // 🚀 ĐÃ SỬA LỖI: Thay 'p.image' thành 'p.thumbnail_url' để khớp với CSDL
        $stmt = $this->pdo->prepare("SELECT pv.variant_id, p.product_id, p.product_name, pv.color, pv.size, pv.sku, p.base_price, p.discount_percent, p.thumbnail_url 
            FROM Product_Variants pv
            JOIN Products p ON pv.product_id = p.product_id
            WHERE pv.variant_id IN ($placeholders)");
        
        $stmt->execute($ids); // Dòng này sẽ không còn lỗi 1054 nữa
        $rows = $stmt->fetchAll();
        
        foreach ($rows as $r) {
            $vid = $r['variant_id'];
            $qty = $_SESSION['cart'][$vid] ?? 0;
            $price = $r['base_price'] * (1 - ($r['discount_percent'] ?? 0)/100);
            
            $items[] = [
                'variant_id'=>$vid,
                'product_id'=>$r['product_id'],
                'product_name'=>$r['product_name'],
                'color'=>$r['color'],
                'size'=>$r['size'],
                'sku'=>$r['sku'],
                'qty'=>$qty,
                'price'=>$price,
                'subtotal'=>$price * $qty,
                'image_url'=>$r['thumbnail_url'] // <--- Đã sửa: Lấy từ cột 'thumbnail_url'
            ];
        }
        return $items;
    }

    public function totalBeforeDiscount() {
        $total = 0;
        foreach ($this->items() as $it) $total += $it['subtotal'];
        return $total;
    }


    public function discountAmount() {
        if (!$this->voucher) return 0;
        $total = $this->totalBeforeDiscount();
        $discount = $total * ($this->voucher['discount_percent'] / 100);
        if (!empty($this->voucher['max_discount_amount'])) {
            $discount = min($discount, $this->voucher['max_discount_amount']);
        }
        return round($discount,2);
    }

    public function totalAfterDiscount() {
        return max($this->totalBeforeDiscount() - $this->discountAmount(), 0);
    }

    public function applyVoucher($code) {
        $voucher = $this->getVoucher($code);
        if (!$voucher) return ['error'=>'Mã giảm giá không hợp lệ'];
        $_SESSION['voucher_code'] = $voucher['voucher_code'];
        $this->voucher = $voucher;
        return ['success'=>true];
    }

    public function removeVoucher() {
        unset($_SESSION['voucher_code']);
        $this->voucher = null;
    }

    public function currentVoucher() {
        return $this->voucher;
    }

    private function getVoucher($code) {
        $stmt = $this->pdo->prepare("SELECT * FROM Vouchers WHERE voucher_code = ? AND is_active=1 AND valid_from<=NOW() AND valid_to>=NOW() LIMIT 1");
        $stmt->execute([$code]);
        return $stmt->fetch();
    }
}