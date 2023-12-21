<?php
namespace TwigThat\Modules\Twig\Tags;

//use Elementor\Core\DynamicTags\Tag;
use \Elementor\Controls_Manager;
use TwigThat\Core\Utils;
use Elementor\Modules\DynamicTags\Module;
//use TwigThat\Base\Base_Tag;
use TwigThat\Modules\Twig\Twig;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Twag extends \Elementor\Core\DynamicTags\Tag {
    
    public $is_data = false;

    public static $wp_objs = array('post', 'user', 'term');

    public function get_group() {
        return 'site';
    }
    
    public function get_name() {
        return 'e-tag-twig';
    }

    public function get_icon() {
        return 'eicon-code';
    }

    public function get_title() {
        return esc_html__('Twig', 'twig-that');
    }
    
    public function get_categories() {
        //return ['base', 'text', 'url', 'image', 'media', 'post_meta', 'gallery', 'number', 'color'];
        //new \Elementor\Modules\DynamicTags\Module();
        $reflection = new \ReflectionClass('\Elementor\Modules\DynamicTags\Module');
        $categories = $reflection->getConstants();
        return array_values($categories);
    }
    
    /**
     * Register Controls
     *
     * Registers the Dynamic tag controls
     *
     * @since 2.0.0
     * @access protected
     *
     * @return void
     */
    protected function register_controls() {

        $this->add_control(
                'e_twig',
                [
                    'label' => esc_html__('Twig', 'twig-that'),
                    'type' => \Elementor\Controls_Manager::CODE,
                    'label_block' => true,
                    'placeholder' => '{{post.post_title}}',
                ]
        );

        $this->add_control(
                'e_twig_data',
                [
                    'label' => esc_html__('Return as Structured Data', 'twig-that'),
                    'type' => \Elementor\Controls_Manager::SWITCHER,
                    'description' => '<small>'. esc_html__('Required for MEDIA Controls and other Controls which need a structured data', 'twig-that').'</small>',
                ]
        );

        //Utils::add_help_control($this);
    }

    public function render() {
        $settings = $this->get_settings();
        if (empty($settings))
            return;

        $value = $this->get_twig_value($settings);

        echo $value;
    }

    public function get_twig_value($settings) {
        //var_dump($settings);
        $twig = $settings['e_twig'];
        $value = apply_filters('twig/that', $twig);
        return $value;
    }

    public function get_value(array $options = []) {
        $settings = $this->get_settings_for_display(null, true);
        if (empty($settings))
            return;

        $value = $this->get_twig_value($settings);
        $value = Utils::maybe_media($value, $this);

        return $value;
    }
    
    /**
	 * @since 2.0.0
	 * @access protected
	 */
	protected function register_advanced_section() {}
    
    /**
     * @since 2.0.0
     * @access public
     *
     * @param array $options
     *
     * @return string
     * 
     * Extend Tag
     * /elementor/core/dynamic-tags/tag.php
     */
    public function get_content(array $options = []) {
        
        $this->is_data = (bool)$this->get_settings('e_twig_data');    
        $settings = $this->get_settings();

        if ($this->is_data) {
            $value = $this->get_value($options);
            //$value = Utils::maybe_media($value, $this);
        } else {
            ob_start();
            $this->render();
            $value = ob_get_clean();
        }

        if (Utils::empty($value)) {
            if (!Utils::empty($settings, 'fallback')) {
                $value = $settings['fallback'];
                $value = apply_filters('twig/that',$value);
            }
        }

        if (!Utils::empty($value) && !$this->is_data) {

            // TODO: fix spaces in `before`/`after` if WRAPPED_TAG ( conflicted with .elementor-tag { display: inline-flex; } );
            if (!Utils::empty($settings, 'before')) {
                $value = wp_kses_post($settings['before']) . $value;
            }

            if (!Utils::empty($settings, 'after')) {
                $value .= wp_kses_post($settings['after']);
            }
            
        }

        return $value;
    }

}
