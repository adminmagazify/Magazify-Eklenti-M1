<?php
/*
Plugin Name: magazify-eklenti-m1
Description: boş eklenti deneme
Version: 2.0
Author: Magazify
* GitHub Plugin URI: https://github.com/adminmagazify/Magazify-Eklenti-M1
*/

require plugin_dir_path(__FILE__) . 'plugin-update-checker-master/plugin-update-checker.php';

$updateChecker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
    'https://github.com/adminmagazify/Magazify-Eklenti-M1',
    __FILE__,
    'magazify-eklenti-m1' // Eklenti klasör adı
);

$updateChecker->setBranch('main');

// Admin menü
add_action('admin_menu', function() {
    add_options_page('Merkezi İçerik Yönetimi', 'Merkezi İçerik Yönetimi', 'manage_options', 'merkezi-icerik-yonetimi', 'merkezi_icerik_ayar_sayfasi');
});

// Transient temizleme
add_action('update_option_icerikler', function() {
    delete_transient('icerikler_cache');
});

// CF7 Mail override - BU FONKSİYON EKLENDİ
add_filter('wpcf7_mail_components', 'merkezi_icerik_cf7_mail_override', 10, 3);
function merkezi_icerik_cf7_mail_override($components, $contact_form, $instance) {
    $to = get_option('merkezi_icerik_global_to_email');
    $from = get_option('merkezi_icerik_global_from_email');

    // Debug için log (opsiyonel)
    // error_log("CF7 Global Mail - To: $to, From: $from");

    if (!empty($to)) {
        $components['recipient'] = $to;
    }
    if (!empty($from)) {
        $components['sender'] = $from;
    }

    // Subject formdan gelir - değiştirilmez
    return $components;
}

// Ayar sayfası
function merkezi_icerik_ayar_sayfasi() {
    // Tab kontrolü - PHP tab sistemi
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'icerikler';
    
    $icerikler = get_option('icerikler', []);
    
    // Ayarları kaydetme
    if (isset($_POST['merkezi_icerik_submit'])) {
        check_admin_referer('merkezi_icerik_kaydet');
        
        if ($active_tab == 'icerikler') {
            // İçerikleri kaydet
            $yeni_icerikler = isset($_POST['icerikler']) ? array_map('wp_kses_post', $_POST['icerikler']) : [];
            update_option('icerikler', array_values($yeni_icerikler));
            $icerikler = $yeni_icerikler;
            echo '<div class="updated"><p>İçerik ayarları kaydedildi.</p></div>';
        } elseif ($active_tab == 'mail') {
            // Mail ayarlarını kaydet
            if (isset($_POST['merkezi_icerik_global_to_email'])) {
                $to_email = sanitize_email($_POST['merkezi_icerik_global_to_email']);
                update_option('merkezi_icerik_global_to_email', $to_email);
            }
            if (isset($_POST['merkezi_icerik_global_from_email'])) {
                $from_email = sanitize_text_field($_POST['merkezi_icerik_global_from_email']);
                update_option('merkezi_icerik_global_from_email', $from_email);
            }
            echo '<div class="updated"><p>Mail ayarları kaydedildi.</p></div>';
        }
    }
    
    // Mevcut mail ayarları
    $global_to_email = get_option('merkezi_icerik_global_to_email', '');
    $global_from_email = get_option('merkezi_icerik_global_from_email', '');

    ?>
    <div class="wrap">
        <h1>Merkezi İçerik Yönetimi</h1>
        
        <h2 class="nav-tab-wrapper">
            <a href="?page=merkezi-icerik-yonetimi&tab=icerikler" class="nav-tab <?php echo $active_tab == 'icerikler' ? 'nav-tab-active' : ''; ?>">İçerik Yönetimi</a>
            <a href="?page=merkezi-icerik-yonetimi&tab=mail" class="nav-tab <?php echo $active_tab == 'mail' ? 'nav-tab-active' : ''; ?>">CF7 Mail Ayarları</a>
        </h2>
        
        <?php if ($active_tab == 'icerikler'): ?>
        <form method="post">
            <?php wp_nonce_field('merkezi_icerik_kaydet'); ?>
            
            <div class="tab-content">
                <h3>İçerik Yönetimi</h3>
                <p>Oluşturduğunuz içerikleri kısa kodlarla sitenizin herhangi bir yerinde kullanabilirsiniz.</p>
                
                <div id="icerikler-wrapper">
                    <?php if (empty($icerikler)): ?>
                        <p>Henüz içerik eklenmemiş. Aşağıdaki butona tıklayarak ilk içeriğinizi ekleyin.</p>
                    <?php else: ?>
                        <?php foreach ($icerikler as $i => $icerik): ?>
                            <div class="icerik-blok">
                                <h3>İçerik <?php echo $i + 1; ?></h3>
                                <p style="margin: 0 0 6px; font-size:13px; font-weight: 500;">Kısa Kod: <code>[icerik id="<?php echo $i + 1; ?>"]</code></p>
                                <?php wp_editor($icerik, 'icerikler_' . $i, [
                                    'textarea_name' => 'icerikler[' . $i . ']',
                                    'textarea_rows' => 5,
                                    'media_buttons' => true,
                                ]); ?>
                                <p><button type="button" class="button remove-icerik" data-index="<?php echo $i; ?>">Sil</button></p>
                                <hr>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <p><button type="button" class="button" id="icerik-ekle">+ Yeni İçerik</button></p>
                
                <p><input type="submit" name="merkezi_icerik_submit" class="button button-primary" value="İçerikleri Kaydet"></p>
            </div>
        </form>
        
        <script>
        // Basit JavaScript - AJAX kullanmadan
        document.getElementById('icerik-ekle').addEventListener('click', function() {
            const wrapper = document.getElementById('icerikler-wrapper');
            const count = wrapper.querySelectorAll('.icerik-blok').length;
            
            // Yeni içerik bloğu oluştur
            const newBlock = document.createElement('div');
            newBlock.className = 'icerik-blok';
            newBlock.innerHTML = `
                <h3>İçerik ${count + 1}</h3>
                <p style="margin: 0 0 6px; font-size:13px; font-weight: 500;">Kısa Kod: <code>[icerik id="${count + 1}"]</code></p>
                <textarea name="icerikler[${count}]" rows="5" class="widefat" placeholder="İçeriğinizi buraya yazın..."></textarea>
                <p><button type="button" class="button remove-icerik" data-index="${count}">Sil</button></p>
                <hr>
            `;
            
            wrapper.appendChild(newBlock);
            
            // Eğer "henüz içerik yok" mesajı varsa kaldır
            const noContentMsg = wrapper.querySelector('p');
            if (noContentMsg && noContentMsg.textContent.includes('Henüz içerik eklenmemiş')) {
                noContentMsg.remove();
            }
        });

        // İçerik silme
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-icerik')) {
                if (!confirm('Bu içeriği silmek istediğinizden emin misiniz?')) {
                    return;
                }
                
                e.target.closest('.icerik-blok').remove();
                
                // Formu yeniden yüklemeden ID'leri güncelle
                const wrapper = document.getElementById('icerikler-wrapper');
                const blocks = wrapper.querySelectorAll('.icerik-blok');
                
                blocks.forEach((block, index) => {
                    const h3 = block.querySelector('h3');
                    const code = block.querySelector('code');
                    const textarea = block.querySelector('textarea');
                    const removeBtn = block.querySelector('.remove-icerik');
                    
                    h3.textContent = `İçerik ${index + 1}`;
                    code.textContent = `[icerik id="${index + 1}"]`;
                    textarea.name = `icerikler[${index}]`;
                    removeBtn.setAttribute('data-index', index);
                });
                
                // Eğer tüm içerikler silindiyse mesaj göster
                if (blocks.length === 0) {
                    wrapper.innerHTML = '<p>Henüz içerik eklenmemiş. Aşağıdaki butona tıklayarak ilk içeriğinizi ekleyin.</p>';
                }
            }
        });
        </script>

        <?php elseif ($active_tab == 'mail'): ?>
        <form method="post">
            <?php wp_nonce_field('merkezi_icerik_kaydet'); ?>
            
            <div class="tab-content">
                <h3>Contact Form 7 Global Mail Ayarları</h3>
                <p>Tüm Contact Form 7 formları için merkezi mail ayarları. <strong>Not:</strong> Konu başlıkları formdan gelir ve değiştirilmez.</p>
                
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Global Kime (To)</th>
                        <td>
                            <input type="email" name="merkezi_icerik_global_to_email" value="<?php echo esc_attr($global_to_email); ?>" class="regular-text" placeholder="iletisim@magazac.com" />
                            <p class="description">Tüm formlardaki "Kime" alanını bu adresle değiştirir</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Global Kimden (From)</th>
                        <td>
                            <input type="text" name="merkezi_icerik_global_from_email" value="<?php echo esc_attr($global_from_email); ?>" class="regular-text" placeholder="Tasarım Store <iletisim@tasarim.store>" />
                            <p class="description">Tüm formlardaki "Kimden" alanını bu adresle değiştirir<br>Örnek: Site Adı &lt;email@site.com&gt;</p>
                        </td>
                    </tr>
                </table>
                
                <div class="notice notice-info">
                    <p><strong>Çalışan Formlar:</strong> Genel Talep Formu, Haber Formu, İletişim Formu, Koleksiyon Ekleme Formu, Tasarım Silme Formu, Tasarım Yükleme Formu, Ürün Fiyat Belirleme, Ürün Güncelleme Formu, Ürün Listeleme Formu, Ürün Silme Formu</p>
                </div>
                
                <div class="notice notice-warning">
                    <p><strong>Önemli:</strong> Bu ayarlar kaydedildikten sonra tüm Contact Form 7 formları bu mail adreslerini kullanacaktır. Formların kendi mail ayarları override edilecektir.</p>
                </div>
                
                <p><input type="submit" name="merkezi_icerik_submit" class="button button-primary" value="Mail Ayarlarını Kaydet"></p>
            </div>
        </form>
        <?php endif; ?>
    </div>

    <style>
    .icerik-blok { 
        background: #f9f9f9; 
        padding: 15px; 
        margin: 15px 0; 
        border: 1px solid #ddd; 
        border-radius: 4px; 
    }
    .nav-tab-active { 
        background: #fff; 
        border-bottom: 1px solid #fff; 
    }
    .tab-content {
        background: #fff;
        padding: 20px;
        border: 1px solid #ccd0d4;
        border-top: none;
    }
    </style>
    <?php
}

// Kısa kod: [icerik id="1"]
add_shortcode('icerik', function($atts) {
    $atts = shortcode_atts(['id' => '1'], $atts);
    $id = intval($atts['id']) - 1; // ID'yi 0 tabanlına çevir
    
    // Transient cache kullan
    $icerikler = get_transient('icerikler_cache');
    
    if ($icerikler === false) {
        $icerikler = get_option('icerikler', []);
        set_transient('icerikler_cache', $icerikler, HOUR_IN_SECONDS);
    }
    
    if (isset($icerikler[$id])) {
        return do_shortcode($icerikler[$id]);
    }
    
    return '<small>İçerik bulunamadı (ID: ' . esc_html($atts['id']) . ')</small>';
});

// Plugin etkinleştirildiğinde transient'i temizle
register_activation_hook(__FILE__, function() {
    delete_transient('icerikler_cache');
});

// Plugin kaldırıldığında temizlik
register_uninstall_hook(__FILE__, 'merkezi_icerik_temizlik');

function merkezi_icerik_temizlik() {
    delete_option('icerikler');
    delete_option('icerikler_silinen');
    delete_option('merkezi_icerik_global_to_email');
    delete_option('merkezi_icerik_global_from_email');
    delete_transient('icerikler_cache');
}