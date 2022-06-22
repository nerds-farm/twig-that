<?php

namespace TwigThat;

use TwigThat\Core\Utils;
use TwigThat\Core\Managers\Modules;

/**
 * Main Plugin Class
 *
 * @since 1.0.1
 */
class Plugin {

    /**
     * Instance.
     *
     * Holds the plugin instance.
     *
     * @since 1.0.1
     * @access public
     * @static
     *
     * @var Plugin
     */
    public static $instance = null;

    /**
     * Modules manager.
     *
     * Holds the modules manager.
     *
     * @since 1.0.0
     * @access public
     *
     * @var Modules_Manager
     */
    public $modules_manager;

    /**
     * Constructor
     *
     * @since 1.0.1
     *
     * @access public
     */
    public function __construct() {
        
        $plugin_class_name = get_class($this);

        require_once(TWIG_THAT_PATH . 'core' . DIRECTORY_SEPARATOR . 'helper.php');

        spl_autoload_register([$this, 'autoload']);
        
        $this->maybe_vendor_autoload();

        $this->setup_hooks();
    }

    /**
     * Instance.
     *
     * Ensures only one instance of the plugin class is loaded or can be loaded.
     *
     * @since 1.0.0
     * @access public
     * @static
     *
     * @return Plugin An instance of the class.
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();

            /**
             * on loaded.
             *
             * Fires when was fully loaded and instantiated.
             *
             * @since 1.0.1
             */
            do_action('twig-that/instance', self::$instance);
        }

        return self::$instance;
    }

    public function autoload($class) {
        $self = explode('\\', get_class($this));
        $other = explode('\\', $class);
        if (reset($other) != reset($self)) {
            return;
        }
        if (!class_exists($class)) {
            $filename = \TwigThat\Core\Helper::class_to_path($class);
            if (is_readable($filename)) {
                include_once( $filename );
            } else {
                // fallback
                $plugin_path = \TwigThat\Core\Helper::get_plugin_path($class);
                $tmp = explode(DIRECTORY_SEPARATOR, $plugin_path);
                $tmp = array_filter($tmp);
                $plugin_name = end($tmp);
                $filename = str_replace(DIRECTORY_SEPARATOR . $plugin_name . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR, $filename);
                $plugin_search = glob($filename);
                $filename = reset($plugin_search);
                if (is_readable($filename)) {
                    include_once( $filename );
                } else {
                    //var_dump($class); var_dump($plugin_path); var_dump($filename); //die();                    
                }
            }
        }
    }

    public function setup_hooks() {
        // fire actions
        
        $this->modules_manager = new Modules();
        
        do_action('twig-that/init');
    }

    public function get_name() {
        list($class, $none) = explode("\\", get_class($this), 2);
        $slug = \TwigThat\Core\Helper::camel_to_slug($class);
        return $slug;
    }

    public function maybe_vendor_autoload($TextDomain = '') {
        if ($this->has_vendors($TextDomain)) {
            $file = TWIG_THAT_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
            if (file_exists($file)) {
                if (!defined('TIMBER_LOADED')) {
                    require_once $file;
                }
            }
        }
    }

    public function has_vendors($TextDomain = '') {
        $composer = TWIG_THAT_PATH . DIRECTORY_SEPARATOR . 'composer.json';
        return file_exists($composer);
    }

}
