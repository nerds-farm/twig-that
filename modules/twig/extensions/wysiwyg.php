<?php

namespace TwigThat\Modules\Twig\Extensions;

use TwigThat\Core\Utils;
use TwigThat\Modules\Twig\Twig;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

/**
 * Wysiwyg editor
 *
 * @since 1.0.1
 */
class Wysiwyg {

    public function __construct() {
        add_filter('widget_text', [$this, 'add_twig_to_widget_text']);
    }
    
    public function add_twig_to_widget_text($text) {
        return apply_filters('thig/that', $text);
    }   

}