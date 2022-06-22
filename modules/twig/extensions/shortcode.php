<?php

namespace TwigThat\Modules\Twig\Extensions;

use TwigThat\Core\Utils;
use TwigThat\Modules\Twig\Twig;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

/**
 * Shortcode
 *
 * @since 1.0.1
 */
class Shortcode {

    public function __construct() {
        //add_filter( 'pre_do_shortcode_tag', [$this, 'add_twig_to_widget_text'], 10, 3);
    }
    
    public function add_twig_to_widget_text($tag, $attr, $m) {
        return Twig::do_twig($tag);
    }   

}