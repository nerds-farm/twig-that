<?php

namespace TwigThat\Modules\Api;

use TwigThat\Core\Utils;
use TwigThat\Base\Module_Base;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Api extends Module_Base {

    public function __construct() {
        add_action('rest_api_init', function () {
            //$user = wp_get_current_user();
            register_rest_route('wp/v2', '/user/roles', array(
                'methods' => 'GET',
                'callback' => function ($request) {
                    $roles = wp_roles();
                    //var_dump($roles->roles); die();
                    return $roles->roles;
                },
                'permission_callback' => '__return_true', 
                        /*function () {
                            return current_user_can( 'edit_posts' );
                        }*/
            ));
        });
    }
}
