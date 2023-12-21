<?php

namespace TwigThat\Modules\Twig\Extensions;

use TwigThat\Core\Utils;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

/**
 * Elementor PRO Custom CSS
 *
 * @since 1.0.1
 */
class Custom_Css {

    public function __construct() {
        if (defined('ELEMENTOR_PRO_VERSION')) {
            add_action( 'elementor/init', [$this, 'add_actions'], 0, 99 );
        }
        //$this->add_actions();
    }

    public function get_icon() {
        return 'eicon-custom-css';
    }

    /**
     * Add Actions
     *
     * @access private
     */
    public function add_actions() {            
        if (class_exists('\ElementorPro\Modules\CustomCss\Module')) {
            $module = \ElementorPro\Modules\CustomCss\Module::instance();
            remove_action( 'elementor/element/parse_css', [ $module, 'add_post_css' ] );
            add_action('elementor/element/parse_css', [$this, 'add_post_css'], 10, 2);
        }
    }
    
    /**
     * @param $post_css Post
     * @param $element  Element_Base
     */
    public function add_post_css($post_css, $element) {
        if ($post_css instanceof \Elementor\Core\DynamicTags\Dynamic_CSS) {
            return;
        }

        $element_settings = $element->get_settings();

        if (empty($element_settings['custom_css'])) {
            return;
        }

        $css = trim($element_settings['custom_css']);

        if (empty($css)) {
            return;
        }
        $css = str_replace('selector', $post_css->get_element_unique_selector($element), $css);

        // Add a css comment
        $css = sprintf('/* Start custom CSS for %s, class: %s */', $element->get_name(), $element->get_unique_selector()) . $css . '/* End custom CSS */';
        
        $css = apply_filters('thig/that', $css); // TWIG REPLACE
        
        $post_css->get_stylesheet()->add_raw_css($css);
    }

}