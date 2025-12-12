<div class="san-pham">
    <img src="<?= base_url('assets/images/san_pham/' . $san_pham['hinh_anh']) ?>" alt="">
    <h5><?= htmlspecialchars($san_pham['ten_san_pham']) ?></h5>
    <p><?= number_format($san_pham['gia']) ?>đ</p>

    <button class="btn-tim" data-id="<?= $san_pham['san_pham_id'] ?>" style="background:none;border:none;">
        <i class="fa fa-heart" style="color:#999;"></i>
    </button>
</div>
