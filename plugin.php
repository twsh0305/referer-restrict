<?php
/*
Plugin Name: 限制referer访问
Plugin URI: https://github.com/twsh0305/referer-restrict/
Description: 限制只有指定 Referer 来源的请求才能访问短链接
Version: 1.0
Author: 天无神话
Author URI: https://wxsnote.cn/
*/

// 防止直接访问
if (!defined('YOURLS_ABSPATH')) die();

// 注册插件设置页面
yourls_add_action('plugins_loaded', 'referer_restrict_add_settings');
function referer_restrict_add_settings() {
    yourls_register_plugin_page('referer_restrict', 'Referer 访问限制', 'referer_restrict_settings_page');
}

// 显示设置页面
function referer_restrict_settings_page() {
    // 处理表单提交
    if (isset($_POST['allowed_referers'])) {
        yourls_verify_nonce('referer_restrict_settings');
        $allowed = trim($_POST['allowed_referers']);
        yourls_update_option('referer_restrict_allowed', $allowed);
        echo '<div class="updated"><p>设置已保存</p></div>';
    }

    // 获取当前设置
    $allowed_referers = yourls_get_option('referer_restrict_allowed', '');
    $nonce = yourls_create_nonce('referer_restrict_settings');

    echo <<<HTML
    <div class="wrap">
        <h2>Referer 访问限制设置</h2>
        <p>请输入允许的 Referer 来源（每行一个域名，例如：example.com）</p>
        <form method="post">
            <input type="hidden" name="nonce" value="$nonce" />
            <textarea name="allowed_referers" rows="10" cols="50" style="width: 100%;">$allowed_referers</textarea>
            <p><input type="submit" value="保存设置" class="button-primary" /></p>
        </form>
        <p><strong>提示：</strong>留空表示允许所有来源访问，输入 "direct" 允许直接访问（无 Referer 的情况）</p>
        <p>我的博客：<a href="https://wxsnote.cn/" target="_blank"><span style="color:blue;">王先生笔记</span></a></p>
    </div>
HTML;
}

// 注册重定向前的检查钩子
yourls_add_action('pre_redirect', 'referer_restrict_check');
function referer_restrict_check($args) {
    $location = $args[0];
    $allowed_referers = yourls_get_option('referer_restrict_allowed', '');
    
    // 如果未设置限制，直接放行
    if (empty($allowed_referers)) {
        return;
    }

    // 获取当前请求的 Referer
    $referer = yourls_get_referrer();
    $allowed_list = explode("\n", strtolower($allowed_referers));
    $allowed_list = array_map('trim', $allowed_list);
    $allowed_list = array_filter($allowed_list);

    // 检查是否允许访问
    $is_allowed = false;
    
    // 处理直接访问（无 Referer）的情况
    if ($referer === 'direct' && in_array('direct', $allowed_list)) {
        $is_allowed = true;
    }
    
    // 检查 Referer 域名是否在允许列表中
    if (!$is_allowed && $referer !== 'direct') {
        $referer_host = parse_url($referer, PHP_URL_HOST);
        if ($referer_host) {
            $referer_host = strtolower($referer_host);
            foreach ($allowed_list as $allowed) {
                if ($allowed === $referer_host || strpos($referer_host, '.' . $allowed) !== false) {
                    $is_allowed = true;
                    break;
                }
            }
        }
    }

    // 如果不允许访问，显示错误信息
    if (!$is_allowed) {
        yourls_status_header(403);
        echo <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <title>访问被拒绝</title>
            <style>
                body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
                .container { max-width: 600px; margin: 0 auto; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>403 访问被拒绝</h1>
                <p>您的访问来源不被允许访问此链接</p>
            </div>
        </body>
        </html>
HTML;
        die(); // 终止执行，防止继续重定向
    }
}
