<?php

namespace TwigThat\Modules\Block;

use TwigThat\Core\Utils;
use TwigThat\Base\Module_Base;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Block extends Module_Base {

    use Traits\Type;
    use Traits\Metabox;
    use Traits\Attributes;
    use Traits\Pages;

    /**
     * Twig constructor.
     *
     * @since 1.0.1
     * @param array $args
     */
    public function __construct() {
        parent::__construct();

        add_action('init', [$this, '_init_type']);
        add_action('add_meta_boxes', [$this, 'meta_fields_add_meta_box']);
        add_action('save_post', [$this, 'meta_fields_save_meta_box_data'], 10, 3);
        
        // fix native assets url bug on win server
        add_filter( 'plugins_url', function($url, $path, $plugin) {
            $tmp = explode('/wp-content/', $url);
            if (count($tmp) == 3) {
                $url = reset($tmp).'/wp-content/'.end($tmp);
            }
            return $url;
        }, 10, 3 );

        // clean folder after block delete
        add_action('after_delete_post', function ($postid, $post) {
            // For a specific post type block
            if ('block' === $post->post_type) {
                if ($post->post_name) {
                    //delete block folder
                    $block_dir = $this->get_blocks_dir($post->post_name);
                    $block_post = $block_dir . DIRECTORY_SEPARATOR . str_replace('__trashed', '', $post->post_name);
                    $this->dir_delete($block_post);
                }
            }
        }, 10, 2);

        add_action('admin_menu', [$this, 'admin_menu_action']);

        // autoload blocks folder
        add_filter('twig/that/dirs', [$this, 'get_blocks_dirs']);

        // filter blocks
        add_filter('twig/that/blocks', function ($blocks) {
            foreach ($blocks as $key => $block) {
                $slug = basename($block);
                $block_post = $this->get_block_post($slug);
                if ($block_post) {
                    //var_dump($block_post->post_name); var_dump($block_post->post_status);
                    if ($block_post->post_status != 'publish') {
                        unset($blocks[$key]);
                    }
                }
            }
            return $blocks;
        });

        require_once(__DIR__ . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'blocks' . DIRECTORY_SEPARATOR . 'autoload.php');
    }
    
    public function get_block_post($slug) {
        //var_dump($slug);
        $posts = get_posts(
                [
                    'name' => $slug,
                    'post_type' => 'block',
                    'posts_per_page' => 1,
                    'post_status' => 'any',
                ]
        );
        if (!empty($posts)) {
            return reset($posts);
        }
        return false;
    }
    
    public function get_blocks() {
        $blocks_dirs = ['self' => TWIG_THAT_PATH.'blocks'];
        $blocks_dirs = apply_filters('twig/that/dirs', $blocks_dirs);
        $blocks = [];
        foreach ($blocks_dirs as $dir) {
            if (is_dir($dir)) {
                $blocks = array_merge($blocks, glob($dir . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR));
            }
        }
        $blocks = apply_filters('twig/that/blocks', $blocks);
        return $blocks;
    }
    
    public function get_block_title($block) {
        if (!empty($block['title'])) return $block['title'];
        list($textdomain, $title) = explode('/', $block['name'], 2);
        return ucfirst($title);
    }
    
    public function get_block_textdomain($block) {
        if (!empty($block['textdomain'])) return $block['textdomain'];
        list($textdomain, $title) = explode('/', $block['name'], 2);
        return strtolower($textdomain);
    }
    
    public function insert_block_post($block, $args = []) {
        $block_post = [
            'post_title' => empty($args['title']) ? $block : $args['title'],
            'post_name' => $block,
            'post_excerpt' => empty($args['description']) ? '' : $args['description'],
            'post_type' => 'block',
            'post_status' => 'publish'
        ];
        //var_dump($block_post); die();
        $block_post_id = wp_insert_post($block_post);
        return $block_post_id;
    }

    function get_blocks_dirs($blocks_dirs) {

        // uploads
        $wp_upload_dir = wp_upload_dir();
        $blocks_dirs['uploads'] = str_replace('/', DIRECTORY_SEPARATOR, $wp_upload_dir["basedir"] . DIRECTORY_SEPARATOR . 'blocks');

        // current theme
        $blocks_dirs['theme'] = str_replace('/', DIRECTORY_SEPARATOR, get_template_directory() . DIRECTORY_SEPARATOR . 'blocks');

        // current theme
        $plugin = str_replace('/', DIRECTORY_SEPARATOR, plugin_dir_path(dirname(__FILE__, 2)) . 'blocks');
        if (!in_array($plugin, $blocks_dirs)) {
            $blocks_dirs['plugin'] = $plugin;
        }

        return $blocks_dirs;
    }

    // save metadata
    public function meta_fields_save_meta_box_data($post_id, $post, $update) {
        if (!isset($_POST['meta_fields_meta_box_nonce']))
            return;
        if (!wp_verify_nonce($_POST['meta_fields_meta_box_nonce'], 'meta_fields_save_meta_box_data'))
            return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return;
        if (!current_user_can('edit_post', $post_id))
            return;

        // if changed slug delete old dir
        $post_slug = get_post_field('post_name', $post_id);
        if (!empty($_POST['_block_name'])) {
            $slug = sanitize_title($_POST['_block_name']);
            if ($post_slug != $slug) {
                wp_update_post( ['ID' => $post_id, 'post_name' => $slug] );
                // delete old dir
                $old_dir = $this->get_ensure_blocks_dir($post_slug);
                $this->dir_delete($old_dir);
                $post_slug = $slug;
            }
        }
        
        $post_excerpt = get_post_field('post_excerpt', $post_id);
        $plugin_name = basename(plugin_dir_path(dirname(__FILE__, 2)));

        $attributes = [];
        if (!empty($_POST['_block_attributes'])) {
            $attributes = trim($_POST['_block_attributes']);
            if (substr($attributes, 0, 1) != '{') {
                $attributes = '{' . $attributes;
            }
            if (substr($attributes, -1, 1) != '}') {
                $attributes = $attributes . '}';
            }
            $attributes = $this->unescape($attributes);
            //var_dump($attributes); die();
            $attributes = json_decode($attributes, true);
            //var_dump($attributes); die();
        }

        $version = "1.0.1";
        if (!empty($_POST['_block_version'])) {
            $version = sanitize_text_field($_POST['_block_version']);
        }

        $keywords = [];
        if (!empty($_POST['_block_keywords'])) {
            $keywords = array_filter(array_map('trim', explode(',', $_POST['_block_keywords'])));
        }

        $usesContext = [];
        if (!empty($_POST['_block_usesContext'])) {
            $usesContext = array_filter(array_map('trim', explode(',', $_POST['_block_usesContext'])));
        }

        $providesContext = [];
        if (!empty($_POST['_block_providesContext'])) {
            $providesContext = trim($_POST['_block_providesContext']);
            if (substr($providesContext, 0, 1) != '{') {
                $providesContext = '{' . $providesContext . '}';
            }
            $providesContext = $this->unescape($providesContext);
            //var_dump($providesContext); die();
            $providesContext = json_decode($providesContext);
        }

        $parents = [];
        if (!empty($_POST['_block_parent'])) {
            $parents = array_filter(array_map('trim', explode(',', $_POST['_block_parent'])));
        }

        $ancestors = [];
        if (!empty($_POST['_block_ancestor'])) {
            $ancestors = array_filter(array_map('trim', explode(',', $_POST['_block_ancestor'])));
        }

        $supports = [];
        if (!empty($_POST['_block_supports'])) {
            //$keys = array_keys($_POST['_block_supports']);
            foreach ($_POST['_block_supports'] as $sup => $value) {
                //var_dump($value); die();
                $value = $value == 'true' ? true : false;
                $default = self::$supports[$sup]; 
                if ($value != $default) {
                    $tmp = explode('.', $sup);
                    if (count($tmp) > 1) {
                        $supports[reset($tmp)][end($tmp)] = $value;
                    } else {
                        $supports[$sup] = $value;
                    }
                }
            }
        }
        if (!empty($_POST['_block_supports_custom'])) {            
            $custom = $this->unescape($_POST['_block_supports_custom'], '"');
            $custom = json_decode($custom, true); 
            if ($custom) {
                $supports = array_merge($supports, $custom);
            }
        }
        
        $json_old = $this->get_json_data($post_slug);
        $textdomain = empty($json_old['textdomain']) ? $plugin_name : $json_old['textdomain'];

        // https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/
        $json = [
            "\$schema" => "https://schemas.wp.org/trunk/block.json",
            "apiVersion" => 3,
            "name" => $textdomain . "/" . $post_slug,
            "title" => get_the_title($post_id),
            "category" => $_POST['_block_category'],
            "parent" => $parents,
            "ancestor" => $ancestors,
            "icon" => $_POST['_block_icon'],
            "description" => $post_excerpt,
            "keywords" => $keywords,
            "version" => $version,
            "textdomain" => $textdomain,
            "attributes" => $attributes,
            "providesContext" => $providesContext,
            "usesContext" => $usesContext,
            /* "selectors": {
              "root": ".wp-block-my-plugin-notice"
              }, */
            "supports" => $supports,
                /* "styles": [
                  { "name": "default", "label": "Default", "isDefault": true },
                  { "name": "other", "label": "Other" }
                  ], */
                /* "example": {
                  "attributes": {
                  "message": "This is a notice!"
                  }
                  }, */
                /* "variations": [
                  {
                  "name": "example",
                  "title": "Example",
                  "attributes": {
                  "message": "This is an example!"
                  }
                  }
                  ], */
                //"editorScript" => "file:./index.js",
                //"script" => "file:./script.js",
                //"viewScript" => "file:./view.js",
                //"editorStyle" => "file:./index.css",
                //"style" => "file:./style.css",
                //"render" => "file:./render.php"
        ];

        
        $assets = [
            'script' => 'js',
            'viewScript' => 'js',
            'editorScript' => 'js',
            'style' => 'css',
            'editorStyle' => 'css',
            'render' => 'php'
        ];
        foreach ($assets as $asset => $type) {
            $code = '';
            if (!empty($_POST['_block_' . $asset])) {
                $code = trim($_POST['_block_' . $asset]);
                $code = $this->unescape($code);
                if (!empty($json_old[$asset])) {
                    $file = $json_old[$asset];
                    $file = str_replace('file:./', '', $file);
                } else {
                    $file = $asset . '.' . $type;
                }
                $path = $this->get_ensure_blocks_dir($post_slug) . $file;
                if (file_put_contents($path, $code)) {
                    $json[$asset] = "file:./" . $file;
                }
            }
            if (in_array($asset, ['editorScript', 'viewScript'])) {
                if ($asset == 'editorScript') {
                    if (!$code || strpos($code, 'generated by '.$plugin_name) !== false) {
                        // autogenerated - so update it
                        // render server side
                        $code = $this->_edit($json);
                        $path = $this->get_ensure_blocks_dir($post_slug) . $asset . '.' . $type;
                        file_put_contents($path, $code);
                    }
                }

                $min = '.min.';
                $path_min = $this->get_ensure_blocks_dir($post_slug) . $asset . $min . $type;
                $minifier = new \MatthiasMullie\Minify\JS($code);
                // save minified file to disk
                $minifier->minify($path_min);

                $json[$asset] = "file:./" . $asset . (SCRIPT_DEBUG ? '.' : $min) . $type;

                $path = $this->get_ensure_blocks_dir($post_slug) . $asset .'.asset.php';
                $code = "<?php return array('dependencies'=>[], 'version'=>'".date('U')."');";
                file_put_contents($path, $code);

            }
        }

        $json = array_filter($json);

        $path = $this->get_ensure_blocks_dir($post_slug) . 'block.json';
        $code = wp_json_encode($json, JSON_PRETTY_PRINT);
        $code = str_replace('\/', '/', $code);
        file_put_contents($path, $code);
        /* echo '<pre>';
          var_dump($json);
          die(); */
    }

    public function get_json_data($post_slug) {
        $path = $this->get_ensure_blocks_dir($post_slug) . 'block.json';
        if (file_exists($path)) {
            $content = file_get_contents($path);
            return json_decode($content, true);
        }
        return [];
    }

    public function unescape($code = '', $quote = '') {
        $code = str_replace('\"', $quote ? $quote : '"', $code);
        $code = str_replace("\'", $quote ? $quote : "'", $code);
        $code = str_replace("\/", "/", $code);
        return $code;
    }

    /**
     * Gets the path to uploaded file.
     *
     * @return string
     */
    private function get_blocks_dir($slug = '') {
        $wp_upload_dir = wp_upload_dir();
        $path = $wp_upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'blocks';
        $path = str_replace('/', DIRECTORY_SEPARATOR, $path);

        $blocks_dirs = ['uploads' => $path];
        $blocks_dirs = apply_filters('twig/that/dirs', $blocks_dirs);
        foreach ($blocks_dirs as $dir) {
            if (is_dir($dir . DIRECTORY_SEPARATOR . $slug)) {
                $path = $dir;
            }
        }

        /**
         * Blocks upload file path.
         *
         * Filters the path to a file uploaded using Elementor forms.
         *
         * By default Elementor forms defines a path to uploaded file. This
         * hook allows developers to alter this path.
         *
         * @since 1.0.0
         *
         * @param string $path Path to uploaded files.
         */
        $path = apply_filters('twig/that/blocks/path', $path);

        return $path;
    }

    /**
     * This function returns the uploads folder after making sure
     * it is created and has protection files
     * @return string
     */
    private function get_ensure_blocks_dir($slug = '') {
        $path = $this->get_blocks_dir($slug);
        if (!file_exists($path . DIRECTORY_SEPARATOR . 'index.php')) {

            wp_mkdir_p($path);

            $files = [
                [
                    'file' => 'index.php',
                    'content' => [
                        '<?php',
                        '// Silence is golden.',
                    ],
                ],
                [
                    'file' => '.htaccess',
                    'content' => [
                        'Options -Indexes',
                        '<ifModule mod_headers.c>',
                        '	<Files *.*>',
                        '       Header set Content-Disposition attachment',
                        '	</Files>',
                        '</IfModule>',
                    ],
                ],
            ];

            foreach ($files as $file) {
                if (!file_exists(trailingslashit($path) . $file['file'])) {
                    $content = implode(PHP_EOL, $file['content']);
                    @ file_put_contents(trailingslashit($path) . $file['file'], $content);
                }
            }
        }

        wp_mkdir_p($path . DIRECTORY_SEPARATOR . $slug);
        return $path . DIRECTORY_SEPARATOR . $slug . DIRECTORY_SEPARATOR;
    }

    public function dir_delete($dir) {
        if (is_dir($dir)) {
            global $wp_filesystem;
            // Make sure that the above variable is properly setup.
            require_once ABSPATH . 'wp-admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'file.php';
            WP_Filesystem();
            return $wp_filesystem->delete($dir, true);
        }
        return false;
    }

    public function dir_copy($src, $dst) {
        // open the source directory 
        $dir = opendir($src);
        // Make the destination directory if not exist 
        @mkdir($dst);
        // Loop through the files in source directory 
        foreach (scandir($src) as $file) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if (is_dir($src . DIRECTORY_SEPARATOR . $file)) {
                    // Recursively calling custom copy function 
                    // for sub directory  
                    $this->dir_copy($src . DIRECTORY_SEPARATOR . $file, $dst . DIRECTORY_SEPARATOR . $file);
                } else {
                    copy($src . DIRECTORY_SEPARATOR . $file, $dst . DIRECTORY_SEPARATOR . $file);
                }
            }
        }
        closedir($dir);
    }
}
