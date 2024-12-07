<?php
/*
Plugin Name: Video Downloader
Description: A WordPress plugin for downloading videos from various platforms. Use shotcode [video_downloader]
Version: 1.0
Author: Muhammad Russell
Author URI: https://muhammadrussell.com
*/

// Enqueue necessary JavaScript and CSS
function vd_enqueue_scripts() {
    wp_enqueue_script('vd-ajax-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), null, true);
    wp_enqueue_style('vd-style', plugin_dir_url(__FILE__) . 'style.css');
    wp_localize_script('vd-ajax-script', 'vd_ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));
}
add_action('wp_enqueue_scripts', 'vd_enqueue_scripts');

// Create a shortcode to display the input field and button
function vd_video_downloader_shortcode() {
    ob_start();
    ?>
    <div class="vd-container">
        <div id="vd-input-container">
            <input type="text" id="vd-url" placeholder="Enter post URL">
            <button id="vd-submit">Download</button>
        </div>
        <div id="vd-result-container"></div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('video_downloader', 'vd_video_downloader_shortcode');

// Handle AJAX request to fetch video formats
function vd_get_video_formats() {
    if (isset($_POST['url'])) {
        $url = sanitize_text_field($_POST['url']);

        // Call the Python API to get data
        $response = wp_remote_post('https://video-downloader-427824826532.us-central1.run.app/get-formats', array(
            'method'    => 'POST',
            'body'      => json_encode(array('url' => $url)),
            'headers'   => array('Content-Type' => 'application/json')
        ));

        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => 'Failed to fetch formats.'));
        }

        $body = wp_remote_retrieve_body($response);
        $formats = json_decode($body, true);

        if (!empty($formats['formats'])) {
            wp_send_json_success(array(
                'formats' => $formats['formats'],
                'title' => $formats['title'] ?? 'Unknown Title',
                'platform' => $formats['platform'] ?? 'Unknown Platform',
                'thumbnail' => $formats['thumbnail'] ?? ''
            ));
        } else {
            wp_send_json_error(array('message' => 'No formats found.'));
        }
    } else {
        wp_send_json_error(array('message' => 'URL is required.'));
    }
}
add_action('wp_ajax_vd_get_video_formats', 'vd_get_video_formats');
add_action('wp_ajax_nopriv_vd_get_video_formats', 'vd_get_video_formats');

//Handle AJAX request to convert and download video
function vd_convert_video() {
    if (isset($_POST['url']) && isset($_POST['format_id'])) {
        $url = sanitize_text_field($_POST['url']);
        $format_id = sanitize_text_field($_POST['format_id']);

        // Call the Python API for conversion
        $response = wp_remote_post('https://video-downloader-427824826532.us-central1.run.app/convert', array(
            'method'    => 'POST',
            'body'      => json_encode(array('url' => $url, 'format_id' => $format_id)),
            'headers'   => array('Content-Type' => 'application/json'),
            'timeout'   => 300,
        ));

        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => 'Failed to contact the server.'));
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        if (!empty($result['download_url'])) {
            wp_send_json_success(array(
                'download_url' => $result['download_url']
            ));
        } else {
            $error_message = $result['error'] ?? 'Conversion failed. Try again or select a lesser resolution.';
            wp_send_json_error(array('message' => $error_message));
        }
    } else {
        wp_send_json_error(array('message' => 'URL and Format ID are required.'));
    }
}
add_action('wp_ajax_vd_convert_video', 'vd_convert_video');
add_action('wp_ajax_nopriv_vd_convert_video', 'vd_convert_video');
