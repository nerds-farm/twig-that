<?php

namespace TwigThat\Modules\Twig;

use TwigThat\Core\Utils;
use TwigThat\Base\Module_Base;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Twig extends Module_Base {

    public static $objects = array('post', 'user', 'term', 'theme', 'site', 'menu', 'posts');

    /**
     * Twig constructor.
     *
     * @since 1.0.1
     * @param array $args
     */
    public function __construct() {
        parent::__construct();

        // ADD more twig filters
        add_action('timber/twig/filters', array($this, 'add_timber_filters'));
        //add_action('timber/twig/functions', array($this, 'add_timber_functions'));
        //add_action( 'timber/twig/escapers', array( $this, 'add_timber_escapers' ) );
        //add_action( 'after_setup_theme', [$this, 'theme_add_woocommerce_support'] );
    }

    public function theme_add_woocommerce_support() {
        add_theme_support('woocommerce');
    }

    public function filter_twig($value = '', $data = array(), $var = '') {
        $value = self::do_twig($value, $data, $var);
        return $value;
    }

    public static function do_twig($string = '', $data = array(), $var = '') {

        /* if (strpos(string, '{{comment') !== false) {
         * // use post.comments
          $data['comment'] = new \Timber\Comment(1);
          } */


        $sanitize_string = self::sanitize_string($string);
        if (!$sanitize_string) {
            return $string;
        }

        global $wp_query;
        global $post;

        if ($var && !empty($data)) {
            $data = array($var => $data);
        }
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                if (in_array($key, self::$objects)) {
                    $obj_id = $this->get_id($value);
                    if ($obj_id) {
                        $class = '\Timber\\' . ucfirst($key);
                        if ('posts' == $key) {
                            $class = '\Timber\PostQuery';
                        }
                        $data[$key] = new $class($obj_id);
                    }
                }
            }
        }

        $author_id = get_the_author_meta('ID');
        if ($author_id) {
            $data['author'] = new \Timber\User($author_id);
        }

        $data['wp_query'] = $wp_query;
        $data['queried_object'] = get_queried_object();

        if (class_exists( 'woocommerce' )) {
            global $product;
            if (empty($product)) {
                if ($post && $post->post_type == 'product') {
                    //timber_set_product($post);
                    if (is_woocommerce()) {
                        $product = wc_get_product($post->ID);
                    }
                }
            }
            $data['product'] = $product;
        }
        
        foreach (self::$objects as $aobj) {
            if (!empty($data[$aobj])) {
                continue;
            }
            if (strpos($string, '{{' . $aobj) !== false || strpos($string, '{{ ' . $aobj) !== false || strpos($string, '(' . $aobj . '.') !== false || strpos($string, ' in ' . $aobj . '.') !== false || strpos($string, 'if ' . $aobj . '.') !== false) {
                $class = '\Timber\\' . ucfirst($aobj);
                if ('posts' == $aobj) {
                    $class = '\Timber\PostQuery';
                }
                if ('menu' == $aobj) {
                    global $_wp_registered_nav_menus;
                    if( $_wp_registered_nav_menus ) {
                        foreach ( $_wp_registered_nav_menus as $mkey => $mname ) {
                            $name = str_replace( '-', '_', $mkey );
                            $data['menu'][$name] = new $class($mkey);
                        }
                    }
                } else { 
                    $data[$aobj] = new $class();
                }
            }
        }

        $data['system'] = array(
            'get' => $_GET,
            'post' => $_POST,
            'request' => $_REQUEST,
            'cookie' => $_COOKIE,
            'server' => $_SERVER,
        );
        if (!empty($_SESSION)) {
            $data['session'] = $_SESSION;
        }

        if (!defined('E_TIMBER_LOADED')) {
            \Timber\Twig::init();
            \Timber\ImageHelper::init();
            //\Timber\Admin::init();
            $integrations = new \Timber\Integrations();
            $integrations->maybe_init_integrations();
            define('E_TIMBER_LOADED', true);
        }

        $data = apply_filters('twig-that/data', $data);

        return \Timber\Timber::compile_string($sanitize_string, $data);
    }

    public static function sanitize_string($string = '') {

        // Echo
        $open = '{{';
        $close = '}}';
        $count_open = substr_count($string, $open);
        $count_close = substr_count($string, $close);

        // Loops
        $lopen = '{%';
        $lclose = '%}';
        $count_lopen = substr_count($string, $lopen);
        $count_lclose = substr_count($string, $lclose);

        // Comments
        $copen = '{#';
        $cclose = '#}';
        $count_copen = substr_count($string, $copen);
        $count_cclose = substr_count($string, $cclose);

        if (substr_count($string, '{{}}') || substr_count($string, '{{ }}')) {
            return false;
        }

        if ((!$count_open && !$count_lopen) || $count_open != $count_close || $count_lopen != $count_lclose || $count_copen != $count_cclose) {
            return false;
        }

        return $string;
    }

    /**
     * Adds functions to Twig.
     *
     * @param \Twig\Environment $twig The Twig Environment.
     * @return \Twig\Environment
     */
    public function add_timber_functions($twig) {
        $templatefunctions = [
            'wp_nav_menu' => 'wp_nav_menu',
            'get_adjacent_post_link' => 'get_adjacent_post_link',
        ];
        foreach ($templatefunctions as $filter) {
            $twig->addFunction(new \Timber\Twig_Function($filter, $filter));
        }
        return $twig;
    }

    /**
     * Adds filters to Twig.
     *
     * @param \Twig\Environment $twig The Twig Environment.
     * @return \Twig\Environment
     */
    public function add_timber_filters($twig) {

        //https://codex.wordpress.org/Function_Reference
        $sanitizers = [
            'sanitize_html_class' => 'sanitize_html_class',
            'sanitize_text_field' => 'sanitize_text_field',
            'sanitize_title' => 'sanitize_title',
            'sanitize_key' => 'sanitize_key',
        ];
        foreach ($sanitizers as $filter) {
            $twig->addFilter(new \Timber\Twig_Filter($filter, $filter));
        }

        $twig->addFilter(new \Timber\Twig_Filter('get_permalink', 'get_permalink'));
        $twig->addFilter(new \Timber\Twig_Filter('permalink', 'get_permalink'));
        $twig->addFilter(new \Timber\Twig_Filter('link', 'get_permalink'));

        $twig->addFilter(new \Timber\Twig_Filter('strtolower', 'strtolower'));
        $twig->addFilter(new \Timber\Twig_Filter('strtoupper', 'strtoupper'));

        $twig->addFilter(new \Timber\Twig_Filter('maybe_unserialize', 'maybe_unserialize'));
        $twig->addFilter(new \Timber\Twig_Filter('json_decode', function ($arr) {
                            return json_decode($arr, true);
                        }));

        $twig->addFilter(new \Timber\Twig_Filter('var_dump', function ($arr) {
                            return '<pre>' . var_export($arr, true) . '</pre>';
                        }));

        $twig->addFilter(new \Timber\Twig_Filter('acf_date', function ($arr) {
                            if (is_string($arr) && strlen($arr) == 8) {
                                $y = substr($arr, 0, 4);
                                $m = substr($arr, 4, 2);
                                $d = substr($arr, 6, 2);
                                return $y . '-' . $m . '-' . $d;
                            }
                            return $arr;
                        }));

        return $twig;
    }
    
    public function get_id($obj = null) {
        if (empty($obj)) {
            return get_the_ID();
        }
        if (filter_var($obj, FILTER_VALIDATE_URL)) {
            return url_to_postid($obj);
        }
        if (is_numeric($obj)) {
            return intval($obj);
        }
        if (is_string($obj)) {
            return intval($obj);
        }
        if (is_array($obj)) {
            if (!empty($obj['term_id'])) {
                return $obj['term_id'];
            }
            if (!empty($obj['comment_ID'])) {
                return $obj['comment_ID'];
            }
            if (!empty($obj['ID'])) {
                return $obj['ID'];
            }
        }
        if (is_object($obj)) {
            switch (get_class($obj)) {
                case 'WP_Post':
                    return $obj->ID;
                case 'WP_Term':
                    return $obj->term_id;
                case 'WP_User':
                    return $obj->ID;
                case 'WP_Comment':
                    return $obj->comment_ID;
            }
        }
        return false;
    }

}
