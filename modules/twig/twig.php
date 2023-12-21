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
        add_filter('twig/that', [$this, 'filter_twig'], 10, 3);

        // prevent fatal error
        add_action('elementor/editor/after_enqueue_scripts', [$this, 'enqueue_editor_assets']);

        // ADD more twig filters
        add_action('timber/twig/filters', array($this, 'add_timber_filters'));
        add_filter('timber/twig/functions', array($this, 'add_timber_functions'));
        //add_action( 'timber/twig/escapers', array( $this, 'add_timber_escapers' ) );
        //add_action( 'after_setup_theme', [$this, 'theme_add_woocommerce_support'] );
    }

    public function theme_add_woocommerce_support() {
        add_theme_support('woocommerce');
    }

    /**
     * Enqueue admin styles
     *
     * @since 1.0.1
     *
     * @access public
     */
    public function enqueue_editor_assets() {
        wp_enqueue_style('editor-twig');
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

        global $wp_query, $wp;
        global $post;
        global $e_form;
        global $epdf;
        global $e_widget_query;

        if ($var && !empty($data)) {
            $data = array($var => $data);
        }
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                if (in_array($key, self::$objects)) {
                    $obj_id = $this->get_id($value);
                    if ($obj_id) {
                        if ('posts' == $key) {
                            $data[$key] = new \Timber\PostQuery($wp_query);
                        } else {
                            $class = '\Timber\\' . ucfirst($key);
                            $data[$key] = $class::build($value);
                        }
                    }
                }
            }
        }

        //echo '<pre>'; var_dump($wp);var_dump($wp_query); echo '</pre>';
        $data['wp'] = $wp;

        $data['wp_query'] = $wp_query;
        $data['queried_object'] = get_queried_object();

        //$author_id = get_the_author_meta('ID');
        global $authordata;
        if ($authordata) {
            $data['author'] = \Timber\User::build($authordata);
        }

        if ($var != 'form' && !empty($e_form)) {
            $data['form'] = $e_form;
        }

        if ($var != 'block' && !empty($e_widget_query)) {
            if (!empty($e_widget_query->current_data)) {
                $data['block'] = $e_widget_query->current_data;
            }
        }

        if (!empty($epdf)) {
            $data['pdf'] = $epdf;
        }

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
            $data['wc'] = WC();
        }
        if (empty($data['query'])) {
            global $e_widget_query;
            if (!empty($e_widget_query)) {
                $data['query'] = $e_widget_query;
            }
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
                if (empty($data[$aobj])) {
                    //var_dump($aobj);
                    switch ($aobj) {
                        case 'menu':
                            global $_wp_registered_nav_menus;
                            if ($_wp_registered_nav_menus) {
                                foreach ($_wp_registered_nav_menus as $mkey => $mname) {
                                    $name = str_replace('-', '_', $mkey);
                                    $menu = wp_get_nav_menu_object($mkey);
                                    //$menu  = get_term_by( 'slug', $mkey, 'nav_menu' );
                                    //var_dump($menu);var_dump($mkey); var_dump($mname);
                                    if ($menu) {
                                        $data[$aobj][$mkey] = $class::build($menu);
                                        $data[$aobj][$name] = $class::build($menu);
                                    }
                                }
                            }
                            break;
                        case 'post':
                            // Timber automatically setup the Post with Queried Object ID, so I may change this behavior
                            $post = get_post();
                            if ($post && get_queried_object() && $post->ID != get_queried_object_id() && get_class(get_queried_object()) == 'WP_Post') {
                                $post = get_queried_object();
                            }
                            $data[$aobj] = \Timber\Post::build($post);    
                            break;
                        case 'user':
                            $user = wp_get_current_user();
                            if ($user) {
                                $data[$aobj] = $class::build($user);
                            }
                            break;
                        case 'term':
                            if (is_object(get_queried_object())) {
                                if (get_class(get_queried_object()) == 'WP_Term') {
                                   $data[$aobj] = $class::build(get_queried_object());
                                }
                            }
                            break;
                        default:
                            $data[$aobj] = new $class();
                    }
                }
            }
        }

        $data['system'] = array(
            'get' => $_GET,
            'post' => $_POST,
            'request' => $_REQUEST,
            'cookie' => $_COOKIE,
            'server' => $_SERVER,
            'files' => $_FILES,
        );
        if (!empty($_SESSION)) {
            $data['session'] = $_SESSION;
        }

        if (empty(\Timber\ImageHelper::$home_url)) {
            \Timber\Twig::init();
            \Timber\ImageHelper::init();
            //\Timber\Admin::init();
            //$integrations = new \Timber\Integrations();
            //$integrations->maybe_init_integrations();
            \Timber\Timber::init_integrations();
            //define('E_TIMBER_LOADED', true);
        }

        if (!empty($GLOBALS)) {
            foreach ($GLOBALS as $gkey => $gvalue) {
                if (empty($data[$gkey])) {
                    $data[$gkey] = $gvalue;
                }
            }
        }

        $data = apply_filters('twig-that/data', $data);
        //return $sanitize_string;
        $sanitize_string = str_replace('&gt;', '>', $sanitize_string);
        $sanitize_string = str_replace('&lt;', '<', $sanitize_string);
        //var_dump($data);// die();
        return \Timber\Timber::compile_string($sanitize_string, $data);
    }

    public static function sanitize_string($string = '') {
        if (is_string($string)) {
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
        }
        return $string;
    }

    /**
     * Adds functions to Twig.
     *
     * @param \Twig\Environment $twig The Twig Environment.
     * @return \Twig\Environment
     */
    public function add_timber_functions($functions) {
        $templatefunctions = [
            'wp_nav_menu' => 'wp_nav_menu',
            'get_adjacent_post_link' => 'get_adjacent_post_link',
        ];
        foreach ($templatefunctions as $fkey => $filter) {
            $functions[$fkey] = [
                'callable' => $filter,
            ];
        }
        return $functions;
    }

    /**
     * Adds filters to Twig.
     *
     * @param \Twig\Environment $twig The Twig Environment.
     * @return \Twig\Environment
     */
    public function add_timber_filters($filters) {

        //https://codex.wordpress.org/Function_Reference
        $sanitizers = [
            'sanitize_html_class' => 'sanitize_html_class',
            'sanitize_text_field' => 'sanitize_text_field',
            'sanitize_title' => 'sanitize_title',
            'sanitize_key' => 'sanitize_key',
        ];
        foreach ($sanitizers as $fkey => $filter) {
            $filters[$fkey] = [ 'callable' => $filter ];
        }

        $commons = [
            'get_permalink' => 'get_permalink',
            'permalink' => 'get_permalink',
            'link' => 'get_permalink',
            'strtolower' => 'strtolower',
            'strtoupper' => 'strtoupper',
            'maybe_unserialize' => 'maybe_unserialize',
        ];
        foreach ($commons as $fkey => $filter) {
            $filters[$fkey] = [ 'callable' => $filter ];
        }
        
        $customs = [
            'json_decode' => [ 'callable' => function ($arr) { return json_decode($arr, true); } ],
            //'get_term' => [ 'callable' => function ($arr) { return Utils::get_term($arr); } ],
            //'to_string' => [ 'callable' => function ($arr) { return Utils::to_string($arr); } ],
            'var_dump' => [ 'callable' => function ($arr) { return '<pre>' . var_export($arr, true) . '</pre>'; } ],
            //'acf_date' => [ 'callable' => function ($arr) { return Utils::maybe_date_convert($arr); } ],
            //'form_options' => [ 'callable' => function ($arr) { return \TwigThat\Core\Utils\Form::array_to_options($arr, 'pro', $format); } ],
            'timber_post' => [ 'callable' => function ($post) {
                            if (intval($post)) {
                                $post = get_post($post_id); 
                            }
                            if ($post && is_object($post) && get_class($post) == 'WP_Post') {
                                return \Timber\Post::build($post);
                            }
                            return $post;
                        } ],     
        ];
 
        foreach ($customs as $fkey => $filter) {
            $filters[$fkey] = $filter;
        }

        return $filters;
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
