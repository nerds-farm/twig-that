<?php

namespace TwigThat\Modules\Twig\Extensions;

use TwigThat\Core\Utils;
use TwigThat\Modules\Twig\Twig;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

/**
 * Elementor PRO Custom Code Snippet
 *
 * @since 1.0.1
 */
class Custom_Code {

    public function __construct() {        
        $this->add_actions();
    }

    public function get_icon() {
        return 'eicon-code';
    }

    /**
     * Add Actions
     *
     * @access private
     */
    protected function add_actions() {
        if (!is_admin()) {
            if (defined('ELEMENTOR_PRO_VERSION')) {
                if (class_exists('\ElementorPro\Modules\CustomCode\Module')) {
                    add_filter('get_post_metadata', [$this, 'get_post_meta'], 10, 5);
                }
            }
        }
    }
    
    public function get_post_meta($check, $object_id, $meta_key, $single, $meta_type) {
        $needle = '_elementor_code';
        if ( substr($meta_key, -strlen($needle)) === $needle ) {
            
            remove_filter( 'get_post_metadata', [$this, 'get_post_meta'], 10 );
            $code = get_post_meta( $object_id, $meta_key, $single );
            add_filter('get_post_metadata', [$this, 'get_post_meta'], 10, 5);
            
            $check = apply_filters('thig/that', $code); // TWIG REPLACE
        }
        return $check;
    }

}