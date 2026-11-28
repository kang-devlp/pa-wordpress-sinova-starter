<?php
/**
 * Module Name: PA SINOVA — Pengelola Menu & Sub-Menu Visual
 * Description: Ubah nama menu utama, tambah/edit/hapus sub-menu, dan atur urutan menu navigasi langsung dari SINOVA Hub tanpa ribet.
 * Version: 2.5.3
 */

defined('ABSPATH') || exit;

if (!function_exists('pa_sinova_mm_get_primary_menu')) {
    function pa_sinova_mm_get_primary_menu(): ?WP_Term {
        $locs = get_nav_menu_locations();
        foreach (array('primary', 'primary_menu', 'main-menu', 'header-menu') as $lk) {
            if (!empty($locs[$lk])) {
                $t = wp_get_nav_menu_object((int) $locs[$lk]);
                if ($t instanceof WP_Term) {
                    return $t;
                }
            }
        }
        $byName = wp_get_nav_menu_object('Menu Utama SINOVA');
        if ($byName instanceof WP_Term) {
            return $byName;
        }
        $all = wp_get_nav_menus();
        return !empty($all) && $all[0] instanceof WP_Term ? $all[0] : null;
    }
}

if (!function_exists('pa_sinova_mm_handle_action')) {
    function pa_sinova_mm_handle_action(string $action): ?string {
        if (!current_user_can('manage_options')) {
            return 'Gagal: Hanya Administrator yang dapat mengelola menu.';
        }
        $menu = pa_sinova_mm_get_primary_menu();
        if (!$menu) {
            return 'Gagal: Menu utama belum ditemukan. Jalankan Seeder 1-Klik di tab Setup & Migrasi terlebih dahulu.';
        }
        $menuId = (int) $menu->term_id;

        if ($action === 'mm_save_all') {
            $titles  = isset($_POST['mm_title']) && is_array($_POST['mm_title']) ? $_POST['mm_title'] : array();
            $urls    = isset($_POST['mm_url']) && is_array($_POST['mm_url']) ? $_POST['mm_url'] : array();
            $orders  = isset($_POST['mm_order']) && is_array($_POST['mm_order']) ? $_POST['mm_order'] : array();
            $parents = isset($_POST['mm_parent']) && is_array($_POST['mm_parent']) ? $_POST['mm_parent'] : array();
            $updated = 0;

            foreach ($titles as $rawId => $rawTitle) {
                $itemId = (int) $rawId;
                if ($itemId <= 0 || get_post_type($itemId) !== 'nav_menu_item') {
                    continue;
                }
                $newTitle  = sanitize_text_field(wp_unslash((string) $rawTitle));
                $newUrl    = isset($urls[$rawId]) ? trim(wp_unslash((string) $urls[$rawId])) : '';
                $newOrder  = isset($orders[$rawId]) ? max(0, (int) $orders[$rawId]) : 0;
                $newParent = isset($parents[$rawId]) ? max(0, (int) $parents[$rawId]) : 0;
                if ($newTitle === '') {
                    continue;
                }
                wp_update_post(array(
                    'ID'         => $itemId,
                    'post_title' => $newTitle,
                    'menu_order' => $newOrder,
                ));
                update_post_meta($itemId, '_menu_item_menu_item_parent', (string) $newParent);
                $itemType = (string) get_post_meta($itemId, '_menu_item_type', true);
                if ($itemType === 'custom' && $newUrl !== '') {
                    update_post_meta($itemId, '_menu_item_url', esc_url_raw($newUrl));
                }
                $updated++;
            }
            if (function_exists('pa_sinova_purge_html_cache_files')) {
                pa_sinova_purge_html_cache_files();
            }
            if (function_exists('pa_home_page_cache_mark_stale')) {
                pa_home_page_cache_mark_stale();
            }
            return sprintf('Berhasil menyimpan %d item menu & sub-menu (cache dibersihkan).', $updated);
        }

        if ($action === 'mm_add_item') {
            $parentId = max(0, (int) ($_POST['mm_new_parent'] ?? 0));
            $pageId   = max(0, (int) ($_POST['mm_new_page_id'] ?? 0));
            $title    = sanitize_text_field(wp_unslash((string) ($_POST['mm_new_title'] ?? '')));
            $url      = trim(wp_unslash((string) ($_POST['mm_new_url'] ?? '')));

            if ($pageId > 0) {
                $pg = get_post($pageId);
                if (!$pg) {
                    return 'Gagal: Laman yang dipilih tidak ditemukan.';
                }
                $label = $title !== '' ? $title : (string) $pg->post_title;
                wp_update_nav_menu_item($menuId, 0, array(
                    'menu-item-title'     => $label,
                    'menu-item-object'    => 'page',
                    'menu-item-object-id' => $pageId,
                    'menu-item-type'      => 'post_type',
                    'menu-item-parent-id' => $parentId,
                    'menu-item-status'    => 'publish',
                ));
            } else {
                if ($title === '') {
                    return 'Gagal: Judul menu/sub-menu wajib diisi.';
                }
                $finalUrl = $url !== '' ? $url : '#';
                wp_update_nav_menu_item($menuId, 0, array(
                    'menu-item-title'     => $title,
                    'menu-item-url'       => $finalUrl === '#' ? '#' : esc_url_raw($finalUrl),
                    'menu-item-type'      => 'custom',
                    'menu-item-parent-id' => $parentId,
                    'menu-item-status'    => 'publish',
                ));
            }

            if (function_exists('pa_sinova_purge_html_cache_files')) {
                pa_sinova_purge_html_cache_files();
            }
            if (function_exists('pa_home_page_cache_mark_stale')) {
                pa_home_page_cache_mark_stale();
            }
            return $parentId > 0 ? 'Sub-menu baru berhasil ditambahkan.' : 'Menu utama baru berhasil ditambahkan.';
        }

        if ($action === 'mm_delete_item') {
            $delId = max(0, (int) ($_POST['mm_delete_id'] ?? 0));
            if ($delId > 0 && get_post_type($delId) === 'nav_menu_item') {
                // Also detach children if deleting a parent
                $items = wp_get_nav_menu_items($menuId) ?: array();
                foreach ($items as $it) {
                    if ((int) $it->menu_item_parent === $delId) {
                        wp_delete_post((int) $it->ID, true);
                    }
                }
                wp_delete_post($delId, true);
                if (function_exists('pa_sinova_purge_html_cache_files')) {
                    pa_sinova_purge_html_cache_files();
                }
                if (function_exists('pa_home_page_cache_mark_stale')) {
                    pa_home_page_cache_mark_stale();
                }
                return 'Item menu berhasil dihapus.';
            }
            return 'Gagal: Item menu tidak ditemukan.';
        }

        return null;
    }
}

if (!function_exists('pa_sinova_mm_render_tab')) {
    function pa_sinova_mm_render_tab(): void {
        $menu = pa_sinova_mm_get_primary_menu();
        if (!$menu) {
            echo '<div class="notice notice-warning"><p>Menu utama belum tersedia. Jalankan <strong>Seeder 1-Klik</strong> di tab <em>Setup, Migrasi &amp; Update</em> terlebih dahulu.</p></div>';
            return;
        }
        $items = wp_get_nav_menu_items((int) $menu->term_id) ?: array();
        $parents = array();
        $children = array();
        foreach ($items as $it) {
            $pid = (int) $it->menu_item_parent;
            if ($pid === 0) {
                $parents[] = $it;
            } else {
                $children[$pid][] = $it;
            }
        }
        $pages = get_pages(array('post_status' => 'publish', 'sort_column' => 'post_title', 'sort_order' => 'ASC'));
        ?>
        <div style="display:grid;grid-template-columns:1fr;gap:18px;">
            <div style="background:#fff;border:1px solid #cbd5e1;border-top:4px solid #C9A227;border-radius:10px;padding:16px 18px;">
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:12px;">
                    <h2 style="margin:0;color:#056e4f;font-size:16px;">
                        + Tambah Menu Utama / Sub-Menu Baru
                        <?php echo function_exists('pa_sinova_info_tip') ? pa_sinova_info_tip('Pilih Menu Induk untuk menambah sub-menu di bawahnya, atau pilih "Menu Utama (Induk)" untuk menambah tab menu baru di header. Anda bisa memilih Laman WP yang ada atau mengisi URL kustom.', 'Info Tambah Menu') : ''; ?>
                    </h2>
                    <span style="padding:3px 10px;border-radius:999px;background:#ecfdf5;color:#065f46;font-size:11.5px;font-weight:700;">Menu Aktif: <?php echo esc_html($menu->name); ?></span>
                </div>
                <form method="post" style="display:grid;grid-template-columns:1.1fr 1.2fr 1.1fr 1.2fr auto;gap:10px;align-items:end;">
                    <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                    <input type="hidden" name="pa_sinova_action" value="mm_add_item">
                    <div>
                        <label style="display:block;font-weight:700;font-size:12px;margin-bottom:4px;">Posisi Menu</label>
                        <select name="mm_new_parent" style="width:100%;">
                            <option value="0">— Menu Utama (Induk) —</option>
                            <?php foreach ($parents as $pr) : ?>
                                <option value="<?php echo esc_attr((string) $pr->ID); ?>">↳ Sub-menu dari: <?php echo esc_html((string) $pr->title); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-weight:700;font-size:12px;margin-bottom:4px;">Pilih Laman WP (Opsional)</label>
                        <select name="mm_new_page_id" style="width:100%;">
                            <option value="0">-- Tautan Kustom --</option>
                            <?php foreach ($pages as $pg) : ?>
                                <option value="<?php echo esc_attr((string) $pg->ID); ?>"><?php echo esc_html($pg->post_title); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-weight:700;font-size:12px;margin-bottom:4px;">Label Menu</label>
                        <input type="text" name="mm_new_title" placeholder="Contoh: Daftar Perkara SIPP" style="width:100%;">
                    </div>
                    <div>
                        <label style="display:block;font-weight:700;font-size:12px;margin-bottom:4px;">URL Kustom (jika tanpa laman)</label>
                        <input type="text" name="mm_new_url" placeholder="https://... atau #" style="width:100%;">
                    </div>
                    <div>
                        <button type="submit" class="button button-primary">+ Tambah</button>
                    </div>
                </form>
            </div>

            <form method="post">
                <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                <input type="hidden" name="pa_sinova_action" value="mm_save_all">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                    <h2 style="margin:0;color:#056e4f;font-size:16px;">
                        Struktur Menu Utama &amp; Sub-Menu (<?php echo count($parents); ?> Induk)
                        <?php echo function_exists('pa_sinova_info_tip') ? pa_sinova_info_tip('Ubah nama menu utama, urutan, atau label sub-menu langsung di kolom di bawah lalu klik Simpan Semua Perubahan Menu.', 'Info Edit Menu') : ''; ?>
                    </h2>
                    <button type="submit" class="button button-primary button-large">Simpan Semua Perubahan Menu</button>
                </div>

                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(420px,1fr));gap:14px;">
                    <?php foreach ($parents as $pr) :
                        $pid = (int) $pr->ID;
                        $kids = $children[$pid] ?? array();
                        $isCustom = ((string) $pr->type === 'custom');
                    ?>
                        <div style="background:#fff;border:1px solid #cbd5e1;border-top:4px solid #056e4f;border-radius:10px;padding:14px 16px;">
                            <div style="display:grid;grid-template-columns:1fr 64px auto;gap:8px;align-items:center;margin-bottom:8px;">
                                <input type="text" name="mm_title[<?php echo $pid; ?>]" value="<?php echo esc_attr((string) $pr->title); ?>" style="font-weight:800;color:#043927;width:100%;">
                                <input type="number" name="mm_order[<?php echo $pid; ?>]" value="<?php echo esc_attr((string) $pr->menu_order); ?>" title="Urutan" style="width:100%;">
                                <button type="button" class="button button-small pa-mm-del-btn" data-id="<?php echo $pid; ?>" data-title="<?php echo esc_attr((string) $pr->title); ?>" style="color:#b91c1c;">Hapus</button>
                            </div>
                            <input type="hidden" name="mm_parent[<?php echo $pid; ?>]" value="0">
                            <?php if ($isCustom) : ?>
                                <input type="text" name="mm_url[<?php echo $pid; ?>]" value="<?php echo esc_attr((string) $pr->url); ?>" placeholder="URL menu utama" style="width:100%;font-size:12px;margin-bottom:10px;color:#475569;">
                            <?php else : ?>
                                <div style="font-size:11.5px;color:#64748b;margin-bottom:10px;word-break:break-all;">Laman: <?php echo esc_html((string) $pr->url); ?></div>
                            <?php endif; ?>

                            <?php if (!empty($kids)) : ?>
                                <div style="border-top:1px dashed #cbd5e1;padding-top:10px;display:flex;flex-direction:column;gap:6px;">
                                    <?php foreach ($kids as $kd) :
                                        $kid = (int) $kd->ID;
                                        $kCustom = ((string) $kd->type === 'custom');
                                    ?>
                                        <div style="display:grid;grid-template-columns:1fr <?php echo $kCustom ? '1fr ' : ''; ?>56px auto;gap:6px;align-items:center;background:#f8fafc;padding:6px 8px;border-radius:6px;border:1px solid #e2e8f0;">
                                            <input type="text" name="mm_title[<?php echo $kid; ?>]" value="<?php echo esc_attr((string) $kd->title); ?>" style="font-size:12.5px;width:100%;">
                                            <?php if ($kCustom) : ?>
                                                <input type="text" name="mm_url[<?php echo $kid; ?>]" value="<?php echo esc_attr((string) $kd->url); ?>" style="font-size:11.5px;width:100%;">
                                            <?php endif; ?>
                                            <input type="number" name="mm_order[<?php echo $kid; ?>]" value="<?php echo esc_attr((string) $kd->menu_order); ?>" title="Urutan" style="width:100%;font-size:12px;">
                                            <input type="hidden" name="mm_parent[<?php echo $kid; ?>]" value="<?php echo $pid; ?>">
                                            <button type="button" class="button button-small pa-mm-del-btn" data-id="<?php echo $kid; ?>" data-title="<?php echo esc_attr((string) $kd->title); ?>" style="color:#b91c1c;">&times;</button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else : ?>
                                <div style="font-size:12px;color:#94a3b8;font-style:italic;">Belum ada sub-menu (tambahkan lewat form di atas).</div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </form>

            <form method="post" id="pa-mm-del-form" style="display:none;">
                <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                <input type="hidden" name="pa_sinova_action" value="mm_delete_item">
                <input type="hidden" name="mm_delete_id" id="pa-mm-del-id" value="0">
            </form>
            <script>
            (function(){
                document.querySelectorAll('.pa-mm-del-btn').forEach(function(btn){
                    btn.addEventListener('click', function(){
                        var t = btn.getAttribute('data-title') || 'item ini';
                        if (!confirm('Hapus menu "' + t + '"?')) return;
                        document.getElementById('pa-mm-del-id').value = btn.getAttribute('data-id') || '0';
                        document.getElementById('pa-mm-del-form').submit();
                    });
                });
            })();
            </script>
        </div>
        <?php
    }
}
