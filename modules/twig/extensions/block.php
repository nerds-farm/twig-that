<?php

namespace TwigThat\Modules\Twig\Extensions;

use TwigThat\Core\Utils;
use TwigThat\Modules\Twig\Twig;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

/**
 * Gutenberg Block
 *
 * @since 1.0.1
 */
class Block {

    public function __construct() {
        //add_filter('pre_render_block', [$this, '_pre_render_block'], 10, 3);
        add_filter('render_block_data', [$this, '_render_block_data'], 10, 3);
    }

    public function _pre_render_block($pre_render, $parsed_block, $parent_block) {
        //var_dump($parsed_block);
        //$pre_render = Twig::do_twig($parsed_block);
        return $pre_render;
    }   
    public function _render_block_data($parsed_block, $source_block, $parent_block) {
        //var_dump($parsed_block);
        //var_dump($source_block);
        //var_dump($parent_block);
        $parsed_block = $this->recursive_twig($parsed_block);
        return $parsed_block;
    }   
    
    public function recursive_twig($parsed_block) {
        if (is_array($parsed_block)) {
            foreach($parsed_block as $key => $value) {
                $parsed_block[$key] = $this->recursive_twig($value);
            }
        } else {
            $parsed_block = apply_filters('thig/that', $parsed_block);
        }
        return $parsed_block;
    }

}