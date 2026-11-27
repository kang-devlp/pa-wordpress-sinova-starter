<?php
/**
 * Module Name: PA SINOVA — Wizard Migrasi Joomla ke WordPress 1-Klik
 * Description: Pemindai dry-run, auto-kategori postingan Uncategorized, sinkronisasi laman placeholder, pembersihan tag/stub/duplikat Joomla, serta snapshot & rollback 1-klik.
 * Version: 2.5.1
 */

defined('ABSPATH') || exit;

if (!function_exists('pa_sinova_joomla_category_defs')) {
    function pa_sinova_joomla_category_defs(): array {
        return array(
            'berita' => array(
                'name'   => 'Berita Kegiatan',
                'parent' => '',
                'desc'   => 'Berita kegiatan satker, rapat, bimtek, pelantikan, apel, upacara, kunjungan, dan HUT.',
            ),
            'sidang-keliling-kepaniteraan' => array(
                'name'   => 'Sidang Keliling & Kepaniteraan',
                'parent' => 'berita',
                'desc'   => 'Sidang di luar gedung, pemeriksaan setempat (descente), sita eksekusi, itsbat, dan akta cerai.',
            ),
            'zona-integritas' => array(
                'name'   => 'Zona Integritas & RB',
                'parent' => 'berita',
                'desc'   => 'Pembangunan Zona Integritas (WBK/WBBM), agen perubahan, pakta integritas, SMAP, dan public campaign.',
            ),
            'kerjasama-mou' => array(
                'name'   => 'MoU & Kerjasama',
                'parent' => 'berita',
                'desc'   => 'Nota kesepahaman (MoU), PKS, dan sinergi antar instansi.',
            ),
            'mediasi' => array(
                'name'   => 'Mediasi',
                'parent' => 'berita',
                'desc'   => 'Keberhasilan mediasi dan perdamaian perkara di Pengadilan Agama.',
            ),
            'pengumuman' => array(
                'name'   => 'Pengumuman',
                'parent' => '',
                'desc'   => 'Pengumuman resmi, sisa panjar, edaran/imbauan MA, libur/cuti, rekrutmen/POSBAKUM, lelang, panggilan ghaib, dan duka cita. Otomatis tampil di widget Pengumuman PA SINOVA.',
            ),
            'artikel' => array(
                'name'   => 'Artikel & Tausiyah',
                'parent' => '',
                'desc'   => 'Artikel hukum, opini, kultum, tausiyah, dan edukasi.',
            ),
        );
    }
}

if (!function_exists('pa_sinova_joomla_classify_title')) {
    function pa_sinova_joomla_classify_title(string $rawTitle): string {
        $title = html_entity_decode(trim($rawTitle), ENT_QUOTES, 'UTF-8');
        if ($title === '') {
            return 'berita';
        }
        if (preg_match('/\b(pengumuman|surat\s+edaran|imbauan|himbauan|pemberitahuan|sisa\s+panjar|libur\s+nasional|cuti\s+bersama|rekrutmen|seleksi|lowongan|penerimaan\s+(cpns|ppnpn|pppk|calon|posbakum)|lelang|jam\s+kerja\s+ramadhan|jadwal\s+pelayanan|penipuan|panggilan\s+(ghaib|gaib|sidang|umum)|ghaib|gaib|duka\s+cita|turut\s+berduka|belasungkawa|wafat|meninggal|maklumat\s+larangan)\b/iu', $title)) {
            return 'pengumuman';
        }
        if (preg_match('/\b(mediasi|dimediasi|berhasil\s+damai|perdamaian)\b/iu', $title)) {
            return 'mediasi';
        }
        if (preg_match('/\b(sidang\s+(keliling|di\s+luar\s+gedung|luar\s+gedung|terpadu)|pemeriksaan\s+setempat|descente|decente|eksekusi|itsbat|isbat|akta\s+cerai)\b/iu', $title)) {
            return 'sidang-keliling-kepaniteraan';
        }
        if (preg_match('/\b(zona\s+integritas|\bzi\b|wbk|wbbm|agen\s+perubahan|apm|akreditasi|pakta\s+integritas|smap|gratifikasi|anti\s+korupsi|public\s+campaign)\b/iu', $title)) {
            return 'zona-integritas';
        }
        if (preg_match('/\b(mou|nota\s+kesepahaman|perjanjian\s+kerja\s*sama|\bpks\b|kerjasama|kerja\s+sama|sinergi)\b/iu', $title)) {
            return 'kerjasama-mou';
        }
        if (preg_match('/\b(artikel|opini|makalah|kajian|tinjauan|kultum|tausiyah|tausiah|tips|mengenal|apa\s+itu)\b/iu', $title)) {
            return 'artikel';
        }
        return 'berita';
    }
}

if (!function_exists('pa_sinova_joomla_placeholder_rules')) {
    function pa_sinova_joomla_placeholder_rules(): array {
        return array(
            'visi-dan-misi'              => array('pattern' => '/\bvisi\s*(dan|&)?\s*misi\b/iu', 'multi' => false),
            'tugas-pokok-dan-fungsi'     => array('pattern' => '/\b(tugas\s+pokok|tupoksi)\b/iu', 'multi' => false),
            'struktur-organisasi'        => array('pattern' => '/\bstruktur\s+organisasi\b/iu', 'multi' => false),
            'alamat-dan-kontak'          => array('pattern' => '/\b(alamat\s+dan\s+kontak|kontak\s+pengadilan)\b/iu', 'multi' => false),
            'prosedur-berperkara'        => array('pattern' => '/^prosedur\s+(berperkara|mediasi|pengambilan\s+produk)/iu', 'multi' => true),
            'biaya-proses-berperkara'    => array('pattern' => '/^(biaya\s+proses\s+berperkara|radius\s+biaya\s+panggilan)/iu', 'multi' => true),
            'laporan-tahunan'            => array('pattern' => '/^laporan\s+tahunan\s*$/iu', 'multi' => false),
            'standar-maklumat-pelayanan' => array('pattern' => '/^(maklumat\s+pelayanan|prosedur\s+standar\s+operasional\s*\(sop\)|standar\s+pelayanan)/iu', 'multi' => true),
            'hukuman-disiplin'           => array('pattern' => '/^hukuman\s+disiplin/iu', 'multi' => false),
        );
    }
}

if (!function_exists('pa_sinova_joomla_clean_tags')) {
    function pa_sinova_joomla_clean_tags(string $html): string {
        $html = (string) preg_replace('/\{source\}.*?\{\/source\}/is', '', $html);
        $html = (string) preg_replace('/\{loadposition\s+[^}]+\}/i', '', $html);
        $html = (string) preg_replace('/\{loadmodule\s+[^}]+\}/i', '', $html);
        $html = (string) preg_replace('/\{gallery\}.*?\{\/gallery\}/is', '', $html);
        return trim($html);
    }
}

if (!function_exists('pa_sinova_joomla_is_news_title')) {
    function pa_sinova_joomla_is_news_title(string $title): bool {
        return (bool) preg_match('/\b(hadiri|dihadiri|ikuti|mengikuti|gelar|menggelar|laksanakan|melaksanakan|lakukan|rapat|bimtek|sosialisasi|kunjungan|audiensi|peringatan|upacara|apel|launching|penandatanganan|pimpin|sambut|terima|serahkan|raih|berhasil)\b/iu', $title);
    }
}

if (!function_exists('pa_sinova_joomla_scan')) {
    function pa_sinova_joomla_scan(): array {
        $catDefs = pa_sinova_joomla_category_defs();
        $posts = get_posts(array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ));
        $pages = get_pages(array(
            'post_status' => 'publish',
            'sort_column' => 'ID',
            'sort_order'  => 'ASC',
        ));

        $uncatPosts = array();
        $titleGroups = array();
        $emptyPosts = array();
        $stubPosts = array();
        $helloWorld = array();
        $joomlaTagItems = array();
        $brokenImgItems = array();

        foreach ($posts as $p) {
            $pid = (int) $p->ID;
            $title = html_entity_decode(trim((string) $p->post_title), ENT_QUOTES, 'UTF-8');
            $tl = function_exists('mb_strtolower') ? mb_strtolower($title, 'UTF-8') : strtolower($title);
            $content = (string) $p->post_content;
            $len = strlen(trim($content));
            $cats = wp_get_post_categories($pid);

            if ($cats === array(1) || in_array(1, $cats, true)) {
                $uncatPosts[] = $p;
            }

            if ($pid === 1 || $tl === 'hello world!') {
                $helloWorld[] = array('id' => $pid, 'title' => $title, 'len' => $len);
            } elseif ($len === 0) {
                $emptyPosts[] = array('id' => $pid, 'title' => $title, 'len' => 0);
            } elseif ($len < 80) {
                $stubPosts[] = array('id' => $pid, 'title' => $title, 'len' => $len);
            }

            $normTitle = preg_replace('/\s+/', ' ', $tl);
            if ($normTitle !== '') {
                $titleGroups[$normTitle][] = array('id' => $pid, 'title' => $title, 'len' => $len);
            }

            if (preg_match('/\{(source|loadposition|loadmodule|gallery|pdf=)/i', $content)) {
                $joomlaTagItems[] = array('id' => $pid, 'type' => 'post', 'title' => $title);
            }
            if (preg_match('/src=["\']images\//i', $content)) {
                $brokenImgItems[] = array('id' => $pid, 'type' => 'post', 'title' => $title);
            }
        }

        // Duplicate posts (keep longest / newest)
        $duplicatePosts = array();
        foreach ($titleGroups as $grp) {
            if (count($grp) > 1) {
                usort($grp, static function ($a, $b) {
                    if ($b['len'] !== $a['len']) {
                        return $b['len'] <=> $a['len'];
                    }
                    return $b['id'] <=> $a['id'];
                });
                array_shift($grp); // keep primary
                foreach ($grp as $dup) {
                    $duplicatePosts[] = $dup;
                }
            }
        }

        // Detect placeholder pages and matching Joomla posts
        $rules = pa_sinova_joomla_placeholder_rules();
        $placeholderMatches = array();
        $syncedSourceIds = array();

        foreach ($pages as $pg) {
            $pgId = (int) $pg->ID;
            $pgContent = (string) $pg->post_content;
            $pgTitle = html_entity_decode(trim((string) $pg->post_title), ENT_QUOTES, 'UTF-8');

            if (preg_match('/\{(source|loadposition|loadmodule|gallery|pdf=)/i', $pgContent)) {
                $joomlaTagItems[] = array('id' => $pgId, 'type' => 'page', 'title' => $pgTitle);
            }
            if (preg_match('/src=["\']images\//i', $pgContent)) {
                $brokenImgItems[] = array('id' => $pgId, 'type' => 'page', 'title' => $pgTitle);
            }

            $slug = (string) $pg->post_name;
            if (!isset($rules[$slug]) || strpos($pgContent, '[LENGKAPI') === false) {
                continue;
            }

            $rule = $rules[$slug];
            $matchedSources = array();
            foreach ($posts as $p) {
                $pid = (int) $p->ID;
                $pt = html_entity_decode(trim((string) $p->post_title), ENT_QUOTES, 'UTF-8');
                $pc = trim((string) $p->post_content);
                if (strlen($pc) < 120 || pa_sinova_joomla_is_news_title($pt)) {
                    continue;
                }
                if (preg_match('/src=["\']images\//i', $pc) && strpos($pc, 'wp-content/uploads') === false) {
                    continue; // skip broken old relative-image-only posts
                }
                if (preg_match($rule['pattern'], $pt)) {
                    $matchedSources[] = array(
                        'id'    => $pid,
                        'title' => $pt,
                        'len'   => strlen($pc),
                    );
                }
            }

            if (!empty($matchedSources)) {
                usort($matchedSources, static function ($a, $b) {
                    return $b['len'] <=> $a['len'];
                });
                if (empty($rule['multi'])) {
                    $matchedSources = array($matchedSources[0]);
                }
                foreach ($matchedSources as $ms) {
                    $syncedSourceIds[$ms['id']] = true;
                }
                $placeholderMatches[] = array(
                    'page_id'      => $pgId,
                    'page_slug'    => $slug,
                    'page_title'   => $pgTitle,
                    'source_posts' => $matchedSources,
                );
            }
        }

        // Cleanup exclusion set
        $cleanupIdSet = array();
        foreach (array_merge($helloWorld, $emptyPosts, $stubPosts, $duplicatePosts) as $cItem) {
            $cleanupIdSet[$cItem['id']] = true;
        }
        foreach (array_keys($syncedSourceIds) as $sid) {
            $cleanupIdSet[$sid] = true;
        }

        // Category preview for uncategorized posts (excluding cleanup candidates)
        $catPreview = array();
        foreach ($catDefs as $slug => $def) {
            $catPreview[$slug] = array(
                'slug'    => $slug,
                'name'    => $def['name'],
                'parent'  => $def['parent'],
                'count'   => 0,
                'samples' => array(),
            );
        }
        foreach ($uncatPosts as $p) {
            $pid = (int) $p->ID;
            if (isset($cleanupIdSet[$pid])) {
                continue;
            }
            $pt = html_entity_decode(trim((string) $p->post_title), ENT_QUOTES, 'UTF-8');
            $targetSlug = pa_sinova_joomla_classify_title($pt);
            if (!isset($catPreview[$targetSlug])) {
                $targetSlug = 'berita';
            }
            $catPreview[$targetSlug]['count']++;
            if (count($catPreview[$targetSlug]['samples']) < 3) {
                $catPreview[$targetSlug]['samples'][] = $pt;
            }
        }

        // Empty legacy categories
        $keepSlugs = array_merge(array('uncategorized'), array_keys($catDefs));
        $allTerms = get_terms(array('taxonomy' => 'category', 'hide_empty' => false));
        $emptyCats = array();
        if (!is_wp_error($allTerms)) {
            foreach ($allTerms as $t) {
                if ((int) $t->term_id === 1 || in_array((string) $t->slug, $keepSlugs, true)) {
                    continue;
                }
                if ((int) $t->count === 0) {
                    $emptyCats[] = array(
                        'term_id' => (int) $t->term_id,
                        'slug'    => (string) $t->slug,
                        'name'    => html_entity_decode((string) $t->name, ENT_QUOTES, 'UTF-8'),
                    );
                }
            }
        }

        return array(
            'scanned_at'          => current_time('mysql'),
            'total_published'     => count($posts),
            'uncat_count'         => count($uncatPosts),
            'category_preview'    => $catPreview,
            'placeholder_matches' => $placeholderMatches,
            'cleanup_candidates'  => array(
                'hello_world'     => $helloWorld,
                'empty_posts'     => $emptyPosts,
                'stub_posts'      => $stubPosts,
                'duplicate_posts' => $duplicatePosts,
                'synced_sources'  => array_keys($syncedSourceIds),
                'total_unique'    => count($cleanupIdSet),
            ),
            'joomla_tag_items'    => $joomlaTagItems,
            'broken_img_items'    => $brokenImgItems,
            'empty_categories'    => $emptyCats,
        );
    }
}

if (!function_exists('pa_sinova_joomla_apply')) {
    function pa_sinova_joomla_apply(array $opts): array {
        $scan = pa_sinova_joomla_scan();

        // 1. Save full rollback snapshot BEFORE modifying anything
        $snapPages = array();
        foreach ($scan['placeholder_matches'] as $pm) {
            $pg = get_post((int) $pm['page_id']);
            if ($pg) {
                $snapPages[] = array(
                    'ID'           => (int) $pg->ID,
                    'post_name'    => (string) $pg->post_name,
                    'post_content' => (string) $pg->post_content,
                );
            }
        }
        $allPostsNow = get_posts(array(
            'post_type'      => 'post',
            'post_status'    => array('publish', 'draft', 'pending', 'private'),
            'posts_per_page' => -1,
            'orderby'        => 'ID',
            'order'          => 'ASC',
        ));
        $snapPosts = array();
        foreach ($allPostsNow as $ap) {
            $snapPosts[] = array(
                'ID'          => (int) $ap->ID,
                'post_status' => (string) $ap->post_status,
                'categories'  => wp_get_post_categories((int) $ap->ID),
            );
        }
        $snapshot = array(
            'created_at'       => current_time('mysql'),
            'default_category' => (int) get_option('default_category', 1),
            'pages'            => $snapPages,
            'posts'            => $snapPosts,
        );
        update_option('pa_sinova_joomla_wizard_snapshot', $snapshot, false);

        $stats = array(
            'pages_synced'     => 0,
            'posts_drafted'    => 0,
            'posts_categorized'=> 0,
            'tags_cleaned'     => 0,
            'cats_deleted'     => 0,
        );

        // 2. Sync placeholder pages
        if (!empty($opts['do_sync_pages'])) {
            foreach ($scan['placeholder_matches'] as $pm) {
                $sources = $pm['source_posts'];
                if (empty($sources)) {
                    continue;
                }
                if (count($sources) === 1) {
                    $sp = get_post((int) $sources[0]['id']);
                    $newHtml = $sp ? pa_sinova_joomla_clean_tags((string) $sp->post_content) : '';
                } else {
                    $nav = array();
                    $secs = array();
                    foreach ($sources as $idx => $sMeta) {
                        $sp = get_post((int) $sMeta['id']);
                        if (!$sp) {
                            continue;
                        }
                        $secId = 'pa-mig-sec-' . ($idx + 1);
                        $sTitle = html_entity_decode(trim((string) $sp->post_title), ENT_QUOTES, 'UTF-8');
                        $nav[] = '<li><a href="#' . esc_attr($secId) . '">' . esc_html($sTitle) . '</a></li>';
                        $secs[] = '<section id="' . esc_attr($secId) . '"><h2>' . esc_html($sTitle) . '</h2>' . pa_sinova_joomla_clean_tags((string) $sp->post_content) . '</section>';
                    }
                    $newHtml = '<nav aria-label="Daftar Bagian"><ul>' . implode('', $nav) . '</ul></nav><hr>' . implode('<hr>', $secs);
                }
                if (strlen(trim($newHtml)) > 100) {
                    $res = wp_update_post(array(
                        'ID'           => (int) $pm['page_id'],
                        'post_content' => $newHtml,
                    ), true);
                    if (!is_wp_error($res)) {
                        $stats['pages_synced']++;
                    }
                }
            }
        }

        // 3. Move empty/stub/duplicate/synced-source posts to draft
        if (!empty($opts['do_draft_cleanup'])) {
            $cc = $scan['cleanup_candidates'];
            $draftIds = array();
            foreach (array_merge($cc['hello_world'], $cc['empty_posts'], $cc['stub_posts'], $cc['duplicate_posts']) as $item) {
                $draftIds[(int) $item['id']] = true;
            }
            if (!empty($opts['do_sync_pages'])) {
                foreach ($cc['synced_sources'] as $sid) {
                    $draftIds[(int) $sid] = true;
                }
            }
            foreach (array_keys($draftIds) as $did) {
                $dp = get_post($did);
                if ($dp && $dp->post_type === 'post' && $dp->post_status === 'publish') {
                    $r = wp_update_post(array('ID' => $did, 'post_status' => 'draft'), true);
                    if (!is_wp_error($r)) {
                        $stats['posts_drafted']++;
                    }
                }
            }
        }

        // 4. Create/normalize 7 standard PA SINOVA categories & categorize Uncategorized posts
        $catIds = array();
        if (!empty($opts['do_categories'])) {
            $defs = pa_sinova_joomla_category_defs();
            // Top-level first
            foreach ($defs as $slug => $def) {
                if ($def['parent'] !== '') {
                    continue;
                }
                $term = get_term_by('slug', $slug, 'category');
                if ($term && !is_wp_error($term)) {
                    wp_update_term((int) $term->term_id, 'category', array('name' => $def['name'], 'parent' => 0));
                    $catIds[$slug] = (int) $term->term_id;
                } else {
                    $ins = wp_insert_term($def['name'], 'category', array('slug' => $slug, 'parent' => 0));
                    if (!is_wp_error($ins)) {
                        $catIds[$slug] = (int) $ins['term_id'];
                    }
                }
            }
            // Children next
            foreach ($defs as $slug => $def) {
                if ($def['parent'] === '') {
                    continue;
                }
                $parentId = (int) ($catIds[$def['parent']] ?? 0);
                $term = get_term_by('slug', $slug, 'category');
                if ($term && !is_wp_error($term)) {
                    wp_update_term((int) $term->term_id, 'category', array('name' => $def['name'], 'parent' => $parentId));
                    $catIds[$slug] = (int) $term->term_id;
                } else {
                    $ins = wp_insert_term($def['name'], 'category', array('slug' => $slug, 'parent' => $parentId));
                    if (!is_wp_error($ins)) {
                        $catIds[$slug] = (int) $ins['term_id'];
                    }
                }
            }
            if (!empty($catIds['berita'])) {
                update_option('default_category', $catIds['berita']);
            }

            $pubPosts = get_posts(array(
                'post_type'      => 'post',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'category'       => 1,
            ));
            foreach ($pubPosts as $pp) {
                $pid = (int) $pp->ID;
                $targetSlug = pa_sinova_joomla_classify_title((string) $pp->post_title);
                $tid = (int) ($catIds[$targetSlug] ?? ($catIds['berita'] ?? 1));
                if ($tid > 1) {
                    wp_set_post_categories($pid, array($tid), false);
                    $stats['posts_categorized']++;
                }
            }
        }

        // 5. Clean residual Joomla tags in published posts/pages
        if (!empty($opts['do_clean_tags'])) {
            foreach ($scan['joomla_tag_items'] as $jItem) {
                $jp = get_post((int) $jItem['id']);
                if (!$jp) {
                    continue;
                }
                $cleaned = pa_sinova_joomla_clean_tags((string) $jp->post_content);
                if ($cleaned !== (string) $jp->post_content) {
                    $r = wp_update_post(array('ID' => (int) $jp->ID, 'post_content' => $cleaned), true);
                    if (!is_wp_error($r)) {
                        $stats['tags_cleaned']++;
                    }
                }
            }
        }

        // 6. Delete empty legacy categories
        if (!empty($opts['do_delete_empty_cats'])) {
            $keepSlugs = array_merge(array('uncategorized'), array_keys(pa_sinova_joomla_category_defs()));
            for ($pass = 0; $pass < 2; $pass++) {
                $terms = get_terms(array('taxonomy' => 'category', 'hide_empty' => false));
                if (!is_wp_error($terms)) {
                    foreach ($terms as $t) {
                        if ((int) $t->term_id === 1 || in_array((string) $t->slug, $keepSlugs, true)) {
                            continue;
                        }
                        if ((int) $t->count === 0) {
                            $del = wp_delete_term((int) $t->term_id, 'category');
                            if (!is_wp_error($del) && $del) {
                                $stats['cats_deleted']++;
                            }
                        }
                    }
                }
            }
        }

        if (function_exists('pa_sinova_purge_html_cache_files')) {
            pa_sinova_purge_html_cache_files();
        }
        if (function_exists('pa_home_page_cache_mark_stale')) {
            pa_home_page_cache_mark_stale();
        }
        wp_cache_flush();

        return $stats;
    }
}

if (!function_exists('pa_sinova_joomla_rollback')) {
    function pa_sinova_joomla_rollback(): array {
        $snap = get_option('pa_sinova_joomla_wizard_snapshot', array());
        if (!is_array($snap) || empty($snap['posts'])) {
            return array('ok' => false, 'error' => 'Snapshot rollback tidak ditemukan.');
        }

        $restoredPages = 0;
        if (!empty($snap['pages']) && is_array($snap['pages'])) {
            foreach ($snap['pages'] as $sp) {
                $pid = (int) ($sp['ID'] ?? 0);
                if ($pid > 0 && get_post($pid)) {
                    $r = wp_update_post(array(
                        'ID'           => $pid,
                        'post_content' => (string) ($sp['post_content'] ?? ''),
                    ), true);
                    if (!is_wp_error($r)) {
                        $restoredPages++;
                    }
                }
            }
        }

        $restoredPosts = 0;
        foreach ($snap['posts'] as $sp) {
            $pid = (int) ($sp['ID'] ?? 0);
            if ($pid <= 0 || !get_post($pid)) {
                continue;
            }
            $status = sanitize_key((string) ($sp['post_status'] ?? 'publish'));
            wp_update_post(array('ID' => $pid, 'post_status' => $status), true);
            $cats = isset($sp['categories']) && is_array($sp['categories']) ? array_map('intval', $sp['categories']) : array(1);
            if (!empty($cats)) {
                wp_set_post_categories($pid, $cats, false);
            }
            $restoredPosts++;
        }

        if (!empty($snap['default_category'])) {
            update_option('default_category', (int) $snap['default_category']);
        }
        if (function_exists('pa_sinova_purge_html_cache_files')) {
            pa_sinova_purge_html_cache_files();
        }
        if (function_exists('pa_home_page_cache_mark_stale')) {
            pa_home_page_cache_mark_stale();
        }
        wp_cache_flush();

        return array(
            'ok'             => true,
            'restored_pages' => $restoredPages,
            'restored_posts' => $restoredPosts,
        );
    }
}

add_action('admin_init', static function () {
    if (!isset($_GET['pa_sinova_export']) || $_GET['pa_sinova_export'] !== 'joomla_snapshot') {
        return;
    }
    if (!current_user_can('manage_options')) {
        wp_die('Akses ditolak.', 403);
    }
    check_admin_referer('pa_sinova_joomla_snap_dl');
    $snap = get_option('pa_sinova_joomla_wizard_snapshot', array());
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="pa-sinova-joomla-backup-' . gmdate('Ymd-His') . '.json"');
    echo wp_json_encode($snap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
});

if (!function_exists('pa_sinova_handle_joomla_wizard_action')) {
    function pa_sinova_handle_joomla_wizard_action(string $action): ?string {
        if (!current_user_can('manage_options')) {
            return 'Gagal: Hanya Administrator yang dapat menjalankan Wizard Migrasi Joomla.';
        }
        if ($action === 'joomla_wizard_scan') {
            $res = pa_sinova_joomla_scan();
            set_transient('pa_sinova_joomla_last_scan_' . get_current_user_id(), $res, 30 * MINUTE_IN_SECONDS);
            return sprintf(
                'Pemindaian Dry-Run selesai: %d postingan Uncategorized, %d laman placeholder cocok, %d kandidat pembersihan, %d kategori kosong.',
                (int) $res['uncat_count'],
                count($res['placeholder_matches']),
                (int) $res['cleanup_candidates']['total_unique'],
                count($res['empty_categories'])
            );
        }
        if ($action === 'joomla_wizard_apply') {
            $opts = array(
                'do_categories'        => !empty($_POST['jw_do_categories']),
                'do_sync_pages'        => !empty($_POST['jw_do_sync_pages']),
                'do_draft_cleanup'     => !empty($_POST['jw_do_draft_cleanup']),
                'do_clean_tags'        => !empty($_POST['jw_do_clean_tags']),
                'do_delete_empty_cats' => !empty($_POST['jw_do_delete_empty_cats']),
            );
            $st = pa_sinova_joomla_apply($opts);
            delete_transient('pa_sinova_joomla_last_scan_' . get_current_user_id());
            return sprintf(
                'Wizard Migrasi Joomla selesai (Backup snapshot tersimpan): %d postingan dikategorikan, %d laman placeholder disinkronkan, %d pos dipindah ke Draft, %d tag Joomla dibersihkan, %d kategori kosong dihapus.',
                $st['posts_categorized'],
                $st['pages_synced'],
                $st['posts_drafted'],
                $st['tags_cleaned'],
                $st['cats_deleted']
            );
        }
        if ($action === 'joomla_wizard_rollback') {
            $rb = pa_sinova_joomla_rollback();
            if (empty($rb['ok'])) {
                return 'Gagal Rollback: ' . (string) ($rb['error'] ?? 'Tidak diketahui.');
            }
            delete_transient('pa_sinova_joomla_last_scan_' . get_current_user_id());
            return sprintf(
                'Rollback berhasil: %d laman dan %d postingan dikembalikan ke kondisi sebelum migrasi.',
                (int) $rb['restored_pages'],
                (int) $rb['restored_posts']
            );
        }
        return null;
    }
}

if (!function_exists('pa_sinova_render_joomla_wizard_card')) {
    function pa_sinova_render_joomla_wizard_card(): void {
        $scan = get_transient('pa_sinova_joomla_last_scan_' . get_current_user_id());
        $hasScan = is_array($scan) && isset($scan['uncat_count']);
        $snap = get_option('pa_sinova_joomla_wizard_snapshot', array());
        $hasSnap = is_array($snap) && !empty($snap['created_at']);
        $dlUrl = wp_nonce_url(admin_url('admin.php?page=pa-sinova-starter&pa_sinova_export=joomla_snapshot'), 'pa_sinova_joomla_snap_dl');

        $uncatTerm = get_term(1, 'category');
        $liveUncat = ($uncatTerm && !is_wp_error($uncatTerm)) ? (int) $uncatTerm->count : 0;
        ?>
        <div style="background:#fff;border:1px solid #cbd5e1;border-top:4px solid #056e4f;border-radius:10px;padding:18px;">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:12px;">
                <div>
                    <h2 style="margin:0;color:#056e4f;font-size:16px;">Wizard Migrasi Joomla &rarr; WordPress (1-Klik + Rollback) <?php echo function_exists('pa_sinova_info_tip') ? pa_sinova_info_tip('Otomatis mengelompokkan postingan Uncategorized hasil impor Joomla ke 7 kategori standar PA SINOVA, mengisi laman placeholder [LENGKAPI], membersihkan tag {source}/postingan kosong ke Draft, dan menghapus kategori kosong.', 'Info Wizard Migrasi') : ''; ?></h2>
                </div>
                <span style="padding:4px 10px;border-radius:999px;font-size:11.5px;font-weight:700;<?php echo $liveUncat === 0 ? 'background:#dcfce7;color:#166534;' : 'background:#fef3c7;color:#92400e;'; ?>">
                    <?php echo $liveUncat === 0 ? '● Uncategorized Bersih (0)' : ('● ' . $liveUncat . ' Pos Uncategorized'); ?>
                </span>
            </div>

            <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:14px;">
                <form method="post" style="margin:0;">
                    <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                    <input type="hidden" name="pa_sinova_action" value="joomla_wizard_scan">
                    <button type="submit" class="button button-primary">Pindai &amp; Pratinjau Migrasi (Dry-Run)</button>
                </form>

                <?php if ($hasSnap) : ?>
                    <a href="<?php echo esc_url($dlUrl); ?>" class="button button-secondary">Unduh Backup JSON</a>
                    <form method="post" style="margin:0;" onsubmit="return confirm('Kembalikan kategori postingan dan isi laman ke snapshot sebelum migrasi terakhir?');">
                        <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                        <input type="hidden" name="pa_sinova_action" value="joomla_wizard_rollback">
                        <button type="submit" class="button">Undo / Rollback (<?php echo esc_html((string) $snap['created_at']); ?>)</button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if ($hasScan) : ?>
                <div style="background:#f8fafc;border:1px solid #cbd5e1;border-radius:8px;padding:14px;margin-top:8px;">
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px;margin-bottom:14px;">
                        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:10px;">
                            <div style="font-size:11px;color:#64748b;font-weight:600;">Uncategorized</div>
                            <strong style="font-size:20px;color:#0f172a;"><?php echo esc_html((string) $scan['uncat_count']); ?></strong>
                        </div>
                        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:10px;">
                            <div style="font-size:11px;color:#64748b;font-weight:600;">Laman Placeholder Cocok</div>
                            <strong style="font-size:20px;color:#056e4f;"><?php echo esc_html((string) count($scan['placeholder_matches'])); ?></strong>
                        </div>
                        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:10px;">
                            <div style="font-size:11px;color:#64748b;font-weight:600;">Kandidat Draft (Kosong/Dup)</div>
                            <strong style="font-size:20px;color:#b45309;"><?php echo esc_html((string) $scan['cleanup_candidates']['total_unique']); ?></strong>
                        </div>
                        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:10px;">
                            <div style="font-size:11px;color:#64748b;font-weight:600;">Tag Joomla / Gambar Pecah</div>
                            <strong style="font-size:20px;color:#991b1b;"><?php echo esc_html(count($scan['joomla_tag_items']) . ' / ' . count($scan['broken_img_items'])); ?></strong>
                        </div>
                        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:10px;">
                            <div style="font-size:11px;color:#64748b;font-weight:600;">Kategori Kosong</div>
                            <strong style="font-size:20px;color:#475569;"><?php echo esc_html((string) count($scan['empty_categories'])); ?></strong>
                        </div>
                    </div>

                    <h3 style="margin:0 0 8px;font-size:13.5px;color:#0f172a;">Pratinjau Pemetaan Kategori Otomatis</h3>
                    <table class="widefat striped" style="margin-bottom:14px;">
                        <thead>
                            <tr>
                                <th>Kategori Target (Slug)</th>
                                <th style="width:90px;">Jumlah Pos</th>
                                <th>Contoh Judul Terdeteksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($scan['category_preview'] as $cp) : ?>
                                <tr>
                                    <td>
                                        <?php echo $cp['parent'] !== '' ? '↳ ' : ''; ?>
                                        <strong><?php echo esc_html($cp['name']); ?></strong>
                                        <code><?php echo esc_html($cp['slug']); ?></code>
                                    </td>
                                    <td><strong><?php echo esc_html((string) $cp['count']); ?></strong></td>
                                    <td style="font-size:12px;color:#475569;"><?php echo !empty($cp['samples']) ? esc_html(implode(' · ', $cp['samples'])) : '—'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php if (!empty($scan['placeholder_matches'])) : ?>
                        <h3 style="margin:0 0 8px;font-size:13.5px;color:#0f172a;">Laman Placeholder yang Akan Diisi Otomatis</h3>
                        <ul style="margin:0 0 14px 18px;list-style:disc;font-size:12.5px;color:#334155;">
                            <?php foreach ($scan['placeholder_matches'] as $pm) :
                                $srcLabels = array_map(static function ($s) {
                                    return '#' . $s['id'] . ' ' . $s['title'];
                                }, $pm['source_posts']);
                            ?>
                                <li><strong><?php echo esc_html($pm['page_title']); ?></strong> (<code>/<?php echo esc_html($pm['page_slug']); ?>/</code>) &larr; <?php echo esc_html(implode(', ', $srcLabels)); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <?php if (!empty($scan['broken_img_items'])) : ?>
                        <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:6px;padding:10px 12px;margin-bottom:14px;font-size:12.5px;color:#991b1b;">
                            <strong>Info Gambar Relatif Joomla:</strong> Ditemukan <?php echo esc_html((string) count($scan['broken_img_items'])); ?> konten yang masih memakai <code>src="images/..."</code> (contoh ID: <?php echo esc_html(implode(', ', array_map(static function ($x) { return '#' . $x['id']; }, array_slice($scan['broken_img_items'], 0, 8)))); ?>). Unggah ulang gambar tersebut di Media Library jika gambarnya tidak tampil.
                        </div>
                    <?php endif; ?>

                    <form method="post" onsubmit="return confirm('Terapkan penataan otomatis sekarang? Backup snapshot akan disimpan terlebih dahulu sehingga bisa di-Undo kapan saja.');">
                        <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                        <input type="hidden" name="pa_sinova_action" value="joomla_wizard_apply">
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:8px;margin-bottom:14px;font-size:12.5px;">
                            <label><input type="checkbox" name="jw_do_categories" value="1" checked> Tata kategori <code>Uncategorized</code> + atur default ke <code>Berita Kegiatan</code></label>
                            <label><input type="checkbox" name="jw_do_sync_pages" value="1" checked> Salin konten Joomla ke laman placeholder <code>[LENGKAPI]</code></label>
                            <label><input type="checkbox" name="jw_do_draft_cleanup" value="1" checked> Pindahkan pos kosong/stub/duplikat/sumber laman ke <code>Draft</code></label>
                            <label><input type="checkbox" name="jw_do_clean_tags" value="1" checked> Bersihkan tag residu Joomla (<code>{source}</code>, <code>{loadposition}</code>)</label>
                            <label><input type="checkbox" name="jw_do_delete_empty_cats" value="1" checked> Hapus kategori kosong sisa impor Joomla</label>
                        </div>
                        <button type="submit" class="button button-primary">Terapkan Penataan Otomatis (Dengan Backup)</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
