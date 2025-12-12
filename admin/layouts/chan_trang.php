<?php
// admin/layouts/chan_trang.php
?>

</div> <!-- Đóng .content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Dark Mode
    const themeToggle = document.getElementById('themeToggle');
    const html = document.documentElement;
    const savedTheme = localStorage.getItem('theme') || 'light';
    html.setAttribute('data-theme', savedTheme);
    themeToggle.querySelector('i').className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';

    themeToggle.addEventListener('click', () => {
        const current = html.getAttribute('data-theme');
        const newTheme = current === 'light' ? 'dark' : 'light';
        html.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        themeToggle.querySelector('i').className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    });

    // Sidebar: Mini mode + Hover expand
    const sidebar = document.getElementById('sidebar');
    const menuToggle = document.getElementById('menuToggle');

    menuToggle.addEventListener('click', (e) => {
        e.stopPropagation();
        sidebar.classList.toggle('mini');
    });

    document.addEventListener('click', (e) => {
        if (window.innerWidth < 992 && !sidebar.classList.contains('mini')) {
            if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                sidebar.classList.add('mini');
            }
        }
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth < 992) {
            sidebar.classList.add('mini');
        }
    });
</script>

</body>
</html>

<?php
if (ob_get_length()) ob_end_flush();
?>