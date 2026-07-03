<?php
require_once __DIR__ . '/includes/header.php';
?>

<style>
/* ── About Page Specific ── */
.about-hero {
    height: 360px;
    background: url('https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&q=85&w=1600') center/cover no-repeat;
    position: relative;
    display: flex;
    align-items: center;
}

.about-hero::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(to right, rgba(0,0,0,0.72) 0%, rgba(0,0,0,0.38) 100%);
}

.about-hero-content {
    position: relative;
    z-index: 10;
    padding: 0 40px;
    max-width: 680px;
}

.about-eyebrow {
    display: inline-block;
    font-family: 'Poppins', sans-serif;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--clr-orange);
    background: rgba(224,123,42,0.18);
    border: 1px solid rgba(224,123,42,0.35);
    padding: 4px 14px;
    border-radius: 30px;
    margin-bottom: 16px;
}

.about-hero-content h1 {
    color: #FFFFFF;
    font-size: clamp(1.6rem, 4vw, 2.4rem);
    font-weight: 800;
    line-height: 1.15;
    letter-spacing: -0.025em;
    text-shadow: 0 2px 10px rgba(0,0,0,0.3);
    margin-bottom: 14px;
}

.about-hero-content p {
    color: rgba(255,255,255,0.82);
    font-size: 0.95rem;
    line-height: 1.7;
    max-width: 520px;
}

/* Stats strip */
.about-stats {
    background: #2D6A3F;
    padding: 32px 0;
}

.about-stats-inner {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0;
    flex-wrap: wrap;
}

/* FAQ accordion */
.faq-item {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    margin-bottom: 10px;
    overflow: hidden;
    transition: border-color 0.25s, box-shadow 0.25s;
}

.faq-item[open] {
    border-color: rgba(45,106,63,0.30);
    box-shadow: var(--shadow-sm);
}

.faq-summary {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px 22px;
    cursor: pointer;
    font-family: 'Poppins', sans-serif;
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--heading-color);
    gap: 16px;
    list-style: none;
    user-select: none;
    transition: background 0.2s;
}

.faq-summary::-webkit-details-marker { display: none; }
.faq-summary:hover { background: var(--bg-subtle); }

.faq-chevron {
    color: var(--text-muted);
    font-size: 0.8rem;
    transition: transform 0.28s ease;
    flex-shrink: 0;
}

details[open] .faq-chevron { transform: rotate(180deg); }

.faq-body {
    padding: 4px 22px 20px;
    border-top: 1px solid var(--border);
}

.faq-body p {
    font-size: 0.875rem;
    color: var(--text-secondary);
    line-height: 1.75;
    margin: 0;
}

/* Contact cards */
.contact-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    padding: 24px;
    display: flex;
    align-items: flex-start;
    gap: 16px;
    transition: var(--transition);
    box-shadow: var(--shadow-xs);
}

.contact-card:hover {
    border-color: var(--clr-orange);
    box-shadow: var(--shadow-sm);
    transform: translateY(-2px);
}
</style>

<!-- Hero -->
<section class="about-hero">
    <div class="about-hero-content container">
        <span class="about-eyebrow">TENTANG KAMI</span>
        <h1>Membangun Jembatan Antara Anda dan Kuliner Laut Terbaik</h1>
        <p>Platform informasi seafood terlengkap — menjadi kompas kuliner terpercaya bagi jutaan pecinta seafood di Batam.</p>
    </div>
</section>

<!-- Mission Section -->
<section class="section">
    <div class="container" style="text-align:center;">
        <span style="display:inline-block; font-family:'Poppins',sans-serif; font-size:0.72rem; font-weight:700; letter-spacing:0.12em; text-transform:uppercase; color:var(--clr-orange); margin-bottom:12px;">MISI KAMI</span>
        <h2 style="margin-bottom:14px;">Mengapa Seafood Batam?</h2>
        <p style="color:var(--text-muted); max-width:520px; margin:0 auto 44px; font-size:0.9rem;">Kami berkomitmen menjadi direktori kuliner seafood paling lengkap, akurat, dan bermanfaat bagi masyarakat Batam.</p>

        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:24px;">
            <?php
            $missions = [
                ['icon'=>'fa-star',        'color'=>'#E07B2A', 'bg'=>'rgba(224,123,42,0.10)', 'title'=>'Ulasan Terpercaya',    'desc'=>'Ribuan ulasan nyata dari pengunjung dengan foto dan rating akurat.'],
                ['icon'=>'fa-location-dot','color'=>'#2D6A3F', 'bg'=>'rgba(45,106,63,0.10)',  'title'=>'Pilihan Lokal Terbaik','desc'=>'Restoran terkurasi dari seluruh kecamatan di Kota Batam.'],
                ['icon'=>'fa-brain',       'color'=>'#3B82F6', 'bg'=>'rgba(59,130,246,0.10)', 'title'=>'Pencarian Cerdas',     'desc'=>'Cari berdasarkan nama, lokasi, atau menu dalam hitungan detik.'],
            ];
            foreach($missions as $m):
            ?>
            <div style="background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius-md); padding:30px 22px; box-shadow:var(--shadow-xs);">
                <div style="width:54px; height:54px; background:<?= $m['bg'] ?>; color:<?= $m['color'] ?>; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; margin:0 auto 16px;">
                    <i class="fa-solid <?= $m['icon'] ?>"></i>
                </div>
                <h3 style="font-size:0.95rem; margin-bottom:9px;"><?= $m['title'] ?></h3>
                <p style="font-size:0.85rem; color:var(--text-muted); line-height:1.65; margin:0;"><?= $m['desc'] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Stats Bar -->
<div class="about-stats">
    <div class="container about-stats-inner">
        <?php
        $stats2 = [
            ['num'=>'80+',  'label'=>'Restoran Terdaftar'],
            ['num'=>'12',    'label'=>'Kecamatan Tercakup'],
            ['num'=>'100+', 'label'=>'Ulasan Pengunjung'],
            ['num'=>'2026', 'label'=>'Tahun Berdiri'],
        ];
        foreach($stats2 as $i => $s):
        ?>
        <?php if ($i > 0): ?>
            <div style="width:1px; height:40px; background:rgba(255,255,255,0.22); margin:0 36px; flex-shrink:0;"></div>
        <?php endif; ?>
        <div style="text-align:center; flex-shrink:0;">
            <div style="font-size:clamp(1.8rem,4vw,2.4rem); font-weight:800; color:#FFFFFF; font-family:'Poppins',sans-serif; letter-spacing:-0.03em; line-height:1;"><?= $s['num'] ?></div>
            <div style="font-size:0.75rem; color:rgba(255,255,255,0.70); font-family:'Poppins',sans-serif; margin-top:3px; font-weight:500;"><?= $s['label'] ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- FAQ Section -->
<section class="section section-alt">
    <div class="container" style="max-width:780px;">
        <div style="text-align:center; margin-bottom:44px;">
            <span style="display:inline-block; font-family:'Poppins',sans-serif; font-size:0.72rem; font-weight:700; letter-spacing:0.12em; text-transform:uppercase; color:var(--clr-orange); margin-bottom:12px;">FAQ</span>
            <h2>Pertanyaan yang Sering Diajukan</h2>
            <p style="color:var(--text-muted); margin-top:10px; font-size:0.9rem;">Tidak menemukan jawaban? Hubungi kami melalui kontak di bawah.</p>
        </div>

        <?php
        $faqs = [
            ['q' => 'Bagaimana cara menggunakan Seafood Batam?',
             'a' => 'Kunjungi halaman Restoran untuk melihat semua daftar restoran. Anda dapat menyaring berdasarkan kecamatan atau mencari nama restoran/masakan. Klik kartu restoran untuk melihat detail lengkap.'],
            ['q' => 'Bagaimana cara membuat reservasi meja?',
             'a' => 'Buka halaman detail restoran yang Anda pilih, lalu klik tombol hijau "Reservasi WhatsApp" di sidebar kanan. Anda akan diarahkan ke WhatsApp dengan pesan otomatis ke restoran tersebut.'],
            ['q' => 'Bagaimana cara menyimpan restoran ke daftar favorit?',
             'a' => 'Pastikan Anda sudah masuk ke akun. Di halaman detail restoran, klik tombol "❤ Simpan" di bagian atas. Restoran akan tersimpan di halaman Profil > Restoran Tersimpan.'],
            ['q' => 'Bagaimana cara meninggalkan ulasan untuk restoran?',
             'a' => 'Login ke akun Anda, buka halaman detail restoran, scroll ke bagian Ulasan Pengunjung, isi bintang penilaian, tulis komentar, dan optionally upload foto/video. Klik Kirim Ulasan.'],
            ['q' => 'Apakah informasi harga dan menu selalu diperbarui?',
             'a' => 'Tim kami memperbarui informasi secara berkala. Namun untuk kepastian, kami menyarankan Anda konfirmasi langsung ke restoran karena harga bisa berubah sewaktu-waktu.'],
            ['q' => 'Bagaimana cara mendaftarkan restoran saya di platform ini?',
             'a' => 'Hubungi kami melalui email atau WhatsApp yang tertera di bagian Kontak di bawah. Sertakan nama restoran, alamat, jam buka, dan foto restoran. Tim kami akan memproses dalam 3-5 hari kerja.'],
        ];
        foreach($faqs as $i => $faq):
        ?>
        <details class="faq-item">
            <summary class="faq-summary">
                <span style="display:flex; align-items:center; gap:12px;">
                    <span style="width:26px; height:26px; border-radius:50%; background:var(--clr-green-subtle); color:var(--clr-green); font-size:0.72rem; font-weight:800; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-family:'Poppins',sans-serif;"><?= $i+1 ?></span>
                    <?= htmlspecialchars($faq['q']) ?>
                </span>
                <i class="fa-solid fa-chevron-down faq-chevron"></i>
            </summary>
            <div class="faq-body">
                <p><?= htmlspecialchars($faq['a']) ?></p>
            </div>
        </details>
        <?php endforeach; ?>
    </div>
</section>

<!-- Contact Section -->
<section class="section">
    <div class="container">
        <div style="text-align:center; margin-bottom:40px;">
            <h2>Hubungi Kami</h2>
            <p style="color:var(--text-muted); margin-top:10px; font-size:0.9rem;">Ada pertanyaan lebih lanjut? Kami siap membantu.</p>
        </div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:20px; max-width:700px; margin:20px auto; justify-content:center;">
            <?php
            $contacts = [
                ['icon'=>'fa-envelope',  'color'=>'var(--clr-orange)', 'bg'=>'rgba(249,115,22,0.10)',
                 'label'=>'Email', 'value'=>'info@seafoodbatam.id', 'sub'=>'Kami balas dalam 24 jam',
                 'link'=>'mailto:info@seafoodbatam.id'],
                ['icon'=>'fa-phone',     'color'=>'#2D6A3F', 'bg'=>'rgba(45,106,63,0.10)',
                 'label'=>'Telepon', 'value'=>'+62 812 7063 2112', 'sub'=>'Senin – Jumat, 09:00–17:00',
                 'link'=>'https://wa.me/6281270632112'],
                ['icon'=>'fa-store',     'color'=>'#3B82F6', 'bg'=>'rgba(59,130,246,0.10)',
                 'label'=>'Daftarkan Restoran', 'value'=>'Bergabung Sekarang', 'sub'=>'Daftarkan bisnis kuliner Anda gratis',
                 'link'=>'mailto:daftar@seafoodbatam.id'],
            ];
            foreach($contacts as $c):
            ?>
            <a href="<?= $c['link'] ?>" class="contact-card" style="text-decoration:none;">
                <div style="width:44px; height:44px; background:<?= $c['bg'] ?>; color:<?= $c['color'] ?>; border-radius:var(--radius-sm); display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0;">
                    <i class="fa-solid <?= $c['icon'] ?>"></i>
                </div>
                <div>
                    <div style="font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:var(--text-muted); margin-bottom:4px; font-family:'Poppins',sans-serif;"><?= $c['label'] ?></div>
                    <div style="font-size:0.875rem; font-weight:700; color:<?= $c['color'] ?>; font-family:'Poppins',sans-serif; margin-bottom:3px;"><?= $c['value'] ?></div>
                    <div style="font-size:0.78rem; color:var(--text-muted); font-family:'Poppins',sans-serif;"><?= $c['sub'] ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
