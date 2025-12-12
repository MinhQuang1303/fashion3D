$(document).ready(function() {
    $('.btn-tim').on('click', function() {
        const btn = $(this);
        const productId = btn.data('id');

        $.ajax({
            url: '/api/them_vao_yeu_thich.php',
            type: 'POST',
            data: { product_id: productId },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'added') {
                    btn.find('i').css('color', 'red');
                } else if (res.status === 'removed') {
                    btn.find('i').css('color', '#999');
                } else if (res.status === 'error') {
                    alert(res.message);
                    window.location.href = '/auth/dang_nhap.php';
                }
            },
            error: function() {
                alert('Lỗi kết nối máy chủ!');
            }
        });
    });
});
