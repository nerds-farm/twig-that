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
        add_filter('render_block',  [$this, '_render_block'], 10, 3 );
        //add_filter('pre_render_block', [$this, '_pre_render_block'], 99, 3);
        //add_filter('render_block_context', [$this, '_render_block_context'], 99, 3);
        //add_filter('render_block_data', [$this, '_render_block_data'], 10, 3);
        //add_filter('render_block_core/query', [$this, '_render_block_core_query'], 10, 2);
    }
    
    public function _render_block_context($block_content, $block) {
        //var_dump($block); die();
        return $block_content;
    }  
    public function _render_block_core_query($block_content, $block) {
        //var_dump($block); die();
        return $block_content;
    }  

    public function _pre_render_block($pre_render, $parsed_block, $parent_block) {
        //var_dump($parsed_block); die();
        return $pre_render;
    }   
    
    public function _render_block($block_content, $parsed_block, $block) {
        global $post;
        $original = $post;
        if (!empty($block->context['postId'])) {
            $post = get_post($block->context['postId']);
        }
        $block_content = apply_filters('twig/that', $block_content, $post, 'post');
        $post = $original;
        return $block_content;
    }
    
    public function _render_block_data($parsed_block, $source_block, $parent_block) {
        //var_dump($parsed_block);die();
        //var_dump($source_block);
        $parsed_block = $this->recursive_twig($parsed_block);
        return $parsed_block;
    }   
    
    public function recursive_twig($parsed_block, $key = null) {
        if (is_array($parsed_block)) {
            foreach($parsed_block as $key => $value) {
                $parsed_block[$key] = $this->recursive_twig($value, $key);
            }
        } else {
            //var_dump($key);
            if (in_array($key, ['blockName'])) { // innerHTML is managed by render hook
                $parsed_block = apply_filters('twig/that', $parsed_block);
            }
        }
        return $parsed_block;
    }

}