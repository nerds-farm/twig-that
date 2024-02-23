<?php
namespace TwigThat\Modules\Block\Traits;

trait Metabox {
    
    // https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#category
        public static $categories = [
            'text',
            'media',
            'design',
            'widgets',
            'theme',
            'embed'
        ];

        // https://developer.wordpress.org/block-editor/reference-guides/block-api/block-supports/
        public static $supports = [
            'anchor' => false,
            'align' => false,
            'alignWide' => true,
            'ariaLabel' => false,
            'className' => true,
            'color.background' => true,
            'color.gradients' => false,
            'color.link' => false,
            'color.text' => true,
            'customClassName' => false,
            'dimensions.aspectRatio' => true,
            'dimensions.minHeight' => false,
            'filter.duotone' => false,
            'html' => true,
            'inserter' => true,
            'interactivity.clientNavigation' => false,
            'interactivity.interactive' => false,
            'layout.allowSwitching' => false,
            'layout.allowEditing' => true,
            'layout.allowInheriting' => true,
            'layout.allowSizingOnChildren' => false,
            'layout.allowVerticalAlignment' => true,
            'layout.allowJustification' => true,
            'layout.allowOrientation' => true,
            'layout.allowCustomContentAndWideSize' => true,
            'multiple' => true,
            'reusable' => true,
            'lock' => true,
            'position.sticky' => false,
            'spacing.margin' => false,
            'spacing.padding' => false,
            'spacing.blockGap' => false,
            'typography.fontSize' => false,
            'typography.lineHeight' => false
        ];
    
    
// register meta box
    public function meta_fields_add_meta_box() {
        add_meta_box(
                'render_meta_box',
                __('Block Content'),
                [$this, 'meta_fields_build_render_callback'],
                'block',
                //'side',
                //'default'
        );

        add_meta_box(
                'meta_fields_meta_box',
                __('Block Assets'),
                [$this, 'meta_fields_build_meta_box_callback'],
                'block',
                //'side',
                //'default'
        );
        add_meta_box(
                'attributes_meta_box',
                __('Block Attributes'),
                [$this, 'meta_fields_build_attributes_callback'],
                'block'
        );
        add_meta_box(
                'meta_fields_side_meta_box',
                __('Block Info'),
                [$this, 'meta_fields_build_meta_box_side_callback'],
                'block',
                'side',
                'default'
        );
    }

    public function meta_fields_build_render_callback($post, $metabox) {
        wp_nonce_field('meta_fields_save_meta_box_data', 'meta_fields_meta_box_nonce');
        
        $json = $post ? $this->get_json_data($post->post_name) : [];
        
        $render = '';
        if ($post) {
            $file = 'render.php';
            if (!empty($json['render'])) {
                $file = str_replace('file:', '', $json['render']);
                $file = str_replace('/', DIRECTORY_SEPARATOR, $file);
            }
            $render_file = $this->get_ensure_blocks_dir($post->post_name) . $file;
            if (file_exists($render_file)) {
                $render = file_get_contents($render_file);
            }
        }
        ?>
        <div class="inside">
            <h3><strong><?php _e('Render', 'twig-that'); ?></strong> <a target="_blank" href="https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#render"><span class="dashicons dashicons-info-outline"></span></a></h3>
            <p><textarea id="_block_render" name="_block_render"><?php echo $render; ?></textarea></p>	           
            <div class="notice inline notice-primary notice-alt" style="display: block; padding: 20px;">
                <span class="dashicons dashicons-info"></span> The following variables are exposed to the file:
                <ul>
                    <li><b>$attributes</b> (array): The block attributes.</li>
                    <li><b>$content</b> (string): The block default content.</li>
                    <li><b>$block</b> (WP_Block): The block instance.</li>
                </ul>
            </div>
        </div>
        <?php
        $php = wp_enqueue_code_editor(array('type' => 'text/html'));
        wp_enqueue_script('wp-theme-plugin-editor');
        wp_enqueue_style('wp-codemirror');
        add_action('admin_print_footer_scripts', function () {
            ?>
            <script>
                jQuery(document).ready(function ($) {
                    var editorSettings = wp.codeEditor.defaultSettings ? _.clone(wp.codeEditor.defaultSettings) : {};
                    editorSettings.codemirror = _.extend(
                            {},
                            editorSettings.codemirror,
                            {
                                indentUnit: 2,
                                tabSize: 2,
                                mode: 'text/x-php'
                            }
                    );
                    var _block_render = wp.codeEditor.initialize(jQuery('#_block_render'), editorSettings);
                });
            </script>
            <?php
        });
    }

// build meta box
    public function meta_fields_build_meta_box_callback($post, $metabox) {
        //wp_nonce_field('meta_fields_save_meta_box_data', 'meta_fields_meta_box_nonce');
        //$style = get_post_meta($post->ID, '_meta_fields_book_title', true);
        $plugin_name = basename(plugin_dir_path(dirname(__FILE__, 3)));
        //var_dump($plugin_name);
        $style = $editorStyle = $script = $editorScript = $viewScript = '';
        if ($post) {
            
            $json = $post ? $this->get_json_data($post->post_name) : [];
            $basepath = $this->get_ensure_blocks_dir($post->post_name);
            
            $asset_file = 'style.css';
            if (!empty($json['style'])) {
                $asset_file = str_replace('file:', '', $json['style']);
                $asset_file = str_replace('/', DIRECTORY_SEPARATOR, $asset_file);
            }
            $asset_file = $basepath . $asset_file;
            if (file_exists($asset_file)) {
                $style = file_get_contents($asset_file);
            }
            
            $asset_file = 'editorStyle.css';
            if (!empty($json['editorStyle'])) {
                $asset_file = str_replace('file:', '', $json['editorStyle']);
                $asset_file = str_replace('/', DIRECTORY_SEPARATOR, $asset_file);
            }
            $asset_file = $basepath . $asset_file;
            if (file_exists($asset_file)) {
                $editorStyle = file_get_contents($asset_file);
            }
            
            $asset_file = 'script.js';
            if (!empty($json['script'])) {
                $asset_file = str_replace('file:', '', $json['script']);
                $asset_file = str_replace('/', DIRECTORY_SEPARATOR, $asset_file);
            }
            $asset_file = $basepath . $asset_file;
            if (file_exists($asset_file)) {
                $script = file_get_contents($asset_file);
            }
            
            $is_editor_script_generated = false;
            $asset_file = 'editorScript.js';
            if (!empty($json['editorScript'])) {
                $asset_file = str_replace('file:', '', $json['editorScript']);
                $asset_file = str_replace('/', DIRECTORY_SEPARATOR, $asset_file);
                $unmin = str_replace('.min.js', '.js', $asset_file);
                $unmin_file = $basepath . $unmin;
                if (file_exists($unmin_file)) {
                    $asset_file = $unmin;
                }
            }
            $asset_file = $basepath . $asset_file;
            if (file_exists($asset_file)) {
                $editorScript = file_get_contents($asset_file);
                if (strpos($editorScript, 'generated by '.$plugin_name) !== false) {
                    $is_editor_script_generated = true;
                }
            }
            //var_dump($is_editor_script_generated);
            
            $asset_file = 'viewScript.js';
            if (!empty($json['viewScript'])) {
                $asset_file = str_replace('file:', '', $json['viewScript']);
                $asset_file = str_replace('/', DIRECTORY_SEPARATOR, $asset_file);
            }
            $asset_file = $basepath . $asset_file;
            if (file_exists($asset_file)) {
                $viewScript = file_get_contents($asset_file);
            }
        }
        ?>
        <div class="inside">
            
            <a class="tab-head tab-active" href="#css">CSS</a> <a class="tab-head" href="js">SCRIPT</a>
            
            <div class="tab-body tab-css">
                <h3><strong><?php _e('Style', 'twig-that'); ?></strong> <a target="_blank" href="https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#style"><span class="dashicons dashicons-info-outline"></span></a></h3>
                <p><textarea id="_block_style" name="_block_style"><?php echo $style; ?></textarea></p>	

                <h3><strong><?php _e('EditorStyle', 'twig-that'); ?></strong> <a target="_blank" href="https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#editor-style"><span class="dashicons dashicons-info-outline"></span></a></h3>
                <p><textarea id="_block_editorStyle" name="_block_editorStyle"><?php echo $editorStyle; ?></textarea></p>	
            </div>
            <div class="tab-body tab-js">
                
                <h3><strong><?php _e('EditorScript', 'twig-that'); ?></strong> <a target="_blank" href="https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#editor-script"><span class="dashicons dashicons-info-outline"></span></a></h3>
                <p><textarea<?php echo (false) ? ' style="background-color: white; cursor: not-allowed;" rows="15" readonly' : '' ; ?> id="_block_editorScript" name="_block_editorScript"><?php echo $editorScript; ?></textarea></p>
                
                <h3><strong><?php _e('Script', 'twig-that'); ?></strong> <a target="_blank" href="https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#script"><span class="dashicons dashicons-info-outline"></span></a></h3>
                <p><textarea id="_block_script" name="_block_script"><?php echo $script; ?></textarea></p>

                <h3><strong><?php _e('ViewScript', 'twig-that'); ?></strong> <a target="_blank" href="https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#view-script"><span class="dashicons dashicons-info-outline"></span></a></h3>
                <p><textarea id="_block_viewScript" name="_block_viewScript"><?php echo $viewScript; ?></textarea></p>
                
            </div>
            
            <style>
                .inside input[type="text"], .inside textarea, .inside select {
                    width: 100%;
                }
                .CodeMirror {
                    border: 1px solid #ddd;
                  
                }
                .tab-head {
                    text-decoration: none;
                    padding: 10px 20px;
                    display: inline-block;
                    background-color: white; 
                    border: 1px solid #ddd;
                    border-bottom: none;
                }
                .tab-head.tab-active {
                    text-decoration: none;
                    background-color: #eee;
                }
                .tab-body {
                    background-color: #eee;
                   border: 1px solid #ddd; 
                   padding: 15px;
                }
                #wpfooter {
                    position: static;
                }
            </style>
            <script>
                jQuery('.tab-head').on('click', function(){
                    jQuery('.tab-head').removeClass('tab-active');
                    jQuery(this).addClass('tab-active');
                    jQuery('.tab-body').toggle();
                    return false;
                });
                setTimeout(function(){jQuery('.tab-js').hide();}, 1000);
            </script>
        </div>
        <?php
        $css = wp_enqueue_code_editor(array('type' => 'text/css'));
        $js = wp_enqueue_code_editor(array('type' => 'application/javascript'));
        wp_enqueue_script('wp-theme-plugin-editor');
        wp_enqueue_style('wp-codemirror');
        add_action('admin_print_footer_scripts', function () use ($is_editor_script_generated) {
            ?>
            <script>
                jQuery(document).ready(function ($) {
                    var editorSettings = wp.codeEditor.defaultSettings ? _.clone(wp.codeEditor.defaultSettings) : {};

                    editorSettings.codemirror = _.extend(
                            {},
                            editorSettings.codemirror,
                            {
                                indentUnit: 2,
                                tabSize: 2,
                                mode: 'css'
                            }
                    );
                    var _block_style = wp.codeEditor.initialize(jQuery('#_block_style'), editorSettings);
                    var _block_editorStyle = wp.codeEditor.initialize(jQuery('#_block_editorStyle'), editorSettings);

                    editorSettings.codemirror = _.extend(
                            {},
                            editorSettings.codemirror,
                            {
                                indentUnit: 2,
                                tabSize: 2,
                                mode: 'javascript',
                            }
                    );
                    var _block_script = wp.codeEditor.initialize(jQuery('#_block_script'), editorSettings);
                    <?php //if (!$is_editor_script_generated) { ?>
                        var _block_editorScript = wp.codeEditor.initialize(jQuery('#_block_editorScript'), editorSettings);
                    <?php //} ?>
                    var _block_viewScript = wp.codeEditor.initialize(jQuery('#_block_viewScript'), editorSettings);

                });
            </script>
            <?php
        });
    }
    
    public function meta_fields_build_attributes_callback($post, $metabox) {
        //wp_nonce_field('meta_fields_save_meta_box_side_data', 'meta_fields_meta_box_nonce');
        
        $json = $post ? $this->get_json_data($post->post_name) : [];
        
        //$style = get_post_meta($post->ID, '_meta_fields_book_title', true);
        $attributes = '';

        ?>
        <div class="inside">
            
            <h3><strong><?php _e('Attributes', 'twig-that'); ?></strong> <a target="_blank" href="https://developer.wordpress.org/block-editor/reference-guides/block-api/block-attributes/"><span class="dashicons dashicons-info-outline"></span></a></h3>
            <p><textarea id="_block_attributes" name="_block_attributes"><?php echo empty($json['attributes']) ? '' : wp_json_encode($json['attributes'], JSON_PRETTY_PRINT); ?></textarea></p>	

        </div>
        <?php
        $js = wp_enqueue_code_editor(array('type' => 'application/javascript'));
        wp_enqueue_script('wp-theme-plugin-editor');
        wp_enqueue_style('wp-codemirror');
        add_action('admin_print_footer_scripts', function () {
            ?>
            <script>
                jQuery(document).ready(function ($) {
                    var editorSettings = wp.codeEditor.defaultSettings ? _.clone(wp.codeEditor.defaultSettings) : {};
                    editorSettings.codemirror = _.extend(
                            {},
                            editorSettings.codemirror,
                            {
                                indentUnit: 2,
                                tabSize: 2,
                                mode: 'javascript'
                            }
                    );
                    var _block_render = wp.codeEditor.initialize(jQuery('#_block_attributes'), editorSettings);
                });
            </script>
            <?php
        });
    }

    public function meta_fields_build_meta_box_side_callback($post, $metabox) {
        //wp_nonce_field('meta_fields_save_meta_box_side_data', 'meta_fields_meta_box_nonce');
        
        $json = $post ? $this->get_json_data($post->post_name) : [];
        
        //$style = get_post_meta($post->ID, '_meta_fields_book_title', true);

        $icons = [];
        //Get an instance of WP_Scripts or create new;
        $wp_styles = wp_styles();
        //Get the script by registered handler name
        $style = $wp_styles->registered['dashicons'];
        $dashicons = ABSPATH . $style->src;
        //var_dump($dashicons); die();
        $dashicons = str_replace('//', DIRECTORY_SEPARATOR, $dashicons);
        $dashicons = str_replace('/', DIRECTORY_SEPARATOR, $dashicons);
        //var_dump($dashicons); die();
        if (file_exists($dashicons)) {
            $css = file_get_contents($dashicons);
            $tmp = explode('.dashicons-', $css);
            foreach ($tmp as $key => $piece) {
                if ($key) {
                    list($icon, $more) = explode(':', $piece, 2);
                    $icons[$icon] = $icon;
                }
            }
        }
        unset($icons['before']);
        ?>
        <div class="inside">
            
            <?php if (!empty($post->post_name)) { ?>
                <h3><strong><?php _e('Name', 'twig-that'); ?></strong> <a target="_blank" href="https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#name"><span class="dashicons dashicons-info-outline"></span></a></h3>
                <p><?php echo $this->get_block_textdomain($json); ?>/<input style="width: 60%;" type="text" id="_block_name" name="_block_name" value="<?php echo $post->post_name; ?>" /></p>
            <?php } ?>

            <h3><strong><?php _e('Version', 'twig-that'); ?></strong> <a target="_blank" href="https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#version"><span class="dashicons dashicons-info-outline"></span></a></h3>
            <p><input type="text" id="_block_version" name="_block_version" placeholder="1.0.1" value="<?php if (!empty($json['version'])) { echo $json['version']; } ?>" /></p>	           

            <h3><strong><?php _e('Icon', 'twig-that'); ?></strong> <a target="_blank" href="https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#icon"><span class="dashicons dashicons-info-outline"></span></a></h3>
            <p><select type="text" id="_block_icon" name="_block_icon"><?php
            if (empty($post->post_name)) $json['icon'] = 'smiley';
            foreach ($icons as $icon) {
                $selected = (!empty($json['icon']) && $json['icon'] == $icon) ? ' selected' : '';
                echo '<option value="' . $icon . '"'.$selected.'>' . $icon . '</option>';
            }
            ?></select></p>	
            <?php
            wp_enqueue_style( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css' );
            wp_enqueue_script( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array( 'jquery' ) );
            ?>
            <script>
            jQuery( function($){

                  jQuery( '#_block_icon' ).select2({
                        templateResult: function (state) {
                            if (!state.id) {
                              return state.text;
                            }
                            var $state = $(
                              '<span class="dashicons dashicons-'+state.element.value+'"></span> ' + state.text + '</span>'
                            );
                            return $state;
                          }
                  });
                    
            } );
            </script>
            

            <h3><strong><?php _e('Category', 'twig-that'); ?></strong> <a target="_blank" href="https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#category"><span class="dashicons dashicons-info-outline"></span></a></h3>
            <p><select type="text" id="_block_category" name="_block_category"><?php
                    foreach (self::$categories as $cat) {
                        $selected = (!empty($json['category']) && $json['category'] == $cat) ? ' selected' : '';
                        echo '<option value="' . $cat . '"'.$selected.'>' . $cat . '</option>';
                    }
                    ?></select></p>	

            <h3><strong><?php _e('Keywords', 'twig-that'); ?></strong> <a target="_blank" href="https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#keywords"><span class="dashicons dashicons-info-outline"></span></a></h3>
            <p><input type="text" id="_block_keywords" name="_block_keywords" placeholder="alert, message" value="<?php if (!empty($json['keywords'])) { echo is_array($json['keywords']) ? implode(', ', $json['keywords']) : $json['keywords']; } ?>" /></p>	           

            <h3><strong><?php _e('Parent', 'twig-that'); ?></strong> <a target="_blank" href="https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#parent"><span class="dashicons dashicons-info-outline"></span></a></h3>
            <p><input type="text" id="_block_parent" name="_block_parent" placeholder="core/group"  value="<?php if (!empty($json['parent'])) { echo is_array($json['parent']) ? implode(', ', $json['parent']) : $json['parent']; } ?>" /></p>	           
 
            <h3><strong><?php _e('Ancestor', 'twig-that'); ?></strong> <a target="_blank" href="https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#ancestor"><span class="dashicons dashicons-info-outline"></span></a></h3>
            <p><input type="text" id="_block_ancestor" name="_block_ancestor" placeholder="my-block/product"  value="<?php if (!empty($json['ancestor'])) { echo is_array($json['ancestor']) ? implode(', ', $json['ancestor']) : $json['ancestor']; } ?>" /></p>	           
            
            <h3><strong><?php _e('providesContext', 'twig-that'); ?></strong> <a target="_blank" href="https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#provides-context"><span class="dashicons dashicons-info-outline"></span></a></h3>
            <p><textarea id="_block_providesContext" name="_block_providesContext" placeholder='"my-plugin/recordId": "recordId"'><?php if (!empty($json['providesContext'])) { echo $providesContext = wp_json_encode($json['providesContext'], JSON_PRETTY_PRINT); } ?></textarea></p>	

            <h3><strong><?php _e('usesContext', 'twig-that'); ?></strong> <a target="_blank" href="https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#uses-context"><span class="dashicons dashicons-info-outline"></span></a></h3>
            <p><input type="text" id="_block_usesContext" name="_block_usesContext" placeholder="postId, postType" value="<?php if (!empty($json['usesContext'])) { echo is_array($json['usesContext']) ? implode(', ', $json['usesContext']) : $json['usesContext']; } ?>" /></p>	           
            
            <h3><strong><?php _e('Supports', 'twig-that'); ?></strong> <a target="_blank" href="https://developer.wordpress.org/block-editor/reference-guides/block-api/block-supports/"><span class="dashicons dashicons-info-outline"></span></a></h3>
            <div style="height: 180px; overflow: auto; border: 1px solid #eee; padding: 0 10px;">
            <?php 
            $custom = [];
            foreach (self::$supports as $sup => $default) { ?>
                <p>
                    <label for="_block_supports_<?php echo $sup; ?>"><b><?php echo $sup; ?></b></label><br>
                    <!-- <input type="checkbox" id="_block_supports_<?php echo $sup; ?>" name="_block_supports[<?php echo $sup; ?>]"<?php if (!empty($json['supports']) && in_array($sup, $json['supports'])) { echo ' checked'; } ?>> <b><?php echo $sup; ?></b></label> -->
                    <?php 
                    $value = $default; 
                    if (!empty($json['supports'])) {
                        if (isset($json['supports'][$sup])) {
                            if (is_bool($json['supports'][$sup])) {
                                $value = $json['supports'][$sup];
                            } else {
                                $custom[$sup] = $value;
                            }
                        } else {
                            $tmp = explode('.', $sup);
                            if (count($tmp) > 2) {
                                if (isset($json['supports'][reset($tmp)][end($tmp)])) {
                                    if (is_bool($json['supports'][reset($tmp)][end($tmp)])) {
                                        $value = $json['supports'][reset($tmp)][end($tmp)];
                                    } else {
                                        $custom[reset($tmp)][end($tmp)] = $value;
                                    }
                                }
                            }
                        }
                    }
                    ?>
                    <input type="radio" id="_block_supports_<?php echo $sup; ?>_true" name="_block_supports[<?php echo $sup; ?>]" value="true"<?php if ($value) { echo ' checked'; } ?>> <label for="_block_supports_<?php echo $sup; ?>_true"><?php echo 'True'; ?></label>
                    <input type="radio" id="_block_supports_<?php echo $sup; ?>_false" name="_block_supports[<?php echo $sup; ?>]" value="false"<?php if (!$value == 'false') { echo ' checked'; } ?>> <label for="_block_supports_<?php echo $sup; ?>_false"><?php echo 'False'; ?></label>
                </p>
            <?php } ?>	
            </div>
            <?php 
            if (!empty($json['supports'])) {
                foreach ($json['supports'] as $sup => $support) {
                    if (!isset($custom[$sup]) && !isset(self::$supports[$sup])) {
                        $custom[$sup] = $support;
                    } else {
                        if (is_array($support)) {
                            foreach($support as $sub => $suppo) {
                                if (!isset($custom[$sup][$sub]) && !isset(self::$supports[$sup.'.'.$sub])) {
                                    $custom[$sup][$sub] = $suppo;
                                }
                            }
                        }
                    }
                }
            }
            $custom = empty($custom) ? '' : wp_json_encode($custom, JSON_PRETTY_PRINT);
            ?>
            <label for="_block_supports_custom"><b><?php _e('Supports custom values', 'twig-that'); ?></b></label>
            <textarea rows="10" id="_block_supports_custom" name="_block_supports_custom" style="width: 100%;" placeholder='{ "spacing": { "margin": [ "top", "bottom" ] } }'><?php echo $custom; ?></textarea>
          
        </div>
        <?php
    }
    
}