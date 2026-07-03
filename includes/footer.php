    </main>

    <footer class="site-footer">
        <div class="container footer-content">

            <!-- Brand column -->
            <div>
                <div class="footer-logo">
                    <i class="fa-solid fa-fish-fins"></i>
                    <span>Seafood<span>&nbsp;Batam</span></span>
                </div>
                <p class="footer-desc">
                    Platform informasi kuliner utama untuk restoran seafood terbaik di Kota Batam. 
                    Temukan, simpan, dan nikmati sajian laut terbaik.
                </p>
                <div class="social-icons">
                    <a href="#" aria-label="Instagram" title="Instagram">
                        <i class="fa-brands fa-instagram"></i>
                    </a>
                    <a href="#" aria-label="TikTok" title="TikTok">
                        <i class="fa-brands fa-tiktok"></i>
                    </a>
                    <a href="#" aria-label="Facebook" title="Facebook">
                        <i class="fa-brands fa-facebook-f"></i>
                    </a>
                </div>
            </div>

            <!-- Quick links column -->
            <div>
                <h4 class="footer-heading">Tautan Cepat</h4>
                <div class="footer-links">
                    <a href="<?= BASE_URL ?>/index.php"><i class="fa-solid fa-house" style="width:14px;margin-right:8px;opacity:0.6;"></i>Beranda</a>
                    <a href="<?= BASE_URL ?>/restaurants.php"><i class="fa-solid fa-utensils" style="width:14px;margin-right:8px;opacity:0.6;"></i>Restoran</a>
                    <a href="<?= BASE_URL ?>/about.php"><i class="fa-solid fa-circle-info" style="width:14px;margin-right:8px;opacity:0.6;"></i>Tentang</a>
                    <?php if(is_logged_in()): ?>
                        <a href="<?= BASE_URL ?>/profile.php"><i class="fa-solid fa-user" style="width:14px;margin-right:8px;opacity:0.6;"></i>Profil Saya</a>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>/auth/login.php"><i class="fa-solid fa-right-to-bracket" style="width:14px;margin-right:8px;opacity:0.6;"></i>Masuk</a>
                        <a href="<?= BASE_URL ?>/auth/register.php"><i class="fa-solid fa-user-plus" style="width:14px;margin-right:8px;opacity:0.6;"></i>Daftar</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Kecamatan column -->
            <div>
                <h4 class="footer-heading">Area Populer</h4>
                <div class="footer-links">
                    <a href="<?= BASE_URL ?>/restaurants.php?district=Batam+Kota">Batam Kota</a>
                    <a href="<?= BASE_URL ?>/restaurants.php?district=Lubuk+Baja">Lubuk Baja</a>
                    <a href="<?= BASE_URL ?>/restaurants.php?district=Batu+Ampar">Batu Ampar</a>
                    <a href="<?= BASE_URL ?>/restaurants.php?district=Sekupang">Sekupang</a>
                    <a href="<?= BASE_URL ?>/restaurants.php?district=Nongsa">Nongsa</a>
                    <a href="<?= BASE_URL ?>/restaurants.php?district=Bengkong">Bengkong</a>
                </div>
            </div>

            <!-- Contact column -->
            <div>
                <h4 class="footer-heading">Kontak Kami</h4>
                <div class="footer-links">
                    <a href="mailto:info@seafoodbatam.com" style="display:flex; align-items:center; gap:9px;">
                        <i class="fa-solid fa-envelope" style="color:var(--clr-orange); flex-shrink:0;"></i>
                        info@seafoodbatam.com
                    </a>
                    <a href="https://wa.me/6281270632112" target="_blank" style="display:flex; align-items:center; gap:9px;">
                        <i class="fa-brands fa-whatsapp" style="color:#25D366; flex-shrink:0;"></i>
                        +62 812 7063 2112
                    </a>
                    <span style="display:flex; align-items:flex-start; gap:9px; color:var(--footer-muted); font-size:0.86rem; font-family:'Poppins',sans-serif;">
                        <i class="fa-solid fa-location-dot" style="color:var(--clr-orange); margin-top:2px; flex-shrink:0;"></i>
                        Kota Batam, Kepulauan Riau, Indonesia
                    </span>
                </div>
            </div>

        </div><!-- end footer-content -->

        <div class="footer-bottom">
            <div class="container" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                <p style="margin:0; color:var(--footer-muted); font-size:0.8rem; font-family:'Poppins',sans-serif;">
                    &copy; <?= date('Y'); ?> Panduan Seafood Batam &mdash; Seluruh Hak Cipta Dilindungi.
                </p>
                <p style="margin:0; color:var(--footer-muted); font-size:0.8rem; font-family:'Poppins',sans-serif;">
                    Dibuat dengan <i class="fa-solid fa-heart" style="color:#ef4444;"></i> untuk pecinta seafood Batam
                </p>
            </div>
        </div>
    </footer>

    <button id="back-to-top" class="icon-btn" aria-label="Kembali ke atas">
        <i class="fa-solid fa-arrow-up"></i>
    </button>

    <!-- App JS -->
    <script src="<?= BASE_URL ?>/assets/js/app.js?v=<?= time() ?>"></script>
</body>
</html>
