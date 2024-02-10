<?php

/**
 *
 * @wordpress-plugin
 * Plugin Name:       Twig That
 * Plugin URI:        https://nerds.farm
 * Description:       Exploit the limitless potentials of Wordpress, add dynamic content on all your pages with the semplicity of Twig placeholders.
 * Version:           1.0.1
 * Author:            Nerds Farm
 * Author URI:        https://nerds.farm/twig-that
 * Text Domain:       twig-that
 * License:           GPL-3.0
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * Twig That is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * Twig That is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('TWIG_THAT_URL', plugins_url(DIRECTORY_SEPARATOR, __FILE__));
define('TWIG_THAT_PATH', str_replace('/', DIRECTORY_SEPARATOR, plugin_dir_path(__FILE__)));

/**
 * Load plugin
 *
 * @since 1.0.1
 */
function twig_that_load_plugin() {
    // Load localization file
    load_plugin_textdomain('twig-that');
    
    // Require the main plugin file
    require_once( __DIR__ . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'plugin.php' );
    $plugin = \TwigThat\Plugin::instance();
    do_action('twig-that/loaded');
}
add_action('plugins_loaded', 'twig_that_load_plugin');


/*
$wp_uploads_dir = wp_get_upload_dir();
$autoload = $wp_uploads_dir['basedir'].DIRECTORY_SEPARATOR.'blocks'.DIRECTORY_SEPARATOR.'autoload.php';
$autoload = str_replace('/', DIRECTORY_SEPARATOR, $autoload);
include_once($autoload);
*/