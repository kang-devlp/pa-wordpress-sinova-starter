<?php
/**
 * Module Name: PA SINOVA — Visual Builder (Edit with KangDevlp)
 * Description: Visual Page & Post Builder 2-panel layar penuh ala Elementor yang ringan untuk PA SINOVA: drag-and-drop widget dasar, HTML Custom, Embed PDF/Maps, Tabel Responsif, serta widget dinamis SINOVA Hub, lalu dikompilasi ke HTML bersih tanpa beban JS di frontend.
 * Version: 2.5.2
 */

defined('ABSPATH') || exit;

if (!function_exists('pa_kd_builder_url')) {
    function pa_kd_builder_url(int $postId): string {
        return admin_url('admin.php?page=pa-kd-builder&post=' . $postId);
    }
}

if (!function_exists('pa_kd_builder_sinova_widgets')) {
    function pa_kd_builder_sinova_widgets(): array {
        return array(
            'pa_sinova_latest_posts'    => 'Berita Terkini Satker',
            'pa_official_announcements' => 'Pengumuman Resmi (Satker + MA/Badilag)',
            'pa_jadwal_sidang'          => 'Jadwal Sidang',
            'pa_panggilan_ghaib'        => 'Daftar Panggilan Ghaib',
            'pa_statistik_perkara'      => 'Statistik Perkara',
            'pa_pegawai_grid'           => 'Profil Pejabat & Pegawai',
            'pa_direktori_putusan'      => 'Direktori Putusan Pilihan',
            'pa_yurisdiksi_grid'        => 'Wilayah Yurisdiksi',
            'pa_inovasi_satker'         => 'Inovasi Layanan Satker',
            'pa_penelusuran_perkara'    => 'Penelusuran Perkara SIPP',
            'pa_video_gallery'          => 'Galeri Video',
            'pa_ucapan_board'           => 'Papan Ucapan & Belasungkawa',
            'pa_visitor_stats'          => 'Statistik Kunjungan Website',
        );
    }
}

if (!function_exists('pa_kd_builder_compile_block')) {
    function pa_kd_builder_compile_block(array $b, bool $allowRawHtml = true): string {
        $type  = sanitize_key((string) ($b['type'] ?? 'text'));
        $data  = isset($b['data']) && is_array($b['data']) ? $b['data'] : array();
        $style = isset($b['style']) && is_array($b['style']) ? $b['style'] : array();

        $cssParts = array();
        $bg = trim((string) ($style['bg'] ?? ''));
        if ($bg !== '' && preg_match('/^#[0-9a-fA-F]{3,8}$/', $bg)) {
            $cssParts[] = 'background:' . $bg;
        }
        $color = trim((string) ($style['color'] ?? ''));
        if ($color !== '' && preg_match('/^#[0-9a-fA-F]{3,8}$/', $color)) {
            $cssParts[] = 'color:' . $color;
        }
        $pad = (int) ($style['padding'] ?? 0);
        if ($pad > 0 && $pad <= 120) {
            $cssParts[] = 'padding:' . $pad . 'px';
        }
        $mb = isset($style['marginBottom']) && $style['marginBottom'] !== '' ? (int) $style['marginBottom'] : 18;
        if ($mb >= 0 && $mb <= 160) {
            $cssParts[] = 'margin-bottom:' . $mb . 'px';
        }
        $rad = (int) ($style['radius'] ?? 0);
        if ($rad > 0 && $rad <= 48) {
            $cssParts[] = 'border-radius:' . $rad . 'px';
        }
        $align = (string) ($style['align'] ?? '');
        if (in_array($align, array('left', 'center', 'right', 'justify'), true)) {
            $cssParts[] = 'text-align:' . $align;
        }

        $extraClass = trim((string) preg_replace('/[^a-zA-Z0-9_\-\s]/', '', (string) ($style['cssClass'] ?? '')));
        $wrapClass  = 'pa-kd-block pa-kd-type-' . $type . ($extraClass !== '' ? ' ' . $extraClass : '');
        $wrapStyle  = !empty($cssParts) ? ' style="' . esc_attr(implode(';', $cssParts)) . '"' : '';

        $cleanHtml = static function (string $raw) use ($allowRawHtml): string {
            return $allowRawHtml ? $raw : wp_kses_post($raw);
        };

        $inner = '';
        switch ($type) {
            case 'heading':
                $tag = in_array((string) ($data['tag'] ?? 'h2'), array('h1', 'h2', 'h3', 'h4'), true) ? (string) $data['tag'] : 'h2';
                $txt = (string) ($data['text'] ?? 'Judul Bagian');
                $inner = '<' . $tag . ' class="pa-kd-heading">' . esc_html($txt) . '</' . $tag . '>';
                break;

            case 'text':
                $raw = (string) ($data['content'] ?? '');
                $inner = '<div class="pa-kd-text">' . wpautop($cleanHtml($raw)) . '</div>';
                break;

            case 'html':
                $raw = (string) ($data['html'] ?? '');
                $inner = '<div class="pa-kd-raw-html">' . $cleanHtml($raw) . '</div>';
                break;

            case 'image':
                $url = esc_url((string) ($data['url'] ?? ''));
                $alt = esc_attr((string) ($data['alt'] ?? ''));
                $cap = trim((string) ($data['caption'] ?? ''));
                $w   = max(10, min(100, (int) ($data['width'] ?? 100)));
                if ($url !== '') {
                    $inner = '<figure class="pa-kd-figure"><img src="' . $url . '" alt="' . $alt . '" style="width:' . $w . '%;height:auto;" loading="lazy" decoding="async">'
                        . ($cap !== '' ? '<figcaption>' . esc_html($cap) . '</figcaption>' : '')
                        . '</figure>';
                }
                break;

            case 'button':
                $label = (string) ($data['label'] ?? 'Selengkapnya');
                $url   = esc_url((string) ($data['url'] ?? '#'));
                $btnSt = in_array((string) ($data['btnStyle'] ?? 'green'), array('green', 'gold', 'outline', 'dark'), true) ? (string) $data['btnStyle'] : 'green';
                $newTab = !empty($data['newTab']) ? ' target="_blank" rel="noopener noreferrer"' : '';
                $inner = '<a class="pa-kd-btn is-' . esc_attr($btnSt) . '" href="' . $url . '"' . $newTab . '>' . esc_html($label) . '</a>';
                break;

            case 'card':
                $title  = (string) ($data['title'] ?? 'Informasi Penting');
                $icon   = (string) ($data['icon'] ?? '📌');
                $body   = (string) ($data['body'] ?? '');
                $accent = in_array((string) ($data['accent'] ?? 'green'), array('green', 'gold', 'blue', 'red'), true) ? (string) $data['accent'] : 'green';
                $inner  = '<div class="pa-kd-card is-' . esc_attr($accent) . '">'
                    . '<div class="pa-kd-card-head"><span>' . esc_html($icon) . '</span><strong>' . esc_html($title) . '</strong></div>'
                    . '<div class="pa-kd-card-body">' . wpautop($cleanHtml($body)) . '</div>'
                    . '</div>';
                break;

            case 'columns':
                $cols = isset($data['cols']) && is_array($data['cols']) ? $data['cols'] : array();
                $cnt  = max(2, min(3, count($cols) ?: 2));
                $colHtml = array();
                for ($i = 0; $i < $cnt; $i++) {
                    $cItem = isset($cols[$i]) && is_array($cols[$i]) ? $cols[$i] : array();
                    $cTitle = trim((string) ($cItem['title'] ?? ''));
                    $cBody  = (string) ($cItem['body'] ?? '');
                    $colHtml[] = '<div class="pa-kd-col">'
                        . ($cTitle !== '' ? '<h3>' . esc_html($cTitle) . '</h3>' : '')
                        . '<div class="pa-kd-col-body">' . $cleanHtml($cBody) . '</div>'
                        . '</div>';
                }
                $inner = '<div class="pa-kd-grid cols-' . $cnt . '">' . implode('', $colHtml) . '</div>';
                break;

            case 'accordion':
                $title = (string) ($data['title'] ?? 'Klik untuk membuka detail');
                $body  = (string) ($data['body'] ?? '');
                $open  = !empty($data['open']) ? ' open' : '';
                $inner = '<details class="pa-kd-accordion"' . $open . '><summary>' . esc_html($title) . '</summary><div class="pa-kd-acc-body">' . wpautop($cleanHtml($body)) . '</div></details>';
                break;

            case 'pdf':
                $rawUrl = trim((string) ($data['url'] ?? ''));
                $title  = trim((string) ($data['title'] ?? 'Pratinjau Dokumen PDF'));
                $height = max(280, min(1000, (int) ($data['height'] ?? 540)));
                $embedUrl = $rawUrl;
                if (preg_match('#drive\.google\.com/file/d/([^/]+)#i', $rawUrl, $m)) {
                    $embedUrl = 'https://drive.google.com/file/d/' . $m[1] . '/preview';
                }
                if ($embedUrl !== '') {
                    $inner = '<div class="pa-kd-pdf-box">'
                        . '<div class="pa-kd-pdf-bar"><strong>📄 ' . esc_html($title) . '</strong>'
                        . '<a href="' . esc_url($rawUrl) . '" target="_blank" rel="noopener noreferrer">Buka / Unduh ↗</a></div>'
                        . '<iframe src="' . esc_url($embedUrl) . '" style="width:100%;height:' . $height . 'px;border:0;display:block;" loading="lazy"></iframe>'
                        . '</div>';
                }
                break;

            case 'maps':
                $query  = trim((string) ($data['query'] ?? ''));
                $height = max(220, min(700, (int) ($data['height'] ?? 380)));
                if ($query !== '') {
                    $src = (stripos($query, 'http') === 0)
                        ? $query
                        : ('https://maps.google.com/maps?q=' . rawurlencode($query) . '&output=embed');
                    $inner = '<div class="pa-kd-maps-box"><iframe src="' . esc_url($src) . '" style="width:100%;height:' . $height . 'px;border:0;display:block;" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe></div>';
                }
                break;

            case 'table':
                $rawRows = trim((string) ($data['rows'] ?? ''));
                if ($rawRows !== '') {
                    $lines = preg_split('/\r?\n/', $rawRows);
                    $thead = '';
                    $tbody = '';
                    foreach ($lines as $idx => $line) {
                        $line = trim((string) $line);
                        if ($line === '') {
                            continue;
                        }
                        $cells = array_map('trim', explode('|', $line));
                        if ($idx === 0) {
                            $ths = array_map(static function ($c) { return '<th>' . esc_html($c) . '</th>'; }, $cells);
                            $thead = '<thead><tr>' . implode('', $ths) . '</tr></thead>';
                        } else {
                            $tds = array_map(static function ($c) { return '<td>' . esc_html($c) . '</td>'; }, $cells);
                            $tbody .= '<tr>' . implode('', $tds) . '</tr>';
                        }
                    }
                    $inner = '<div class="pa-cs-table-wrap"><table class="pa-kd-table">' . $thead . '<tbody>' . $tbody . '</tbody></table></div>';
                }
                break;

            case 'spacer':
                $h = max(8, min(160, (int) ($data['height'] ?? 24)));
                $hasLine = !empty($data['divider']);
                $inner = $hasLine
                    ? '<hr class="pa-kd-divider" style="margin:' . (int) ($h / 2) . 'px 0;">'
                    : '<div class="pa-kd-spacer" style="height:' . $h . 'px;" aria-hidden="true"></div>';
                break;

            case 'sinova_docs':
                $slug = sanitize_key((string) ($data['slug'] ?? 'lra'));
                $inner = '[pa_sinova_docs slug="' . esc_attr($slug) . '"]';
                break;

            case 'sinova_widget':
                $allowed = array_keys(pa_kd_builder_sinova_widgets());
                $sc = sanitize_key((string) ($data['shortcode'] ?? 'pa_sinova_latest_posts'));
                if (in_array($sc, $allowed, true)) {
                    $inner = '[' . $sc . ']';
                }
                break;
        }

        if ($inner === '') {
            return '';
        }
        return '<div class="' . esc_attr($wrapClass) . '"' . $wrapStyle . '>' . $inner . '</div>';
    }
}

if (!function_exists('pa_kd_builder_compile_html')) {
    function pa_kd_builder_compile_html(array $blocks, bool $allowRawHtml = true): string {
        $out = array();
        foreach ($blocks as $blk) {
            if (!is_array($blk)) {
                continue;
            }
            $compiled = pa_kd_builder_compile_block($blk, $allowRawHtml);
            if ($compiled !== '') {
                $out[] = $compiled;
            }
        }
        if (empty($out)) {
            return '';
        }
        return '<div class="pa-kd-canvas-out">' . "\n" . implode("\n", $out) . "\n" . '</div>';
    }
}

// Frontend lightweight CSS for KangDevlp Builder compiled HTML (zero JS!)
add_action('wp_head', static function () {
    if (is_admin() || !is_singular(array('post', 'page'))) {
        return;
    }
    $postId = get_queried_object_id();
    if (!$postId) {
        return;
    }
    $p = get_post($postId);
    if (!$p || (strpos((string) $p->post_content, 'pa-kd-') === false && !get_post_meta($postId, '_pa_kd_builder_json', true))) {
        return;
    }
    ?>
    <style id="pa-kd-builder-front-css">
        .pa-kd-canvas-out{display:block;width:100%}
        .pa-kd-heading{margin:0 0 8px;line-height:1.3;color:#056e4f}
        .pa-kd-text p:last-child{margin-bottom:0}
        .pa-kd-figure{margin:0}
        .pa-kd-figure img{border-radius:10px;display:inline-block}
        .pa-kd-figure figcaption{font-size:12.5px;color:#64748b;margin-top:6px}
        .pa-kd-btn{display:inline-flex;align-items:center;gap:8px;padding:10px 20px;border-radius:8px;font-weight:700;font-size:14px;text-decoration:none !important;transition:transform .15s ease,opacity .15s ease}
        .pa-kd-btn:hover{transform:translateY(-1px);opacity:.93}
        .pa-kd-btn.is-green{background:#056e4f;color:#fff !important}
        .pa-kd-btn.is-gold{background:#C9A227;color:#022c22 !important}
        .pa-kd-btn.is-dark{background:#0f172a;color:#fff !important}
        .pa-kd-btn.is-outline{background:transparent;border:2px solid #056e4f;color:#056e4f !important}
        .pa-kd-card{padding:16px 18px;border-radius:10px;border:1px solid #cbd5e1;background:#f8fafc}
        .pa-kd-card.is-green{border-left:5px solid #056e4f;background:#f2f9f6}
        .pa-kd-card.is-gold{border-left:5px solid #C9A227;background:#fffbeb}
        .pa-kd-card.is-blue{border-left:5px solid #0284c7;background:#f0f9ff}
        .pa-kd-card.is-red{border-left:5px solid #dc2626;background:#fef2f2}
        .pa-kd-card-head{display:flex;align-items:center;gap:8px;font-size:15.5px;color:#0f172a;margin-bottom:6px}
        .pa-kd-grid{display:grid;gap:16px}
        .pa-kd-grid.cols-2{grid-template-columns:repeat(2,minmax(0,1fr))}
        .pa-kd-grid.cols-3{grid-template-columns:repeat(3,minmax(0,1fr))}
        @media(max-width:768px){.pa-kd-grid.cols-2,.pa-kd-grid.cols-3{grid-template-columns:1fr}}
        .pa-kd-col{padding:16px;background:#fff;border:1px solid #e2e8f0;border-radius:10px}
        .pa-kd-col h3{margin:0 0 8px;font-size:15.5px;color:#056e4f}
        .pa-kd-accordion{border:1px solid #cbd5e1;border-left:4px solid #056e4f;border-radius:10px;background:#fff;padding:12px 16px}
        .pa-kd-accordion summary{font-weight:700;cursor:pointer;color:#056e4f;font-size:15px}
        .pa-kd-acc-body{margin-top:10px;padding-top:10px;border-top:1px solid #e2e8f0}
        .pa-kd-pdf-box,.pa-kd-maps-box{border:1px solid #cbd5e1;border-radius:10px;overflow:hidden;background:#fff}
        .pa-kd-pdf-bar{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 14px;background:#033022;color:#fff;font-size:13px}
        .pa-kd-pdf-bar a{color:#fde68a !important;font-weight:700;text-decoration:none}
        .pa-kd-divider{border:0;border-top:1px solid #cbd5e1}
        [data-pa-theme="dark"] .pa-kd-col,[data-pa-theme="dark"] .pa-kd-accordion,[data-pa-theme="dark"] .pa-kd-card{background:#0f1f1a;border-color:#1f3a31;color:#e2e8f0}
        [data-pa-theme="dark"] .pa-kd-card-head,[data-pa-theme="dark"] .pa-kd-heading{color:#a7f3d0}
    </style>
    <?php
}, 42);

// 1. Row action "Edit with KangDevlp" on Posts & Pages list
foreach (array('post_row_actions', 'page_row_actions') as $hook) {
    add_filter($hook, static function (array $actions, WP_Post $post): array {
        if (!current_user_can('edit_post', $post->ID)) {
            return $actions;
        }
        $actions['pa_kd_builder'] = '<a href="' . esc_url(pa_kd_builder_url((int) $post->ID)) . '" style="color:#056e4f;font-weight:700;">Edit with KangDevlp</a>';
        return $actions;
    }, 15, 2);
}

// 2. Button in Classic Editor & Gutenberg Metabox
add_action('edit_form_after_title', static function (WP_Post $post) {
    if (!in_array($post->post_type, array('post', 'page'), true) || !current_user_can('edit_post', $post->ID)) {
        return;
    }
    echo '<div style="margin:10px 0 6px;"><a href="' . esc_url(pa_kd_builder_url((int) $post->ID)) . '" class="button button-primary" style="background:#056e4f;border-color:#04593f;font-weight:700;padding:4px 14px;height:auto;">✨ Edit with KangDevlp (Visual Builder)</a></div>';
});

add_action('add_meta_boxes', static function () {
    foreach (array('post', 'page') as $sc) {
        add_meta_box(
            'pa_kd_builder_launch_box',
            'KangDevlp Visual Builder',
            static function (WP_Post $post) {
                echo '<p style="margin:0 0 10px;font-size:12.5px;color:#475569;">Susun tata letak, kolom, tombol, HTML custom, PDF, dan widget SINOVA Hub secara visual ala Elementor — hasil tetap HTML ringan.</p>';
                echo '<a href="' . esc_url(pa_kd_builder_url((int) $post->ID)) . '" class="button button-primary" style="width:100%;text-align:center;background:#056e4f;border-color:#04593f;font-weight:700;">✨ Edit with KangDevlp</a>';
            },
            $sc,
            'side',
            'high'
        );
    }
});

// 3. Frontend Admin Bar shortcut
add_action('admin_bar_menu', static function ($wp_admin_bar) {
    if (is_admin() || !is_singular(array('post', 'page'))) {
        return;
    }
    $postId = get_queried_object_id();
    if (!$postId || !current_user_can('edit_post', $postId)) {
        return;
    }
    $wp_admin_bar->add_node(array(
        'id'    => 'pa-kd-builder-bar',
        'title' => '✨ Edit with KangDevlp',
        'href'  => pa_kd_builder_url($postId),
    ));
}, 85);

// 4. Register hidden full-screen admin page for KangDevlp Visual Builder
add_action('admin_menu', static function () {
    add_submenu_page(
        null,
        'Edit with KangDevlp — PA SINOVA Visual Builder',
        'Edit with KangDevlp',
        'edit_posts',
        'pa-kd-builder',
        'pa_kd_builder_render_page'
    );
});

add_action('admin_enqueue_scripts', static function () {
    if (isset($_GET['page']) && $_GET['page'] === 'pa-kd-builder') {
        wp_enqueue_media();
    }
});

// 5. AJAX Save Handler
add_action('wp_ajax_pa_kd_builder_save', static function () {
    check_ajax_referer('pa_kd_builder_nonce', 'nonce');
    $postId = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
    if ($postId <= 0 || !current_user_can('edit_post', $postId)) {
        wp_send_json_error(array('message' => 'Akses ditolak atau ID tidak valid.'), 403);
    }

    $rawJson = isset($_POST['blocks_json']) ? wp_unslash((string) $_POST['blocks_json']) : '[]';
    $blocks  = json_decode($rawJson, true);
    if (!is_array($blocks)) {
        wp_send_json_error(array('message' => 'Format data blok tidak valid.'), 400);
    }

    $allowRaw = current_user_can('unfiltered_html') || current_user_can('manage_options');
    $compiled = pa_kd_builder_compile_html($blocks, $allowRaw);

    $updArgs = array(
        'ID'           => $postId,
        'post_content' => $compiled,
    );
    if (isset($_POST['post_title'])) {
        $newTitle = sanitize_text_field(wp_unslash((string) $_POST['post_title']));
        if ($newTitle !== '') {
            $updArgs['post_title'] = $newTitle;
        }
    }
    $res = wp_update_post($updArgs, true);
    if (is_wp_error($res)) {
        wp_send_json_error(array('message' => $res->get_error_message()), 500);
    }

    update_post_meta($postId, '_pa_kd_builder_json', wp_json_encode($blocks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    if (isset($_POST['page_css'])) {
        $css = wp_unslash((string) $_POST['page_css']);
        $cleanCss = function_exists('pa_sinova_cs_sanitize_css') ? pa_sinova_cs_sanitize_css($css) : trim(str_ireplace(array('</style', '<script'), '', $css));
        update_post_meta($postId, '_pa_cs_custom_css', $cleanCss);
    }

    if (function_exists('pa_sinova_purge_html_cache_files')) {
        pa_sinova_purge_html_cache_files();
    }
    if (function_exists('pa_home_page_cache_mark_stale')) {
        pa_home_page_cache_mark_stale();
    }

    wp_send_json_success(array(
        'message'   => 'Berhasil disimpan & dikompilasi ke HTML bersih!',
        'permalink' => get_permalink($postId),
        'bytes'     => strlen($compiled),
    ));
});

if (!function_exists('pa_kd_builder_render_page')) {
    function pa_kd_builder_render_page(): void {
        $postId = isset($_GET['post']) ? (int) $_GET['post'] : 0;
        $post   = $postId > 0 ? get_post($postId) : null;
        if (!$post || !current_user_can('edit_post', $post->ID)) {
            echo '<div class="wrap"><h1>KangDevlp Visual Builder</h1><p>Post/Laman tidak ditemukan atau Anda tidak memiliki izin edit.</p></div>';
            return;
        }

        $savedJson = (string) get_post_meta($post->ID, '_pa_kd_builder_json', true);
        $blocks    = $savedJson !== '' ? json_decode($savedJson, true) : null;
        if (!is_array($blocks)) {
            $existing = trim((string) $post->post_content);
            if ($existing !== '') {
                $blocks = array(
                    array(
                        'id'    => 'blk_init_1',
                        'type'  => 'html',
                        'data'  => array('html' => $existing),
                        'style' => array('marginBottom' => 18),
                    ),
                );
            } else {
                $blocks = array(
                    array(
                        'id'    => 'blk_init_1',
                        'type'  => 'heading',
                        'data'  => array('tag' => 'h2', 'text' => (string) $post->post_title),
                        'style' => array('marginBottom' => 12),
                    ),
                    array(
                        'id'    => 'blk_init_2',
                        'type'  => 'text',
                        'data'  => array('content' => 'Mulai menulis konten atau tambahkan widget dari panel kiri.'),
                        'style' => array('marginBottom' => 18),
                    ),
                );
            }
        }

        $pageCss = (string) get_post_meta($post->ID, '_pa_cs_custom_css', true);
        $catalog = function_exists('pa_sinova_module_catalog') ? pa_sinova_module_catalog() : array();
        $docOptions = array();
        foreach ($catalog as $slug => $m) {
            $docOptions[] = array('slug' => $slug, 'label' => ($m['title'] ?? $slug) . ' (' . $slug . ')');
        }
        $sinovaWidgets = array();
        foreach (pa_kd_builder_sinova_widgets() as $sc => $lbl) {
            $sinovaWidgets[] = array('shortcode' => $sc, 'label' => $lbl);
        }

        $backUrl = get_edit_post_link($post->ID, 'raw') ?: admin_url('edit.php?post_type=' . $post->post_type);
        $viewUrl = (string) get_permalink($post->ID);
        $nonce   = wp_create_nonce('pa_kd_builder_nonce');
        ?>
        <style>
            #adminmenumain,#wpadminbar,#wpfooter,.update-nag,.notice{display:none !important}
            #wpcontent,#wpbody,#wpbody-content{margin:0 !important;padding:0 !important}
            html.wp-toolbar{padding-top:0 !important}
            .kd-shell{position:fixed;inset:0;display:grid;grid-template-rows:52px minmax(0,1fr);background:#0f172a;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;z-index:9999}
            .kd-top{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:0 16px;background:linear-gradient(90deg,#022c22 0%,#056e4f 100%);color:#fff;border-bottom:1px solid rgba(255,255,255,.12)}
            .kd-brand{display:flex;align-items:center;gap:10px;font-weight:800;font-size:14px}
            .kd-brand-badge{padding:3px 8px;border-radius:6px;background:#C9A227;color:#022c22;font-size:11px;font-weight:900}
            .kd-top-mid{display:flex;align-items:center;gap:6px}
            .kd-top-btn{background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.18);border-radius:7px;padding:5px 11px;font-size:12px;font-weight:600;cursor:pointer;text-decoration:none}
            .kd-top-btn:hover,.kd-top-btn.is-active{background:rgba(255,255,255,.26);color:#fff}
            .kd-top-save{background:#C9A227;color:#022c22;border:none;border-radius:7px;padding:6px 16px;font-size:12.5px;font-weight:800;cursor:pointer}
            .kd-top-save:hover{background:#e5c158}
            .kd-body{display:grid;grid-template-columns:330px minmax(0,1fr);min-height:0}
            .kd-side{background:#111827;color:#e5e7eb;display:flex;flex-direction:column;border-right:1px solid #1f2937;min-height:0}
            .kd-side-tabs{display:grid;grid-template-columns:1fr 1fr;border-bottom:1px solid #1f2937}
            .kd-side-tab{padding:11px;text-align:center;font-size:12.5px;font-weight:700;background:#111827;color:#9ca3af;border:none;cursor:pointer;border-bottom:2px solid transparent}
            .kd-side-tab.is-active{color:#fff;border-bottom-color:#C9A227;background:#1f2937}
            .kd-side-scroll{flex:1;overflow-y:auto;padding:14px}
            .kd-wgrp{font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:#9ca3af;font-weight:700;margin:12px 0 8px}
            .kd-wgrp:first-child{margin-top:0}
            .kd-wgrid{display:grid;grid-template-columns:1fr 1fr;gap:8px}
            .kd-wbtn{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:5px;padding:11px 8px;background:#1f2937;border:1px solid #374151;border-radius:8px;color:#f3f4f6;font-size:11.5px;font-weight:600;cursor:pointer;text-align:center;transition:border-color .15s,background .15s}
            .kd-wbtn:hover{border-color:#C9A227;background:#263244}
            .kd-wbtn span{font-size:18px}
            .kd-fld{margin-bottom:11px}
            .kd-fld label{display:block;font-size:11.5px;font-weight:700;color:#d1d5db;margin-bottom:4px}
            .kd-fld input[type="text"],.kd-fld input[type="number"],.kd-fld input[type="url"],.kd-fld select,.kd-fld textarea{width:100%;background:#0f172a;color:#f8fafc;border:1px solid #374151;border-radius:6px;padding:7px 9px;font-size:12.5px;box-sizing:border-box}
            .kd-fld textarea{font-family:monospace;line-height:1.45}
            .kd-subtabs{display:grid;grid-template-columns:1fr 1fr 1fr;gap:4px;margin-bottom:12px}
            .kd-subtab{padding:6px;border-radius:6px;border:1px solid #374151;background:#1f2937;color:#9ca3af;font-size:11.5px;font-weight:700;cursor:pointer}
            .kd-subtab.is-active{background:#056e4f;border-color:#056e4f;color:#fff}
            .kd-canvas-area{background:#e2e8f0;overflow-y:auto;padding:22px;display:flex;justify-content:center}
            .kd-canvas-frame{width:100%;max-width:1040px;background:#fff;border-radius:12px;box-shadow:0 18px 42px -18px rgba(15,23,42,.35);padding:26px 30px;min-height:calc(100vh - 100px);transition:max-width .2s ease}
            .kd-canvas-frame.is-tablet{max-width:768px}
            .kd-canvas-frame.is-mobile{max-width:390px;padding:18px 14px}
            .kd-title-input{width:100%;font-size:24px;font-weight:800;color:#0f172a;border:1px dashed transparent;border-radius:8px;padding:6px 10px;margin:0 0 18px;box-sizing:border-box}
            .kd-title-input:focus,.kd-title-input:hover{border-color:#cbd5e1;outline:none;background:#f8fafc}
            .kd-item{position:relative;border:2px solid transparent;border-radius:10px;padding:8px;margin-bottom:10px;transition:border-color .12s;cursor:pointer}
            .kd-item:hover{border-color:#94a3b8}
            .kd-item.is-selected{border-color:#056e4f;background:rgba(5,110,79,.03)}
            .kd-item.is-dragging{opacity:.45}
            .kd-item-bar{position:absolute;top:-12px;right:8px;display:none;gap:3px;background:#022c22;padding:3px 6px;border-radius:6px;z-index:10}
            .kd-item:hover .kd-item-bar,.kd-item.is-selected .kd-item-bar{display:flex}
            .kd-item-bar button{background:transparent;border:none;color:#fff;font-size:11px;cursor:pointer;padding:2px 5px;border-radius:4px}
            .kd-item-bar button:hover{background:rgba(255,255,255,.2)}
            .kd-item-tag{position:absolute;top:-11px;left:10px;background:#056e4f;color:#fff;font-size:10px;font-weight:700;padding:1px 7px;border-radius:4px;display:none}
            .kd-item:hover .kd-item-tag,.kd-item.is-selected .kd-item-tag{display:block}
            .kd-toast{position:fixed;bottom:18px;right:20px;background:#056e4f;color:#fff;padding:10px 16px;border-radius:8px;font-size:13px;font-weight:700;box-shadow:0 10px 25px rgba(0,0,0,.3);display:none;z-index:100000}
            .kd-sinova-ph{padding:14px 16px;background:#f0fdf4;border:1px dashed #056e4f;border-radius:8px;color:#065f46;font-size:13px;font-weight:700}
        </style>

        <div class="kd-shell">
            <header class="kd-top">
                <div class="kd-brand">
                    <span class="kd-brand-badge">KangDevlp</span>
                    <span>Visual Builder · PA SINOVA</span>
                    <a href="<?php echo esc_url($backUrl); ?>" class="kd-top-btn" style="margin-left:8px;">&larr; WP Admin</a>
                </div>
                <div class="kd-top-mid">
                    <button type="button" class="kd-top-btn is-active" data-vp="desktop">🖥 Desktop</button>
                    <button type="button" class="kd-top-btn" data-vp="tablet">📱 Tablet</button>
                    <button type="button" class="kd-top-btn" data-vp="mobile">📲 HP</button>
                    <button type="button" class="kd-top-btn" id="kd-undo" title="Undo">↶ Undo</button>
                    <button type="button" class="kd-top-btn" id="kd-redo" title="Redo">↷ Redo</button>
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <a href="<?php echo esc_url($viewUrl); ?>" target="_blank" rel="noopener noreferrer" class="kd-top-btn">Lihat Live ↗</a>
                    <button type="button" class="kd-top-save" id="kd-save-btn">Simpan &amp; Terbitkan</button>
                </div>
            </header>

            <div class="kd-body">
                <aside class="kd-side">
                    <div class="kd-side-tabs">
                        <button type="button" class="kd-side-tab is-active" data-stab="widgets">+ Tambah Widget</button>
                        <button type="button" class="kd-side-tab" data-stab="inspector">⚙ Pengaturan</button>
                    </div>
                    <div class="kd-side-scroll" id="kd-panel-widgets">
                        <div class="kd-wgrp">Dasar &amp; Tata Letak</div>
                        <div class="kd-wgrid">
                            <button type="button" class="kd-wbtn" data-add="heading"><span>H</span>Heading</button>
                            <button type="button" class="kd-wbtn" data-add="text"><span>¶</span>Teks / Paragraf</button>
                            <button type="button" class="kd-wbtn" data-add="image"><span>🖼️</span>Gambar</button>
                            <button type="button" class="kd-wbtn" data-add="button"><span>🔘</span>Tombol CTA</button>
                            <button type="button" class="kd-wbtn" data-add="columns"><span>▥</span>Grid 2–3 Kolom</button>
                            <button type="button" class="kd-wbtn" data-add="spacer"><span>↕️</span>Spacer / Garis</button>
                        </div>

                        <div class="kd-wgrp">Konten Kaya &amp; HTML</div>
                        <div class="kd-wgrid">
                            <button type="button" class="kd-wbtn" data-add="html"><span>&lt;/&gt;</span>HTML Custom</button>
                            <button type="button" class="kd-wbtn" data-add="card"><span>📌</span>Kartu Info</button>
                            <button type="button" class="kd-wbtn" data-add="accordion"><span>▾</span>Accordion FAQ</button>
                            <button type="button" class="kd-wbtn" data-add="pdf"><span>📄</span>Embed PDF / Drive</button>
                            <button type="button" class="kd-wbtn" data-add="maps"><span>🗺️</span>Google Maps</button>
                            <button type="button" class="kd-wbtn" data-add="table"><span>⊞</span>Tabel Responsif</button>
                        </div>

                        <div class="kd-wgrp">Dinamis SINOVA Hub</div>
                        <div class="kd-wgrid">
                            <button type="button" class="kd-wbtn" data-add="sinova_docs"><span>📚</span>Arsip Dokumen</button>
                            <button type="button" class="kd-wbtn" data-add="sinova_widget"><span>🏛️</span>Widget Data PA</button>
                        </div>
                    </div>
                    <div class="kd-side-scroll" id="kd-panel-inspector" style="display:none;"></div>
                </aside>

                <main class="kd-canvas-area">
                    <div class="kd-canvas-frame" id="kd-canvas-frame">
                        <input type="text" id="kd-post-title" class="kd-title-input" value="<?php echo esc_attr((string) $post->post_title); ?>" placeholder="Judul Postingan / Laman">
                        <div id="kd-canvas-list"></div>
                    </div>
                </main>
            </div>
        </div>
        <div class="kd-toast" id="kd-toast"></div>

        <script>
        (function(){
            var POST_ID = <?php echo (int) $post->ID; ?>;
            var NONCE = <?php echo wp_json_encode($nonce); ?>;
            var AJAX_URL = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
            var DOC_OPTIONS = <?php echo wp_json_encode($docOptions); ?>;
            var SINOVA_WIDGETS = <?php echo wp_json_encode($sinovaWidgets); ?>;

            var state = {
                blocks: <?php echo wp_json_encode($blocks); ?>,
                selectedId: null,
                inspectorTab: 'content',
                pageCss: <?php echo wp_json_encode($pageCss); ?>,
                history: [],
                future: []
            };
            if (state.blocks.length) state.selectedId = state.blocks[0].id;

            function uid() { return 'blk_' + Math.random().toString(36).slice(2, 9); }
            function esc(s) {
                return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
            }
            function snapshot() {
                state.history.push(JSON.stringify(state.blocks));
                if (state.history.length > 40) state.history.shift();
                state.future = [];
            }
            function toast(msg, isErr) {
                var t = document.getElementById('kd-toast');
                t.textContent = msg;
                t.style.background = isErr ? '#dc2626' : '#056e4f';
                t.style.display = 'block';
                clearTimeout(t._tm);
                t._tm = setTimeout(function(){ t.style.display = 'none'; }, 2800);
            }

            function defaultBlock(type) {
                var id = uid();
                var baseStyle = { bg: '', color: '', padding: 0, marginBottom: 18, radius: 0, align: '', cssClass: '' };
                var map = {
                    heading:       { tag: 'h2', text: 'Judul Bagian Baru' },
                    text:          { content: 'Tulis paragraf atau penjelasan informasi layanan di sini.' },
                    html:          { html: '<div style="padding:14px;background:#f4f9f6;border-left:4px solid #056e4f;border-radius:8px;">\n  <strong>Blok HTML Custom</strong>\n  <p>Anda bebas menulis HTML, embed, atau tabel di sini.</p>\n</div>' },
                    image:         { url: '', alt: 'Dokumentasi', caption: '', width: 100 },
                    button:        { label: 'Buka Layanan', url: 'https://', btnStyle: 'green', newTab: true },
                    card:          { icon: '📌', title: 'Informasi Layanan', body: 'Uraian singkat pengumuman atau layanan.', accent: 'green' },
                    columns:       { cols: [{ title: 'Kolom 1', body: 'Isi kolom pertama.' }, { title: 'Kolom 2', body: 'Isi kolom kedua.' }] },
                    accordion:     { title: 'Klik untuk melihat rincian prosedur', body: 'Langkah 1...\nLangkah 2...', open: false },
                    pdf:           { title: 'Dokumen Resmi (PDF)', url: 'https://drive.google.com/file/d/ID_FILE/view', height: 520 },
                    maps:          { query: 'Pengadilan Agama Bintuhan', height: 360 },
                    table:         { rows: 'No | Uraian | Keterangan\n1 | Contoh Baris 1 | Aktif\n2 | Contoh Baris 2 | Selesai' },
                    spacer:        { height: 24, divider: true },
                    sinova_docs:   { slug: (DOC_OPTIONS[0] && DOC_OPTIONS[0].slug) || 'lra' },
                    sinova_widget: { shortcode: 'pa_sinova_latest_posts' }
                };
                return { id: id, type: type, data: map[type] || {}, style: baseStyle };
            }

            function renderBlockPreview(b) {
                var d = b.data || {};
                var s = b.style || {};
                var st = [];
                if (s.bg) st.push('background:' + s.bg);
                if (s.color) st.push('color:' + s.color);
                if (s.padding) st.push('padding:' + s.padding + 'px');
                if (s.radius) st.push('border-radius:' + s.radius + 'px');
                if (s.align) st.push('text-align:' + s.align);
                var wrapOpen = '<div style="' + esc(st.join(';')) + '">';
                var html = '';
                switch (b.type) {
                    case 'heading':
                        html = '<' + (d.tag || 'h2') + ' style="margin:0;color:#056e4f;">' + esc(d.text) + '</' + (d.tag || 'h2') + '>';
                        break;
                    case 'text':
                        html = '<div style="line-height:1.7;">' + String(d.content || '').replace(/\n/g,'<br>') + '</div>';
                        break;
                    case 'html':
                        html = String(d.html || '<em style="color:#94a3b8;">(Blok HTML Custom kosong)</em>');
                        break;
                    case 'image':
                        html = d.url
                            ? '<img src="' + esc(d.url) + '" alt="' + esc(d.alt) + '" style="width:' + (d.width || 100) + '%;max-width:100%;border-radius:8px;">' + (d.caption ? '<div style="font-size:12px;color:#64748b;margin-top:4px;">' + esc(d.caption) + '</div>' : '')
                            : '<div class="kd-sinova-ph">🖼️ Klik untuk memilih Gambar dari Media Library</div>';
                        break;
                    case 'button':
                        var bcol = d.btnStyle === 'gold' ? '#C9A227;color:#022c22' : (d.btnStyle === 'dark' ? '#0f172a;color:#fff' : (d.btnStyle === 'outline' ? 'transparent;color:#056e4f;border:2px solid #056e4f' : '#056e4f;color:#fff'));
                        html = '<span style="display:inline-block;padding:9px 18px;border-radius:8px;font-weight:700;background:' + bcol + ';">' + esc(d.label || 'Tombol') + '</span>';
                        break;
                    case 'card':
                        var cbor = d.accent === 'gold' ? '#C9A227' : (d.accent === 'blue' ? '#0284c7' : (d.accent === 'red' ? '#dc2626' : '#056e4f'));
                        html = '<div style="padding:14px 16px;border-radius:9px;border:1px solid #cbd5e1;border-left:5px solid ' + cbor + ';background:#f8fafc;"><strong>' + esc(d.icon || '📌') + ' ' + esc(d.title) + '</strong><p style="margin:6px 0 0;">' + String(d.body || '').replace(/\n/g,'<br>') + '</p></div>';
                        break;
                    case 'columns':
                        var cols = d.cols || [];
                        html = '<div style="display:grid;grid-template-columns:repeat(' + cols.length + ',minmax(0,1fr));gap:12px;">' + cols.map(function(c){
                            return '<div style="padding:12px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;"><strong style="color:#056e4f;">' + esc(c.title) + '</strong><div style="margin-top:6px;font-size:13px;">' + String(c.body || '') + '</div></div>';
                        }).join('') + '</div>';
                        break;
                    case 'accordion':
                        html = '<div style="padding:12px 14px;border:1px solid #cbd5e1;border-left:4px solid #056e4f;border-radius:8px;"><strong style="color:#056e4f;">▾ ' + esc(d.title) + '</strong><div style="margin-top:6px;font-size:13px;color:#475569;">' + String(d.body || '').replace(/\n/g,'<br>') + '</div></div>';
                        break;
                    case 'pdf':
                        html = '<div class="kd-sinova-ph">📄 Embed PDF / Google Drive: ' + esc(d.title) + ' (' + esc(d.url) + ')</div>';
                        break;
                    case 'maps':
                        html = '<div class="kd-sinova-ph">🗺️ Google Maps Embed: ' + esc(d.query) + '</div>';
                        break;
                    case 'table':
                        var lines = String(d.rows || '').split(/\r?\n/).filter(Boolean);
                        html = '<table style="width:100%;border-collapse:collapse;font-size:13px;">' + lines.map(function(ln, idx){
                            var tag = idx === 0 ? 'th' : 'td';
                            var bgc = idx === 0 ? 'background:#056e4f;color:#fff;' : '';
                            return '<tr>' + ln.split('|').map(function(c){ return '<' + tag + ' style="border:1px solid #cbd5e1;padding:7px 10px;' + bgc + '">' + esc(c.trim()) + '</' + tag + '>'; }).join('') + '</tr>';
                        }).join('') + '</table>';
                        break;
                    case 'spacer':
                        html = d.divider ? '<hr style="border:0;border-top:1px solid #cbd5e1;margin:10px 0;">' : '<div style="height:' + (d.height || 24) + 'px;border:1px dashed #cbd5e1;border-radius:4px;"></div>';
                        break;
                    case 'sinova_docs':
                        html = '<div class="kd-sinova-ph">📚 Modul Arsip Dokumen SINOVA Hub: <code>[pa_sinova_docs slug="' + esc(d.slug) + '"]</code></div>';
                        break;
                    case 'sinova_widget':
                        html = '<div class="kd-sinova-ph">🏛️ Widget Dinamis PA SINOVA: <code>[' + esc(d.shortcode) + ']</code></div>';
                        break;
                }
                return wrapOpen + html + '</div>';
            }

            function renderCanvas() {
                var list = document.getElementById('kd-canvas-list');
                if (!state.blocks.length) {
                    list.innerHTML = '<div style="text-align:center;padding:48px 20px;border:2px dashed #cbd5e1;border-radius:12px;color:#64748b;">Klik widget di panel kiri untuk mulai menyusun halaman.</div>';
                    return;
                }
                list.innerHTML = state.blocks.map(function(b, idx){
                    var sel = b.id === state.selectedId ? ' is-selected' : '';
                    return '<div class="kd-item' + sel + '" draggable="true" data-id="' + b.id + '" data-idx="' + idx + '">'
                        + '<span class="kd-item-tag">' + esc(b.type.toUpperCase()) + '</span>'
                        + '<div class="kd-item-bar">'
                        + '<button type="button" data-act="up" title="Naik">↑</button>'
                        + '<button type="button" data-act="down" title="Turun">↓</button>'
                        + '<button type="button" data-act="dup" title="Duplikat">⧉</button>'
                        + '<button type="button" data-act="del" title="Hapus">✕</button>'
                        + '</div>'
                        + renderBlockPreview(b)
                        + '</div>';
                }).join('');

                list.querySelectorAll('.kd-item').forEach(function(el){
                    var id = el.getAttribute('data-id');
                    var idx = parseInt(el.getAttribute('data-idx'), 10);
                    el.addEventListener('click', function(e){
                        var actBtn = e.target.closest('button[data-act]');
                        if (actBtn) {
                            e.stopPropagation();
                            var act = actBtn.getAttribute('data-act');
                            snapshot();
                            if (act === 'up' && idx > 0) {
                                var tmp = state.blocks[idx - 1]; state.blocks[idx - 1] = state.blocks[idx]; state.blocks[idx] = tmp;
                            } else if (act === 'down' && idx < state.blocks.length - 1) {
                                var tmp2 = state.blocks[idx + 1]; state.blocks[idx + 1] = state.blocks[idx]; state.blocks[idx] = tmp2;
                            } else if (act === 'dup') {
                                var copy = JSON.parse(JSON.stringify(state.blocks[idx])); copy.id = uid();
                                state.blocks.splice(idx + 1, 0, copy); state.selectedId = copy.id;
                            } else if (act === 'del') {
                                state.blocks.splice(idx, 1);
                                state.selectedId = state.blocks[0] ? state.blocks[0].id : null;
                            }
                            renderCanvas(); renderInspector();
                            return;
                        }
                        state.selectedId = id;
                        switchSideTab('inspector');
                        renderCanvas(); renderInspector();
                    });
                    el.addEventListener('dragstart', function(e){ el.classList.add('is-dragging'); e.dataTransfer.setData('text/plain', String(idx)); });
                    el.addEventListener('dragend', function(){ el.classList.remove('is-dragging'); });
                    el.addEventListener('dragover', function(e){ e.preventDefault(); });
                    el.addEventListener('drop', function(e){
                        e.preventDefault();
                        var from = parseInt(e.dataTransfer.getData('text/plain'), 10);
                        if (!isNaN(from) && from !== idx) {
                            snapshot();
                            var moved = state.blocks.splice(from, 1)[0];
                            state.blocks.splice(idx, 0, moved);
                            renderCanvas();
                        }
                    });
                });
            }

            function getSelected() {
                return state.blocks.find(function(b){ return b.id === state.selectedId; }) || null;
            }

            function renderInspector() {
                var panel = document.getElementById('kd-panel-inspector');
                var b = getSelected();
                if (!b) {
                    panel.innerHTML = '<p style="color:#9ca3af;font-size:12.5px;">Pilih salah satu blok di kanvas kanan untuk mengatur konten, gaya, dan CSS.</p>';
                    return;
                }
                var t = state.inspectorTab;
                var html = '<div class="kd-subtabs">'
                    + '<button type="button" class="kd-subtab' + (t==='content'?' is-active':'') + '" data-itab="content">Konten</button>'
                    + '<button type="button" class="kd-subtab' + (t==='style'?' is-active':'') + '" data-itab="style">Gaya</button>'
                    + '<button type="button" class="kd-subtab' + (t==='adv'?' is-active':'') + '" data-itab="adv">Lanjutan</button>'
                    + '</div>';

                var d = b.data || {};
                var s = b.style || {};

                if (t === 'content') {
                    if (b.type === 'heading') {
                        html += '<div class="kd-fld"><label>Level Heading</label><select data-k="tag">'
                            + ['h1','h2','h3','h4'].map(function(h){ return '<option value="'+h+'"'+(d.tag===h?' selected':'')+'>'+h.toUpperCase()+'</option>'; }).join('')
                            + '</select></div>'
                            + '<div class="kd-fld"><label>Teks Judul</label><input type="text" data-k="text" value="'+esc(d.text)+'"></div>';
                    } else if (b.type === 'text') {
                        html += '<div class="kd-fld"><label>Isi Paragraf (mendukung HTML dasar)</label><textarea rows="9" data-k="content">'+esc(d.content)+'</textarea></div>';
                    } else if (b.type === 'html') {
                        html += '<div class="kd-fld"><label>Kode HTML Custom (Widget HTML)</label><textarea rows="14" data-k="html">'+esc(d.html)+'</textarea></div>';
                    } else if (b.type === 'image') {
                        html += '<div class="kd-fld"><label>URL Gambar</label><input type="url" data-k="url" value="'+esc(d.url)+'"></div>'
                            + '<div class="kd-fld"><button type="button" class="kd-top-save" id="kd-pick-media" style="width:100%;">📁 Pilih dari Media Library WP</button></div>'
                            + '<div class="kd-fld"><label>Alt Teks</label><input type="text" data-k="alt" value="'+esc(d.alt)+'"></div>'
                            + '<div class="kd-fld"><label>Keterangan (Caption)</label><input type="text" data-k="caption" value="'+esc(d.caption)+'"></div>'
                            + '<div class="kd-fld"><label>Lebar (%)</label><input type="number" min="10" max="100" data-k="width" value="'+esc(d.width || 100)+'"></div>';
                    } else if (b.type === 'button') {
                        html += '<div class="kd-fld"><label>Label Tombol</label><input type="text" data-k="label" value="'+esc(d.label)+'"></div>'
                            + '<div class="kd-fld"><label>URL Tujuan</label><input type="url" data-k="url" value="'+esc(d.url)+'"></div>'
                            + '<div class="kd-fld"><label>Gaya Warna</label><select data-k="btnStyle">'
                            + [['green','Hijau PA'],['gold','Emas'],['outline','Outline Hijau'],['dark','Gelap']].map(function(o){ return '<option value="'+o[0]+'"'+(d.btnStyle===o[0]?' selected':'')+'>'+o[1]+'</option>'; }).join('')
                            + '</select></div>'
                            + '<div class="kd-fld"><label><input type="checkbox" data-k="newTab" '+(d.newTab?'checked':'')+'> Buka di tab baru</label></div>';
                    } else if (b.type === 'card') {
                        html += '<div class="kd-fld"><label>Ikon / Emoji</label><input type="text" data-k="icon" value="'+esc(d.icon)+'"></div>'
                            + '<div class="kd-fld"><label>Judul Kartu</label><input type="text" data-k="title" value="'+esc(d.title)+'"></div>'
                            + '<div class="kd-fld"><label>Isi Kartu (mendukung HTML)</label><textarea rows="6" data-k="body">'+esc(d.body)+'</textarea></div>'
                            + '<div class="kd-fld"><label>Aksen Warna</label><select data-k="accent">'
                            + [['green','Hijau'],['gold','Emas'],['blue','Biru Info'],['red','Merah Peringatan']].map(function(o){ return '<option value="'+o[0]+'"'+(d.accent===o[0]?' selected':'')+'>'+o[1]+'</option>'; }).join('')
                            + '</select></div>';
                    } else if (b.type === 'columns') {
                        var cols = d.cols || [];
                        html += '<div class="kd-fld"><label>Jumlah Kolom</label><select id="kd-col-count">'
                            + '<option value="2"'+(cols.length===2?' selected':'')+'>2 Kolom</option>'
                            + '<option value="3"'+(cols.length===3?' selected':'')+'>3 Kolom</option>'
                            + '</select></div>';
                        cols.forEach(function(c, ci){
                            html += '<div style="padding:8px;border:1px solid #374151;border-radius:6px;margin-bottom:8px;">'
                                + '<div class="kd-fld"><label>Judul Kolom '+(ci+1)+'</label><input type="text" data-col-idx="'+ci+'" data-col-k="title" value="'+esc(c.title)+'"></div>'
                                + '<div class="kd-fld" style="margin-bottom:0;"><label>Isi Kolom '+(ci+1)+' (HTML)</label><textarea rows="4" data-col-idx="'+ci+'" data-col-k="body">'+esc(c.body)+'</textarea></div>'
                                + '</div>';
                        });
                    } else if (b.type === 'accordion') {
                        html += '<div class="kd-fld"><label>Judul Lipatan</label><input type="text" data-k="title" value="'+esc(d.title)+'"></div>'
                            + '<div class="kd-fld"><label>Isi Detail (HTML)</label><textarea rows="7" data-k="body">'+esc(d.body)+'</textarea></div>'
                            + '<div class="kd-fld"><label><input type="checkbox" data-k="open" '+(d.open?'checked':'')+'> Terbuka secara default</label></div>';
                    } else if (b.type === 'pdf') {
                        html += '<div class="kd-fld"><label>Judul Dokumen</label><input type="text" data-k="title" value="'+esc(d.title)+'"></div>'
                            + '<div class="kd-fld"><label>URL PDF / Google Drive</label><input type="url" data-k="url" value="'+esc(d.url)+'"></div>'
                            + '<div class="kd-fld"><label>Tinggi Viewer (px)</label><input type="number" min="280" max="1000" data-k="height" value="'+esc(d.height || 520)+'"></div>';
                    } else if (b.type === 'maps') {
                        html += '<div class="kd-fld"><label>Nama Lokasi / Koordinat / URL Embed Maps</label><input type="text" data-k="query" value="'+esc(d.query)+'"></div>'
                            + '<div class="kd-fld"><label>Tinggi Peta (px)</label><input type="number" min="220" max="700" data-k="height" value="'+esc(d.height || 360)+'"></div>';
                    } else if (b.type === 'table') {
                        html += '<div class="kd-fld"><label>Baris Tabel (pisahkan kolom dengan tanda <code>|</code>, baris 1 = Header)</label><textarea rows="9" data-k="rows">'+esc(d.rows)+'</textarea></div>';
                    } else if (b.type === 'spacer') {
                        html += '<div class="kd-fld"><label>Tinggi Jarak (px)</label><input type="number" min="8" max="160" data-k="height" value="'+esc(d.height || 24)+'"></div>'
                            + '<div class="kd-fld"><label><input type="checkbox" data-k="divider" '+(d.divider?'checked':'')+'> Tampilkan garis pemisah</label></div>';
                    } else if (b.type === 'sinova_docs') {
                        html += '<div class="kd-fld"><label>Pilih Modul Arsip SINOVA Hub</label><select data-k="slug">'
                            + DOC_OPTIONS.map(function(o){ return '<option value="'+esc(o.slug)+'"'+(d.slug===o.slug?' selected':'')+'>'+esc(o.label)+'</option>'; }).join('')
                            + '</select></div><p style="font-size:11.5px;color:#9ca3af;">Dokumen tetap diunggah &amp; dikelola dari menu Arsip Dokumen di SINOVA Hub.</p>';
                    } else if (b.type === 'sinova_widget') {
                        html += '<div class="kd-fld"><label>Pilih Widget Data PA SINOVA</label><select data-k="shortcode">'
                            + SINOVA_WIDGETS.map(function(o){ return '<option value="'+esc(o.shortcode)+'"'+(d.shortcode===o.shortcode?' selected':'')+'>'+esc(o.label)+'</option>'; }).join('')
                            + '</select></div>';
                    }
                } else if (t === 'style') {
                    html += '<div class="kd-fld"><label>Warna Latar (Hex, contoh #f4f9f6)</label><input type="text" data-sk="bg" value="'+esc(s.bg)+'" placeholder="#ffffff"></div>'
                        + '<div class="kd-fld"><label>Warna Teks (Hex, contoh #0f172a)</label><input type="text" data-sk="color" value="'+esc(s.color)+'" placeholder="#0f172a"></div>'
                        + '<div class="kd-fld"><label>Padding Dalam (px)</label><input type="number" min="0" max="120" data-sk="padding" value="'+esc(s.padding || 0)+'"></div>'
                        + '<div class="kd-fld"><label>Jarak Bawah / Margin (px)</label><input type="number" min="0" max="160" data-sk="marginBottom" value="'+esc(s.marginBottom !== undefined ? s.marginBottom : 18)+'"></div>'
                        + '<div class="kd-fld"><label>Radius Sudut (px)</label><input type="number" min="0" max="48" data-sk="radius" value="'+esc(s.radius || 0)+'"></div>'
                        + '<div class="kd-fld"><label>Perataan Teks</label><select data-sk="align">'
                        + [['','Default'],['left','Kiri'],['center','Tengah'],['right','Kanan'],['justify','Rata Kiri-Kanan']].map(function(o){ return '<option value="'+o[0]+'"'+(s.align===o[0]?' selected':'')+'>'+o[1]+'</option>'; }).join('')
                        + '</select></div>';
                } else {
                    html += '<div class="kd-fld"><label>CSS Class Blok (Opsional)</label><input type="text" data-sk="cssClass" value="'+esc(s.cssClass)+'" placeholder="misal: box-khusus"></div>'
                        + '<div class="kd-fld"><label>Custom CSS Khusus Halaman/Post Ini</label><textarea rows="10" id="kd-page-css" placeholder=".pa-kd-heading { letter-spacing: .02em; }">'+esc(state.pageCss)+'</textarea></div>';
                }

                panel.innerHTML = html;

                panel.querySelectorAll('.kd-subtab').forEach(function(btn){
                    btn.addEventListener('click', function(){
                        state.inspectorTab = btn.getAttribute('data-itab');
                        renderInspector();
                    });
                });
                panel.querySelectorAll('[data-k]').forEach(function(inp){
                    var ev = (inp.type === 'checkbox' || inp.tagName === 'SELECT') ? 'change' : 'input';
                    inp.addEventListener(ev, function(){
                        var k = inp.getAttribute('data-k');
                        b.data[k] = inp.type === 'checkbox' ? inp.checked : inp.value;
                        renderCanvas();
                    });
                });
                panel.querySelectorAll('[data-sk]').forEach(function(inp){
                    var ev = inp.tagName === 'SELECT' ? 'change' : 'input';
                    inp.addEventListener(ev, function(){
                        var sk = inp.getAttribute('data-sk');
                        b.style[sk] = inp.type === 'number' ? parseInt(inp.value || '0', 10) : inp.value;
                        renderCanvas();
                    });
                });
                panel.querySelectorAll('[data-col-idx]').forEach(function(inp){
                    inp.addEventListener('input', function(){
                        var ci = parseInt(inp.getAttribute('data-col-idx'), 10);
                        var ck = inp.getAttribute('data-col-k');
                        if (b.data.cols && b.data.cols[ci]) {
                            b.data.cols[ci][ck] = inp.value;
                            renderCanvas();
                        }
                    });
                });
                var colCnt = document.getElementById('kd-col-count');
                if (colCnt) {
                    colCnt.addEventListener('change', function(){
                        var target = parseInt(colCnt.value, 10);
                        b.data.cols = b.data.cols || [];
                        while (b.data.cols.length < target) b.data.cols.push({ title: 'Kolom ' + (b.data.cols.length + 1), body: 'Isi kolom.' });
                        if (b.data.cols.length > target) b.data.cols = b.data.cols.slice(0, target);
                        renderCanvas(); renderInspector();
                    });
                }
                var mediaBtn = document.getElementById('kd-pick-media');
                if (mediaBtn && window.wp && wp.media) {
                    mediaBtn.addEventListener('click', function(){
                        var frame = wp.media({ title: 'Pilih Gambar', button: { text: 'Gunakan Gambar' }, multiple: false });
                        frame.on('select', function(){
                            var att = frame.state().get('selection').first().toJSON();
                            b.data.url = att.url || '';
                            if (!b.data.alt && att.alt) b.data.alt = att.alt;
                            renderCanvas(); renderInspector();
                        });
                        frame.open();
                    });
                }
                var pageCssEl = document.getElementById('kd-page-css');
                if (pageCssEl) {
                    pageCssEl.addEventListener('input', function(){ state.pageCss = pageCssEl.value; });
                }
            }

            function switchSideTab(tab) {
                document.querySelectorAll('.kd-side-tab').forEach(function(t){
                    t.classList.toggle('is-active', t.getAttribute('data-stab') === tab);
                });
                document.getElementById('kd-panel-widgets').style.display = tab === 'widgets' ? 'block' : 'none';
                document.getElementById('kd-panel-inspector').style.display = tab === 'inspector' ? 'block' : 'none';
            }

            document.querySelectorAll('.kd-side-tab').forEach(function(btn){
                btn.addEventListener('click', function(){ switchSideTab(btn.getAttribute('data-stab')); });
            });
            document.querySelectorAll('.kd-wbtn[data-add]').forEach(function(btn){
                btn.addEventListener('click', function(){
                    snapshot();
                    var nb = defaultBlock(btn.getAttribute('data-add'));
                    state.blocks.push(nb);
                    state.selectedId = nb.id;
                    state.inspectorTab = 'content';
                    renderCanvas(); renderInspector();
                    switchSideTab('inspector');
                });
            });
            document.querySelectorAll('[data-vp]').forEach(function(btn){
                btn.addEventListener('click', function(){
                    var vp = btn.getAttribute('data-vp');
                    document.querySelectorAll('[data-vp]').forEach(function(x){ x.classList.toggle('is-active', x === btn); });
                    var f = document.getElementById('kd-canvas-frame');
                    f.classList.remove('is-tablet', 'is-mobile');
                    if (vp === 'tablet') f.classList.add('is-tablet');
                    if (vp === 'mobile') f.classList.add('is-mobile');
                });
            });
            document.getElementById('kd-undo').addEventListener('click', function(){
                if (!state.history.length) return;
                state.future.push(JSON.stringify(state.blocks));
                state.blocks = JSON.parse(state.history.pop());
                state.selectedId = state.blocks[0] ? state.blocks[0].id : null;
                renderCanvas(); renderInspector();
            });
            document.getElementById('kd-redo').addEventListener('click', function(){
                if (!state.future.length) return;
                state.history.push(JSON.stringify(state.blocks));
                state.blocks = JSON.parse(state.future.pop());
                state.selectedId = state.blocks[0] ? state.blocks[0].id : null;
                renderCanvas(); renderInspector();
            });
            document.getElementById('kd-save-btn').addEventListener('click', function(){
                var btn = document.getElementById('kd-save-btn');
                btn.disabled = true; btn.textContent = 'Menyimpan...';
                var fd = new FormData();
                fd.append('action', 'pa_kd_builder_save');
                fd.append('nonce', NONCE);
                fd.append('post_id', String(POST_ID));
                fd.append('post_title', document.getElementById('kd-post-title').value);
                fd.append('blocks_json', JSON.stringify(state.blocks));
                fd.append('page_css', state.pageCss);
                fetch(AJAX_URL, { method: 'POST', credentials: 'same-origin', body: fd })
                    .then(function(r){ return r.json(); })
                    .then(function(res){
                        if (res && res.success) {
                            toast(res.data.message || 'Tersimpan ✓');
                        } else {
                            toast((res && res.data && res.data.message) || 'Gagal menyimpan.', true);
                        }
                    })
                    .catch(function(){ toast('Koneksi gagal saat menyimpan.', true); })
                    .finally(function(){ btn.disabled = false; btn.textContent = 'Simpan & Terbitkan'; });
            });

            renderCanvas();
            renderInspector();
        })();
        </script>
        <?php
    }
}
