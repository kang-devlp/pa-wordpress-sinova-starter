<?php
/**
 * Module Name: PA SINOVA — Content Studio (Auto-Beautifier, Custom HTML/CSS & Block Patterns)
 * Description: Mempercantik tampilan postingan & laman secara otomatis (tabel responsif, kartu PDF, daftar isi otomatis, lightbox, share WA, berita terkait), metabox Custom HTML/CSS ringan per post/laman + preset snippet, shortcode [pa_html_box], dan Gutenberg Block Patterns.
 * Version: 2.5.1
 */

defined('ABSPATH') || exit;

if (!function_exists('pa_sinova_cs_defaults')) {
    function pa_sinova_cs_defaults(): array {
        return array(
            'enable_beautifier' => '1',
            'responsive_tables' => '1',
            'auto_pdf_cards'    => '1',
            'auto_toc'          => '1',
            'article_meta_bar'  => '1',
            'share_and_related' => '1',
            'image_lightbox'    => '1',
            'global_custom_css' => '',
            'global_html_top'   => '',
            'global_html_bottom'=> '',
        );
    }
}

if (!function_exists('pa_sinova_cs_get_settings')) {
    function pa_sinova_cs_get_settings(): array {
        $saved = get_option('pa_sinova_content_studio_settings', array());
        if (!is_array($saved)) {
            $saved = array();
        }
        return array_merge(pa_sinova_cs_defaults(), $saved);
    }
}

if (!function_exists('pa_sinova_cs_sanitize_css')) {
    function pa_sinova_cs_sanitize_css(string $css): string {
        $css = str_ireplace(array('</style', '<script', '</script', '<?php', '<?='), '', $css);
        return trim($css);
    }
}

if (!function_exists('pa_sinova_cs_is_eligible_view')) {
    function pa_sinova_cs_is_eligible_view(): bool {
        if (is_admin() || is_feed() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return false;
        }
        if (is_front_page()) {
            return false;
        }
        return is_singular(array('post', 'page'));
    }
}

if (!function_exists('pa_sinova_cs_transform_html')) {
    /**
     * Pure HTML transformer for responsive tables, PDF/Drive link cards, and Auto-TOC.
     */
    function pa_sinova_cs_transform_html(string $html, array $opts = array()): string {
        if (trim($html) === '') {
            return $html;
        }

        // 1. Responsive table wrapper (skip already wrapped tables)
        if (!empty($opts['responsive_tables'])) {
            $html = (string) preg_replace_callback(
                '/<table\b[^>]*>.*?<\/table>/is',
                static function ($m) {
                    $tbl = $m[0];
                    if (strpos($tbl, 'pa-ops-table') !== false || strpos($tbl, 'pa-sinova-table') !== false) {
                        return $tbl;
                    }
                    return '<div class="pa-cs-table-wrap">' . $tbl . '</div>';
                },
                $html
            );
        }

        // 2. Enhance PDF / Google Drive links with document badge class
        if (!empty($opts['auto_pdf_cards'])) {
            $html = (string) preg_replace_callback(
                '/<a\b([^>]*href=["\']([^"\']+)["\'][^>]*)>(.*?)<\/a>/is',
                static function ($m) {
                    $attrs = $m[1];
                    $href  = $m[2];
                    $label = $m[3];
                    if (stripos($label, '<img') !== false) {
                        return $m[0];
                    }
                    if (!preg_match('/(\.pdf(\?.*)?$|drive\.google\.com|docs\.google\.com)/i', $href)) {
                        return $m[0];
                    }
                    if (preg_match('/class=["\']([^"\']*)["\']/i', $attrs)) {
                        $attrs = (string) preg_replace('/class=["\']([^"\']*)["\']/i', 'class="$1 pa-cs-doc-link"', $attrs, 1);
                    } else {
                        $attrs .= ' class="pa-cs-doc-link"';
                    }
                    if (stripos($attrs, 'target=') === false) {
                        $attrs .= ' target="_blank" rel="noopener noreferrer"';
                    }
                    return '<a' . $attrs . '>' . $label . '</a>';
                },
                $html
            );
        }

        // 3. Auto Table of Contents when >= 3 <h2> headings exist
        if (!empty($opts['auto_toc'])) {
            $tocItems = array();
            $secIdx = 0;
            $processed = (string) preg_replace_callback(
                '/<h2\b([^>]*)>(.*?)<\/h2>/is',
                static function ($m) use (&$tocItems, &$secIdx) {
                    $attrs = $m[1];
                    $inner = $m[2];
                    $plain = trim(wp_strip_all_tags($inner));
                    if ($plain === '') {
                        return $m[0];
                    }
                    $secIdx++;
                    if (preg_match('/id=["\']([^"\']+)["\']/i', $attrs, $idM)) {
                        $id = $idM[1];
                    } else {
                        $id = 'pa-sec-' . $secIdx;
                        $attrs .= ' id="' . esc_attr($id) . '"';
                    }
                    $tocItems[] = array('id' => $id, 'label' => $plain);
                    return '<h2' . $attrs . '>' . $inner . '</h2>';
                },
                $html
            );

            if (count($tocItems) >= 3) {
                $lis = array();
                foreach ($tocItems as $it) {
                    $lis[] = '<li><a href="#' . esc_attr($it['id']) . '">' . esc_html($it['label']) . '</a></li>';
                }
                $tocBox = '<details class="pa-cs-toc" open><summary>Daftar Isi</summary><ol>' . implode('', $lis) . '</ol></details>';
                $html = $tocBox . $processed;
            }
        }

        return $html;
    }
}

add_action('wp_head', static function () {
    $cs = pa_sinova_cs_get_settings();
    $isSingular = pa_sinova_cs_is_eligible_view();

    if ($isSingular && ($cs['enable_beautifier'] ?? '1') === '1') {
        ?>
        <style id="pa-sinova-content-studio-css">
            .pa-cs-content{font-size:15.5px;line-height:1.78;color:#1e293b}
            .pa-cs-content img{max-width:100%;height:auto;border-radius:10px;box-shadow:0 6px 20px -10px rgba(2,44,34,.25);cursor:zoom-in;transition:transform .2s ease}
            .pa-cs-content img:hover{transform:translateY(-1px)}
            .pa-cs-meta-bar{display:flex;flex-wrap:wrap;align-items:center;gap:8px 12px;margin:0 0 18px;padding:10px 14px;background:#f4f8f6;border:1px solid #d7e7e0;border-left:4px solid #056e4f;border-radius:8px;font-size:12.5px;color:#334155}
            .pa-cs-cat-badge{display:inline-flex;align-items:center;padding:3px 10px;border-radius:999px;background:#056e4f;color:#fff !important;font-weight:700;font-size:11.5px;text-decoration:none !important}
            .pa-cs-cat-badge.is-pengumuman{background:#b45309}
            .pa-cs-read-time{color:#475569;font-weight:600}
            .pa-cs-toc{margin:0 0 20px;padding:12px 16px;background:#f8fafc;border:1px solid #cbd5e1;border-left:4px solid #C9A227;border-radius:10px}
            .pa-cs-toc summary{font-weight:800;color:#056e4f;cursor:pointer;font-size:14px}
            .pa-cs-toc ol{margin:10px 0 0 18px;padding:0;display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:4px 18px;font-size:13.5px}
            .pa-cs-toc a{color:#0f172a;text-decoration:none}
            .pa-cs-toc a:hover{color:#056e4f;text-decoration:underline}
            .pa-cs-table-wrap{width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch;margin:16px 0;border:1px solid #cbd5e1;border-radius:10px;background:#fff}
            .pa-cs-table-wrap table{width:100%;border-collapse:collapse;margin:0 !important;font-size:13.5px;min-width:480px}
            .pa-cs-table-wrap th,.pa-cs-table-wrap thead td{background:#056e4f !important;color:#fff !important;font-weight:700;text-align:left;padding:10px 12px !important;border:1px solid #04593f}
            .pa-cs-table-wrap td{padding:9px 12px !important;border:1px solid #e2e8f0;vertical-align:top}
            .pa-cs-table-wrap tr:nth-child(even) td{background:#f8fafc}
            .pa-cs-doc-link{display:inline-flex;align-items:center;gap:6px;padding:4px 11px;margin:2px 2px;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:7px;color:#065f46 !important;font-weight:700;font-size:13px;text-decoration:none !important;transition:background .15s ease}
            .pa-cs-doc-link::before{content:"📄";font-size:13px}
            .pa-cs-doc-link:hover{background:#d1fae5}
            .pa-cs-html-box{margin:18px 0;padding:16px 18px;border-radius:10px;border:1px solid #cbd5e1;background:#ffffff}
            .pa-cs-html-box.is-green{border-left:5px solid #056e4f;background:#f4f9f6}
            .pa-cs-html-box.is-gold{border-left:5px solid #C9A227;background:#fffbeb}
            .pa-cs-html-box.is-info{border-left:5px solid #0284c7;background:#f0f9ff}
            .pa-cs-html-box-title{margin:0 0 10px;font-size:15px;font-weight:800;color:#056e4f}
            .pa-cs-share-bar{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px;margin:26px 0 20px;padding:12px 16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px}
            .pa-cs-share-btns{display:flex;flex-wrap:wrap;gap:8px}
            .pa-cs-share-btn{display:inline-flex;align-items:center;gap:6px;padding:6px 13px;border-radius:8px;font-size:12.5px;font-weight:700;border:1px solid #cbd5e1;background:#fff;color:#0f172a !important;text-decoration:none !important;cursor:pointer}
            .pa-cs-share-btn.is-wa{background:#15803d;border-color:#15803d;color:#fff !important}
            .pa-cs-related{margin:24px 0 8px}
            .pa-cs-related h3{margin:0 0 12px;font-size:16px;color:#056e4f;border-bottom:2px solid #e2e8f0;padding-bottom:6px}
            .pa-cs-related-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:14px}
            .pa-cs-rel-card{display:flex;flex-direction:column;padding:12px 14px;background:#fff;border:1px solid #e2e8f0;border-radius:10px;text-decoration:none !important;color:#0f172a !important;transition:border-color .15s,box-shadow .15s}
            .pa-cs-rel-card:hover{border-color:#056e4f;box-shadow:0 6px 18px -12px rgba(5,110,79,.35)}
            .pa-cs-rel-date{font-size:11.5px;color:#64748b;margin-bottom:4px}
            .pa-cs-rel-title{font-size:13.5px;font-weight:700;line-height:1.4;color:#0f172a}
            .pa-cs-lightbox{position:fixed;inset:0;z-index:99999;background:rgba(2,18,14,.86);display:none;align-items:center;justify-content:center;padding:20px;cursor:zoom-out}
            .pa-cs-lightbox.is-open{display:flex}
            .pa-cs-lightbox img{max-width:94vw;max-height:90vh;border-radius:10px;box-shadow:0 20px 50px rgba(0,0,0,.5)}
            [data-pa-theme="dark"] .pa-cs-content{color:#e2e8f0}
            [data-pa-theme="dark"] .pa-cs-meta-bar,[data-pa-theme="dark"] .pa-cs-toc,[data-pa-theme="dark"] .pa-cs-share-bar,[data-pa-theme="dark"] .pa-cs-html-box,[data-pa-theme="dark"] .pa-cs-rel-card{background:#0f1f1a;border-color:#1f3a31;color:#e2e8f0 !important}
            [data-pa-theme="dark"] .pa-cs-toc a,[data-pa-theme="dark"] .pa-cs-rel-title{color:#e2e8f0 !important}
            [data-pa-theme="dark"] .pa-cs-table-wrap{background:#0f1f1a;border-color:#1f3a31}
            [data-pa-theme="dark"] .pa-cs-table-wrap tr:nth-child(even) td{background:#132822}
            [data-pa-theme="dark"] .pa-cs-table-wrap td{border-color:#1f3a31;color:#e2e8f0}
        </style>
        <?php
    }

    $globalCss = pa_sinova_cs_sanitize_css((string) ($cs['global_custom_css'] ?? ''));
    if ($globalCss !== '') {
        echo "\n<style id=\"pa-sinova-global-custom-css\">\n" . $globalCss . "\n</style>\n";
    }

    if ($isSingular) {
        $postId = get_queried_object_id();
        if ($postId > 0) {
            $postCss = pa_sinova_cs_sanitize_css((string) get_post_meta($postId, '_pa_cs_custom_css', true));
            if ($postCss !== '') {
                echo "\n<style id=\"pa-sinova-post-custom-css\">\n" . $postCss . "\n</style>\n";
            }
        }
    }
}, 40);

add_filter('the_content', static function (string $content): string {
    if (!pa_sinova_cs_is_eligible_view() || !in_the_loop() || !is_main_query()) {
        return $content;
    }
    $postId = get_the_ID();
    if (!$postId) {
        return $content;
    }
    // Skip PA SINOVA hub pages that already render their own document app
    if (strpos($content, 'pa-sinova-hub') !== false || strpos($content, '[pa_sinova_beranda') !== false) {
        $htmlTop = trim((string) get_post_meta($postId, '_pa_cs_html_top', true));
        $htmlBot = trim((string) get_post_meta($postId, '_pa_cs_html_bottom', true));
        return ($htmlTop !== '' ? $htmlTop . "\n" : '') . $content . ($htmlBot !== '' ? "\n" . $htmlBot : '');
    }

    $cs = pa_sinova_cs_get_settings();
    $isPost = (get_post_type($postId) === 'post');
    $disableToc = ((string) get_post_meta($postId, '_pa_cs_disable_toc', true) === '1');

    $body = $content;
    if (($cs['enable_beautifier'] ?? '1') === '1') {
        $body = pa_sinova_cs_transform_html($body, array(
            'responsive_tables' => ($cs['responsive_tables'] ?? '1') === '1',
            'auto_pdf_cards'    => ($cs['auto_pdf_cards'] ?? '1') === '1',
            'auto_toc'          => (($cs['auto_toc'] ?? '1') === '1') && !$disableToc,
        ));
    }

    $prefix = '';
    $suffix = '';

    // Article meta bar (categories + estimated reading time)
    if ($isPost && ($cs['enable_beautifier'] ?? '1') === '1' && ($cs['article_meta_bar'] ?? '1') === '1') {
        $cats = get_the_category($postId);
        $badges = array();
        foreach ($cats as $c) {
            $cls = ((string) $c->slug === 'pengumuman') ? 'pa-cs-cat-badge is-pengumuman' : 'pa-cs-cat-badge';
            $badges[] = '<a class="' . esc_attr($cls) . '" href="' . esc_url((string) get_category_link($c->term_id)) . '">' . esc_html($c->name) . '</a>';
        }
        $words = str_word_count(wp_strip_all_tags($content));
        $mins = max(1, (int) ceil($words / 200));
        $prefix .= '<div class="pa-cs-meta-bar">' . implode('', $badges) . '<span class="pa-cs-read-time">⏱ ~' . esc_html((string) $mins) . ' menit baca</span></div>';
    }

    // Global & Per-post Custom HTML Top
    if ($isPost && trim((string) ($cs['global_html_top'] ?? '')) !== '') {
        $prefix .= '<div class="pa-cs-custom-top">' . do_shortcode((string) $cs['global_html_top']) . '</div>';
    }
    $postHtmlTop = trim((string) get_post_meta($postId, '_pa_cs_html_top', true));
    if ($postHtmlTop !== '') {
        $prefix .= '<div class="pa-cs-custom-top">' . do_shortcode($postHtmlTop) . '</div>';
    }

    // Per-post & Global Custom HTML Bottom
    $postHtmlBot = trim((string) get_post_meta($postId, '_pa_cs_html_bottom', true));
    if ($postHtmlBot !== '') {
        $suffix .= '<div class="pa-cs-custom-bottom">' . do_shortcode($postHtmlBot) . '</div>';
    }
    if ($isPost && trim((string) ($cs['global_html_bottom'] ?? '')) !== '') {
        $suffix .= '<div class="pa-cs-custom-bottom">' . do_shortcode((string) $cs['global_html_bottom']) . '</div>';
    }

    // Share bar + Related posts (only on single post)
    if ($isPost && ($cs['enable_beautifier'] ?? '1') === '1' && ($cs['share_and_related'] ?? '1') === '1') {
        $perm = (string) get_permalink($postId);
        $ttl  = wp_strip_all_tags((string) get_the_title($postId));
        $waUrl = 'https://wa.me/?text=' . rawurlencode($ttl . ' — ' . $perm);
        $suffix .= '<div class="pa-cs-share-bar">'
            . '<strong>Bagikan Informasi Ini</strong>'
            . '<div class="pa-cs-share-btns">'
            . '<a class="pa-cs-share-btn is-wa" href="' . esc_url($waUrl) . '" target="_blank" rel="noopener noreferrer">Bagikan ke WhatsApp</a>'
            . '<button type="button" class="pa-cs-share-btn pa-cs-copy-btn" data-url="' . esc_attr($perm) . '">Salin Tautan</button>'
            . '</div></div>';

        $catIds = wp_get_post_categories($postId);
        $relPosts = get_posts(array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => 3,
            'post__not_in'   => array($postId),
            'category__in'   => !empty($catIds) ? $catIds : array(),
            'orderby'        => 'date',
            'order'          => 'DESC',
        ));
        if (!empty($relPosts)) {
            $cards = array();
            foreach ($relPosts as $rp) {
                $cards[] = '<a class="pa-cs-rel-card" href="' . esc_url((string) get_permalink($rp)) . '">'
                    . '<span class="pa-cs-rel-date">' . esc_html(get_the_date('d M Y', $rp)) . '</span>'
                    . '<span class="pa-cs-rel-title">' . esc_html(get_the_title($rp)) . '</span>'
                    . '</a>';
            }
            $suffix .= '<section class="pa-cs-related" aria-label="Berita Terkait"><h3>Berita &amp; Informasi Terkait</h3><div class="pa-cs-related-grid">' . implode('', $cards) . '</div></section>';
        }
    }

    return '<div class="pa-cs-content">' . $prefix . $body . $suffix . '</div>';
}, 25);

add_action('wp_footer', static function () {
    $cs = pa_sinova_cs_get_settings();
    if (!pa_sinova_cs_is_eligible_view() || ($cs['enable_beautifier'] ?? '1') !== '1') {
        return;
    }
    $useLightbox = (($cs['image_lightbox'] ?? '1') === '1');
    ?>
    <?php if ($useLightbox) : ?>
        <div class="pa-cs-lightbox" id="pa-cs-lightbox" role="dialog" aria-modal="true" aria-label="Pratinjau Gambar"><img src="" alt=""></div>
    <?php endif; ?>
    <script id="pa-sinova-content-studio-js">
    (function(){
        document.querySelectorAll('.pa-cs-copy-btn').forEach(function(btn){
            btn.addEventListener('click', function(){
                var u = btn.getAttribute('data-url') || location.href;
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(u).then(function(){
                        var old = btn.textContent;
                        btn.textContent = 'Tautan Disalin ✓';
                        setTimeout(function(){ btn.textContent = old; }, 2000);
                    });
                }
            });
        });
        <?php if ($useLightbox) : ?>
        var lb = document.getElementById('pa-cs-lightbox');
        if (lb) {
            var lbImg = lb.querySelector('img');
            document.querySelectorAll('.pa-cs-content img').forEach(function(img){
                if (img.closest('a[href]')) return;
                img.addEventListener('click', function(){
                    lbImg.src = img.currentSrc || img.src;
                    lbImg.alt = img.alt || '';
                    lb.classList.add('is-open');
                });
            });
            lb.addEventListener('click', function(){ lb.classList.remove('is-open'); });
            document.addEventListener('keydown', function(e){ if (e.key === 'Escape') lb.classList.remove('is-open'); });
        }
        <?php endif; ?>
    })();
    </script>
    <?php
}, 30);

// Shortcode [pa_html_box title="" style="default|green|gold|info"]...[/pa_html_box]
add_shortcode('pa_html_box', static function ($atts, $content = null): string {
    $a = shortcode_atts(array(
        'title' => '',
        'style' => 'green',
        'class' => '',
    ), $atts, 'pa_html_box');
    $style = in_array($a['style'], array('default', 'green', 'gold', 'info'), true) ? $a['style'] : 'green';
    $extra = trim((string) preg_replace('/[^a-zA-Z0-9_\-\s]/', '', (string) $a['class']));
    $titleHtml = trim((string) $a['title']) !== '' ? '<div class="pa-cs-html-box-title">' . esc_html((string) $a['title']) . '</div>' : '';
    return '<div class="pa-cs-html-box is-' . esc_attr($style) . ($extra !== '' ? ' ' . esc_attr($extra) : '') . '">' . $titleHtml . do_shortcode((string) $content) . '</div>';
});

// Gutenberg Block Patterns bawaan PA SINOVA
add_action('init', static function () {
    if (!function_exists('register_block_pattern')) {
        return;
    }
    if (function_exists('register_block_pattern_category')) {
        register_block_pattern_category('pa-sinova', array(
            'label' => 'PA SINOVA (Pengadilan Agama)',
        ));
    }
    register_block_pattern('pa-sinova/berita-kegiatan', array(
        'title'       => 'Berita Kegiatan Standar PA',
        'categories'  => array('pa-sinova'),
        'description' => 'Struktur berita kegiatan satker dengan ringkasan pembuka, foto utama, dan kutipan pimpinan.',
        'content'     => '<!-- wp:paragraph --><p><strong>Bintuhan, [Tanggal]</strong> — Pengadilan Agama menyelenggarakan kegiatan [Nama Kegiatan] yang bertempat di [Lokasi] dan diikuti oleh pimpinan serta seluruh aparatur.</p><!-- /wp:paragraph --><!-- wp:image --><figure class="wp-block-image"><img alt="Dokumentasi Kegiatan"/></figure><!-- /wp:image --><!-- wp:quote --><blockquote class="wp-block-quote"><p>"Kegiatan ini merupakan wujud komitmen kami dalam meningkatkan kualitas pelayanan prima kepada masyarakat pencari keadilan."</p><cite>Ketua Pengadilan Agama</cite></blockquote><!-- /wp:quote -->',
    ));
    register_block_pattern('pa-sinova/berita-mediasi', array(
        'title'       => 'Berita Mediasi Berhasil Damai',
        'categories'  => array('pa-sinova'),
        'description' => 'Template berita keberhasilan mediasi perkara.',
        'content'     => '<!-- wp:paragraph --><p><strong>Bintuhan, [Tanggal]</strong> — Bertempat di Ruang Mediasi, Hakim Mediator [Nama Mediator] kembali berhasil mendamaikan para pihak dalam perkara Nomor [Nomor Perkara].</p><!-- /wp:paragraph --><!-- wp:shortcode -->[pa_html_box title="Ringkasan Mediasi" style="green"]<ul><li><strong>Jenis Perkara:</strong> Cerai Gugat / Harta Bersama</li><li><strong>Hasil:</strong> Berhasil Damai / Sebagian</li><li><strong>Mediator:</strong> [Nama Mediator]</li></ul>[/pa_html_box]<!-- /wp:shortcode -->',
    ));
    register_block_pattern('pa-sinova/pengumuman-resmi', array(
        'title'       => 'Pengumuman Resmi + Lampiran PDF',
        'categories'  => array('pa-sinova'),
        'description' => 'Template pengumuman resmi dengan kotak ringkasan dan tautan dokumen PDF.',
        'content'     => '<!-- wp:shortcode -->[pa_html_box title="Pengumuman Resmi" style="gold"]<p>Diberitahukan kepada seluruh masyarakat pencari keadilan bahwa [isi ringkas pengumuman].</p><p><a href="https://drive.google.com/">Unduh Dokumen Pengumuman Resmi (PDF)</a></p>[/pa_html_box]<!-- /wp:shortcode -->',
    ));
});

// Per-Post & Per-Page Metabox: Custom HTML & CSS + 1-Click Snippet Presets
add_action('add_meta_boxes', static function () {
    if (!current_user_can('edit_posts')) {
        return;
    }
    foreach (array('post', 'page') as $screen) {
        add_meta_box(
            'pa_sinova_content_studio_box',
            'PA SINOVA — Custom HTML & CSS (Ringan, Tanpa Elementor)',
            'pa_sinova_cs_render_metabox',
            $screen,
            'normal',
            'default'
        );
    }
});

if (!function_exists('pa_sinova_cs_render_metabox')) {
    function pa_sinova_cs_render_metabox(WP_Post $post): void {
        wp_nonce_field('pa_cs_metabox_save', 'pa_cs_metabox_nonce');
        $htmlTop = (string) get_post_meta($post->ID, '_pa_cs_html_top', true);
        $htmlBot = (string) get_post_meta($post->ID, '_pa_cs_html_bottom', true);
        $css     = (string) get_post_meta($post->ID, '_pa_cs_custom_css', true);
        $noToc   = (string) get_post_meta($post->ID, '_pa_cs_disable_toc', true);
        $canRaw  = current_user_can('unfiltered_html') || current_user_can('manage_options');
        ?>
        <p style="margin-top:0;color:#475569;font-size:12.5px;">
            Tambahkan HTML custom (seperti widget HTML Elementor) atau CSS khusus yang <strong>hanya dimuat di halaman/postingan ini</strong>.
            <?php if (!$canRaw) : ?><em>(Akun non-Admin akan disaring otomatis dengan <code>wp_kses_post</code>.)</em><?php endif; ?>
        </p>

        <div style="margin-bottom:12px;padding:10px 12px;background:#f4f8f6;border:1px solid #d7e7e0;border-radius:8px;">
            <strong style="display:block;font-size:12px;color:#056e4f;margin-bottom:6px;">Sisipkan Preset Snippet 1-Klik (ke HTML Bawah Konten):</strong>
            <div style="display:flex;flex-wrap:wrap;gap:6px;">
                <button type="button" class="button button-small pa-cs-preset-btn" data-preset="pengumuman">+ Box Pengumuman</button>
                <button type="button" class="button button-small pa-cs-preset-btn" data-preset="accordion">+ Accordion Prosedur</button>
                <button type="button" class="button button-small pa-cs-preset-btn" data-preset="grid2">+ Kartu Info 2 Kolom</button>
                <button type="button" class="button button-small pa-cs-preset-btn" data-preset="pdfbtn">+ Tombol Unduh PDF</button>
                <button type="button" class="button button-small pa-cs-preset-btn" data-preset="embedpdf">+ Embed PDF / Drive</button>
                <button type="button" class="button button-small pa-cs-preset-btn" data-preset="table">+ Tabel SK Responsif</button>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
            <div>
                <label for="pa_cs_html_top" style="display:block;font-weight:700;margin-bottom:4px;">Custom HTML (Atas Konten)</label>
                <textarea id="pa_cs_html_top" name="pa_cs_html_top" rows="4" style="width:100%;font-family:monospace;font-size:12px;" placeholder="Contoh: banner info, alert, atau [pa_html_box]..."><?php echo esc_textarea($htmlTop); ?></textarea>
            </div>
            <div>
                <label for="pa_cs_html_bottom" style="display:block;font-weight:700;margin-bottom:4px;">Custom HTML (Bawah Konten)</label>
                <textarea id="pa_cs_html_bottom" name="pa_cs_html_bottom" rows="4" style="width:100%;font-family:monospace;font-size:12px;" placeholder="Contoh: embed PDF, tombol CTA, accordion, iframe Maps..."><?php echo esc_textarea($htmlBot); ?></textarea>
            </div>
        </div>

        <div style="margin-bottom:10px;">
            <label for="pa_cs_custom_css" style="display:block;font-weight:700;margin-bottom:4px;">Custom CSS Khusus Halaman/Post Ini (tanpa tag <code>&lt;style&gt;</code>)</label>
            <textarea id="pa_cs_custom_css" name="pa_cs_custom_css" rows="4" style="width:100%;font-family:monospace;font-size:12px;" placeholder=".pa-cs-content h2 { color: #056e4f; }"><?php echo esc_textarea($css); ?></textarea>
        </div>

        <label style="display:inline-flex;align-items:center;gap:6px;font-size:12.5px;">
            <input type="checkbox" name="pa_cs_disable_toc" value="1" <?php checked($noToc, '1'); ?>>
            Nonaktifkan Daftar Isi Otomatis (Auto-TOC) khusus di halaman/postingan ini
        </label>

        <script>
        (function(){
            var presets = {
                pengumuman: '[pa_html_box title="Pengumuman Penting" style="gold"]\n<p>Isi pengumuman atau informasi penting di sini.</p>\n[/pa_html_box]',
                accordion: '<details class="pa-cs-html-box is-green">\n  <summary style="font-weight:700;cursor:pointer;color:#056e4f;">Klik untuk melihat rincian prosedur</summary>\n  <ol style="margin-top:10px;"><li>Langkah pertama</li><li>Langkah kedua</li></ol>\n</details>',
                grid2: '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px;margin:14px 0;">\n  <div class="pa-cs-html-box is-green"><strong>Kolom 1</strong><p>Uraian layanan pertama.</p></div>\n  <div class="pa-cs-html-box is-gold"><strong>Kolom 2</strong><p>Uraian layanan kedua.</p></div>\n</div>',
                pdfbtn: '<p><a href="https://drive.google.com/file/d/ID_FILE/view" target="_blank" rel="noopener noreferrer">Unduh Dokumen Resmi (PDF)</a></p>',
                embedpdf: '<div style="margin:14px 0;border:1px solid #cbd5e1;border-radius:10px;overflow:hidden;">\n  <iframe src="https://drive.google.com/file/d/ID_FILE/preview" style="width:100%;height:560px;border:0;" loading="lazy"></iframe>\n</div>',
                table: '<table>\n  <thead><tr><th>No</th><th>Uraian</th><th>Keterangan</th></tr></thead>\n  <tbody><tr><td>1</td><td>Contoh data</td><td>Aktif</td></tr></tbody>\n</table>'
            };
            document.querySelectorAll('.pa-cs-preset-btn').forEach(function(btn){
                btn.addEventListener('click', function(){
                    var ta = document.getElementById('pa_cs_html_bottom');
                    var key = btn.getAttribute('data-preset');
                    if (ta && presets[key]) {
                        ta.value = (ta.value.trim() ? ta.value.trim() + '\n\n' : '') + presets[key];
                        ta.focus();
                    }
                });
            });
        })();
        </script>
        <?php
    }
}

add_action('save_post', static function (int $postId) {
    if (!isset($_POST['pa_cs_metabox_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_POST['pa_cs_metabox_nonce'])), 'pa_cs_metabox_save')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $postId)) {
        return;
    }
    $canRaw = current_user_can('unfiltered_html') || current_user_can('manage_options');
    $rawTop = isset($_POST['pa_cs_html_top']) ? wp_unslash((string) $_POST['pa_cs_html_top']) : '';
    $rawBot = isset($_POST['pa_cs_html_bottom']) ? wp_unslash((string) $_POST['pa_cs_html_bottom']) : '';
    $rawCss = isset($_POST['pa_cs_custom_css']) ? wp_unslash((string) $_POST['pa_cs_custom_css']) : '';

    update_post_meta($postId, '_pa_cs_html_top', $canRaw ? $rawTop : wp_kses_post($rawTop));
    update_post_meta($postId, '_pa_cs_html_bottom', $canRaw ? $rawBot : wp_kses_post($rawBot));
    update_post_meta($postId, '_pa_cs_custom_css', pa_sinova_cs_sanitize_css($rawCss));
    update_post_meta($postId, '_pa_cs_disable_toc', !empty($_POST['pa_cs_disable_toc']) ? '1' : '0');

    if (function_exists('pa_sinova_purge_html_cache_files')) {
        pa_sinova_purge_html_cache_files();
    }
});

// SINOVA Hub Tab Handler & Renderer
if (!function_exists('pa_sinova_handle_content_studio_action')) {
    function pa_sinova_handle_content_studio_action(): string {
        if (!current_user_can('manage_options')) {
            return 'Gagal: Hanya Administrator yang dapat mengubah pengaturan Desain Postingan & HTML.';
        }
        $next = array(
            'enable_beautifier'  => !empty($_POST['cs_enable_beautifier']) ? '1' : '0',
            'responsive_tables'  => !empty($_POST['cs_responsive_tables']) ? '1' : '0',
            'auto_pdf_cards'     => !empty($_POST['cs_auto_pdf_cards']) ? '1' : '0',
            'auto_toc'           => !empty($_POST['cs_auto_toc']) ? '1' : '0',
            'article_meta_bar'   => !empty($_POST['cs_article_meta_bar']) ? '1' : '0',
            'share_and_related'  => !empty($_POST['cs_share_and_related']) ? '1' : '0',
            'image_lightbox'     => !empty($_POST['cs_image_lightbox']) ? '1' : '0',
            'global_custom_css'  => pa_sinova_cs_sanitize_css(wp_unslash((string) ($_POST['cs_global_custom_css'] ?? ''))),
            'global_html_top'    => wp_unslash((string) ($_POST['cs_global_html_top'] ?? '')),
            'global_html_bottom' => wp_unslash((string) ($_POST['cs_global_html_bottom'] ?? '')),
        );
        update_option('pa_sinova_content_studio_settings', $next, false);
        if (function_exists('pa_sinova_purge_html_cache_files')) {
            pa_sinova_purge_html_cache_files();
        }
        if (function_exists('pa_home_page_cache_mark_stale')) {
            pa_home_page_cache_mark_stale();
        }
        return 'Pengaturan Desain Postingan & Custom HTML/CSS berhasil disimpan dan cache dibersihkan.';
    }
}

if (!function_exists('pa_sinova_render_content_studio_tab')) {
    function pa_sinova_render_content_studio_tab(): void {
        $cs = pa_sinova_cs_get_settings();
        ?>
        <form method="post">
            <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
            <input type="hidden" name="pa_sinova_action" value="save_content_studio">

            <div class="pa-adm-cols-2">
                <div style="background:#fff;border:1px solid #cbd5e1;border-top:4px solid #056e4f;border-radius:10px;padding:18px;">
                    <h2 style="margin-top:0;color:#056e4f;font-size:16px;">1. Auto-Beautifier Postingan &amp; Laman</h2>
                    <p style="font-size:12.5px;color:#64748b;margin-top:-4px;">Otomatis mempercantik seluruh postingan lama (termasuk hasil migrasi Joomla) dan postingan baru tanpa perlu edit satu per satu atau memasang Elementor.</p>

                    <div style="display:flex;flex-direction:column;gap:10px;font-size:13px;">
                        <label style="padding:10px 12px;background:#f4f8f6;border:1px solid #d7e7e0;border-radius:8px;font-weight:700;">
                            <input type="checkbox" name="cs_enable_beautifier" value="1" <?php checked($cs['enable_beautifier'], '1'); ?>>
                            Aktifkan Auto-Beautifier PA SINOVA di semua Post &amp; Laman
                        </label>
                        <label><input type="checkbox" name="cs_responsive_tables" value="1" <?php checked($cs['responsive_tables'], '1'); ?>> <strong>Tabel HTML Responsif Otomatis</strong> (header hijau PA, zebra stripe, geser halus di HP)</label>
                        <label><input type="checkbox" name="cs_auto_pdf_cards" value="1" <?php checked($cs['auto_pdf_cards'], '1'); ?>> <strong>Kartu/Tombol Dokumen PDF &amp; Google Drive</strong> otomatis pada tautan lampiran</label>
                        <label><input type="checkbox" name="cs_auto_toc" value="1" <?php checked($cs['auto_toc'], '1'); ?>> <strong>Daftar Isi Otomatis (Auto-TOC)</strong> untuk artikel/laman panjang (&ge; 3 subjudul <code>H2</code>)</label>
                        <label><input type="checkbox" name="cs_article_meta_bar" value="1" <?php checked($cs['article_meta_bar'], '1'); ?>> <strong>Lencana Kategori &amp; Estimasi Waktu Baca</strong> di bagian atas artikel</label>
                        <label><input type="checkbox" name="cs_share_and_related" value="1" <?php checked($cs['share_and_related'], '1'); ?>> <strong>Tombol Bagikan WhatsApp, Salin Tautan &amp; Berita Terkait</strong> di bawah artikel</label>
                        <label><input type="checkbox" name="cs_image_lightbox" value="1" <?php checked($cs['image_lightbox'], '1'); ?>> <strong>Lightbox Gambar</strong> (klik foto berita untuk memperbesar)</label>
                    </div>

                    <hr style="margin:18px 0;">
                    <h3 style="margin:0 0 8px;color:#056e4f;font-size:14px;">Panduan Shortcode &amp; Editor Per Postingan</h3>
                    <ul style="margin:0 0 0 18px;list-style:disc;font-size:12.5px;color:#475569;line-height:1.6;">
                        <li>Di setiap halaman edit <strong>Post</strong> atau <strong>Laman</strong>, tersedia kotak <strong>PA SINOVA — Custom HTML &amp; CSS</strong> lengkap dengan 6 tombol preset snippet 1-klik.</li>
                        <li>Gunakan shortcode <code>[pa_html_box title="Info" style="green|gold|info"]...[/pa_html_box]</code> untuk kotak konten rapi di mana saja.</li>
                        <li>Di editor Gutenberg, klik tombol <strong>+ &rarr; Patterns &rarr; PA SINOVA</strong> untuk memakai template <em>Berita Kegiatan</em>, <em>Berita Mediasi</em>, dan <em>Pengumuman Resmi</em>.</li>
                    </ul>
                </div>

                <div style="background:#fff;border:1px solid #cbd5e1;border-top:4px solid #C9A227;border-radius:10px;padding:18px;">
                    <h2 style="margin-top:0;color:#056e4f;font-size:16px;">2. Global Custom CSS &amp; HTML (Seluruh Situs)</h2>

                    <div style="margin-bottom:14px;">
                        <label for="cs_global_custom_css" style="display:block;font-weight:700;font-size:12.5px;margin-bottom:4px;">Global Custom CSS (tanpa tag <code>&lt;style&gt;</code>)</label>
                        <textarea id="cs_global_custom_css" name="cs_global_custom_css" rows="7" style="width:100%;font-family:monospace;font-size:12px;" placeholder="/* Contoh: ubah ukuran font isi artikel */\n.pa-cs-content { font-size: 16px; }"><?php echo esc_textarea((string) $cs['global_custom_css']); ?></textarea>
                    </div>

                    <div style="margin-bottom:14px;">
                        <label for="cs_global_html_top" style="display:block;font-weight:700;font-size:12.5px;margin-bottom:4px;">Global Custom HTML — Atas Setiap Postingan Berita (Opsional)</label>
                        <textarea id="cs_global_html_top" name="cs_global_html_top" rows="4" style="width:100%;font-family:monospace;font-size:12px;" placeholder="Contoh: banner info layanan PTSP atau disclaimer singkat..."><?php echo esc_textarea((string) $cs['global_html_top']); ?></textarea>
                    </div>

                    <div style="margin-bottom:16px;">
                        <label for="cs_global_html_bottom" style="display:block;font-weight:700;font-size:12.5px;margin-bottom:4px;">Global Custom HTML — Bawah Setiap Postingan Berita (Opsional)</label>
                        <textarea id="cs_global_html_bottom" name="cs_global_html_bottom" rows="4" style="width:100%;font-family:monospace;font-size:12px;" placeholder="Contoh: kotak kontak layanan WhatsApp PTSP di bawah semua berita..."><?php echo esc_textarea((string) $cs['global_html_bottom']); ?></textarea>
                    </div>

                    <button type="submit" class="button button-primary button-large">Simpan Desain Postingan &amp; HTML</button>
                </div>
            </div>
        </form>
        <?php
    }
}
