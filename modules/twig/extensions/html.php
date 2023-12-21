<?php

namespace TwigThat\Modules\Twig\Extensions;

use TwigThat\Core\Utils;
use TwigThat\Modules\Twig\Twig;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

/**
 * Html Widget
 *
 * @since 1.0.1
 */
class Html {

    public function __construct() {
        add_filter( 'elementor/widget/render_content', [$this, 'do_twig'], 10, 2 );
    }
    
    /**
 * Filters heading widgets and change their content.
 *
 * @since 1.0.0
 * @param string                 $widget_content The widget HTML output.
 * @param \Elementor\Widget_Base $widget         The widget instance.
 * @return string The changed widget content.
 */
    public function do_twig($widget_content, $widget) {
        if (in_array($widget->get_name(), ['html', 'include', 'heading', 'e-include-file'])) {
            $widget_content = apply_filters('thig/that', $widget_content);
        }
        return $widget_content;
    }   

}