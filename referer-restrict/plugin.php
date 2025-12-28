<?php
/*
Plugin Name: Referer访问限制，Referer Access Restriction
Plugin URI: https://github.com/twsh0305/referer-restrict/
Description: 限制指定Referer来源的请求才能访问短链接，Restrict requests specifying the Referer origin to access short links
Version: 1.1
Author: twsh0305(天无神话)
Author URI: https://wxsnote.cn/
*/

// 防止直接访问
if (!defined('YOURLS_ABSPATH')) die();

// 加载翻译文件
yourls_add_action('plugins_loaded', 'referer_restrict_load_textdomain');
function referer_restrict_load_textdomain() {
    $domain = 'referer-restrict';
    $lang_dir = dirname(__FILE__) . '/languages';
    
    $locale = yourls_get_locale();
    if (empty($locale)) {
        error_log("未设置YOURLS_LANG，不加载翻译");
        return;
    }
    
    $mo_file = $lang_dir . '/' . $domain . '-' . $locale . '.mo';
    if (file_exists($mo_file) && is_readable($mo_file)) {
        $loaded = yourls_load_textdomain($domain, $mo_file);
        $loaded ? error_log("成功加载翻译文件: $mo_file") : error_log("翻译文件存在但加载失败: $mo_file");
    } else {
        error_log("翻译文件不存在或不可读: $mo_file");
    }
}

// 注册插件设置页面
yourls_add_action('plugins_loaded', 'referer_restrict_add_settings');
function referer_restrict_add_settings() {
    yourls_register_plugin_page('referer_restrict', yourls__('Referer Access Restriction', 'referer-restrict'), 'referer_restrict_settings_page');
}

// 显示设置页面
function referer_restrict_settings_page() {
    if (isset($_POST['allowed_referers'])) {
        yourls_verify_nonce('referer_restrict_settings', $_POST['nonce']);
        $allowed = trim($_POST['allowed_referers']);
        $allow_direct = isset($_POST['allow_direct']) ? 1 : 0;
        yourls_update_option('referer_restrict_allowed', $allowed);
        yourls_update_option('referer_restrict_allow_direct', $allow_direct);
        echo '<div class="updated"><p>' . yourls__('Settings saved', 'referer-restrict') . '</p></div>';
    }

    $allowed_referers = yourls_get_option('referer_restrict_allowed', '');
    $allow_direct = yourls_get_option('referer_restrict_allow_direct', 0);
    $nonce = yourls_create_nonce('referer_restrict_settings');
    $checked = $allow_direct ? 'checked' : '';

    $page_title = yourls__('Referer Access Restriction Settings', 'referer-restrict');
    $description = yourls__('Please enter allowed main domains (one per line, e.g.: example.com), subdomains are automatically included', 'referer-restrict');
    $save_btn = yourls__('Save Settings', 'referer-restrict');
    $tips = yourls__('Tips:', 'referer-restrict');
    $tips_text = yourls__('Leave blank to allow all sources', 'referer-restrict');
    $allow_direct_label = yourls__('Allow direct access (no Referer)', 'referer-restrict');
    $my_blog = yourls__('My blog:', 'referer-restrict');
    $mr_wang = yourls__('Mr.Wang\'s Notes', 'referer-restrict');
    $translation_tips = yourls__('<strong>About Translation:</strong> You can refer to the file <code>/referer-restrict/languages/referer-restrict-zh_CN.po</code> in the plugin directory, translate this file into your language, rename it to your regional language code, and place it in the plugin languages directory. For more details, please refer to the tutorial:', 'referer-restrict');
    $translation_course = yourls__('How to create your own translation file for YOURLS', 'referer-restrict');
    $attention = yourls__('<strong>Note:</strong> YOURLS has a built-in caching function. When testing, please use incognito mode, or develop a plugin that adds a timestamp to the URL to avoid cached states', 'referer-restrict');

    echo <<<HTML
    <div class="wrap">
        <h2>$page_title</h2>
        <p>$description</p>
            <form method="post">
                <input type="hidden" name="nonce" value="$nonce" />
                <textarea name="allowed_referers" rows="10" cols="50" style="width: 100%;">$allowed_referers</textarea>
                <p>
                    <label>
                        <input type="checkbox" name="allow_direct" value="1" {$checked} />
                        $allow_direct_label
                    </label>
                </p>
                <p><input type="submit" value="$save_btn" class="button-primary" /></p>
            </form>
        <p><strong>$tips</strong>$tips_text</p>
        <p><strong>$my_blog</strong><a href="https://wxsnote.cn/" target="_blank"><span style="color:blue;">$mr_wang</span></a></p>
        <p>$translation_tips<a href="https://yourls.org/blog/2013/02/workshop-how-to-create-your-own-translation-file-for-yourls" target="_blank"><span style="color:blue;">$translation_course</span></a></p>
        <p>$attention</p>
    </div>
HTML;
}


yourls_add_action('pre_redirect', 'referer_restrict_check');
function referer_restrict_check($args) {
    $location = $args[0];
    $allowed_referers = yourls_get_option('referer_restrict_allowed', '');
    $allow_direct = yourls_get_option('referer_restrict_allow_direct', 0);
    
    
    // 未设置限制时直接放行
    if (empty($allowed_referers)) {
        return;
    }

    $referer = yourls_get_referrer();
    $is_allowed = false;
    
    // 处理直接访问（无Referer）
    if ($referer === 'direct') {
        $is_allowed = $allow_direct;
    }

    // 标准化处理白名单域名
    $allowed_list = array_filter(array_map(function($domain){
        $domain = trim(strtolower($domain));
        $domain = preg_replace(['/^https?:\/\/(www\.)?/', '/:\d+$/'], '', $domain);
        return $domain ? $domain : false;
    }, explode("\n", $allowed_referers)));
    
    // 检查Referer域名匹配
    if (!$is_allowed && $referer !== 'direct') {
        $referer_host = parse_url($referer, PHP_URL_HOST);
        if ($referer_host) {
            $referer_host = strtolower(trim($referer_host));
            foreach ($allowed_list as $allowed_domain) {
                if ($referer_host === $allowed_domain || preg_match("/\.{$allowed_domain}$/", $referer_host)) {
                    $is_allowed = true;
                    break;
                }
            }
        }
    }

    // 不允许访问时显示403错误
    if (!$is_allowed) {
        $access_denied = yourls__('Access Denied', 'referer-restrict');
        $error_403 = yourls__('403 Access Denied', 'referer-restrict');
        $error_msg = yourls__('Your access source is not allowed to access this link', 'referer-restrict');
        
        yourls_status_header(403);
        echo <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <title>$access_denied</title>
            <style>
                body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
                .container { max-width: 600px; margin: 0 auto; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>$error_403</h1>
                <p>$error_msg</p>
            </div>
        </body>
        </html>
HTML;
        die();
    }
}
