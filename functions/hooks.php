<?php

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) 
{
    echo "Hi there!<br>Do you want to plug me ?<br>";
	echo "If you looking for more about me, you can read at http://osw3.net/wordpress/plugins/please-plug-me/";
    exit;
}


/**
 * Session Start
 */
if (!function_exists('WPPPM_SessionStart'))
{
    function WPPPM_SessionStart()
    {
        if (empty(session_id())) session_start();
    }
} 


/**
 * Front Form Submission
 */
if (!function_exists('GetInTouch_Submission'))
{
    function GetInTouch_Submission()
    {
        // Define the post type
        // config.registers.posts[x].type
        $post_type = 'get_in_touch';
        // $errors = [];

        // Make shure we are on Front side only
        if (!is_admin())
        {
            // Make shure we recieve the form we have generate
            if ($_SERVER['REQUEST_METHOD'] === 'POST'
                && isset($_POST[$post_type.'_nonce'])
                && wp_verify_nonce($_POST[$post_type.'_nonce'], $post_type)
            ) {

                // retrieve Plugin config and Custom post Schema
                $config = (object) apply_filters('get_in_touch', false);
                $schema = isset($config->Schemas->CustomPosts)
                    ? $config->Schemas->CustomPosts
                    : [];

                // Add $_POST data to $_REQUEST[PluginNamespace]
                $_REQUEST[$config->Namespace] = $_POST;

                // Format responses
                $responses = PPM::responses([
                    "config" => $config,
                    "schema" => $schema[$post_type]
                ]);
                
                // check response validation
                $validate = PPM::validate([
                    "config" => $config,
                    "responses" => $responses
                ]);
                
                if ($validate->isValide)
                {
                    $post_id = wp_insert_post([
                        'post_title'    => wp_strip_all_tags( $responses['name']->value ),
                        'post_content'  => $responses['message']->value,
                        'post_type'     => $post_type,
                        'post_status'   => 'publish'
                    ]); 
                    update_post_meta( $post_id, "name", $responses['name']->value );
                    update_post_meta( $post_id, "email", $responses['email']->value );
                    update_post_meta( $post_id, "phone", $responses['phone']->value );
                    update_post_meta( $post_id, "message", $responses['message']->value );

                }
                else
                {
                    // $errors = $validate->errors;
                    $_SESSION[$post_type]['success'] = $validate->success;
                    $_SESSION[$post_type]['errors'] = $validate->errors;
                }
            }        
        }

        // redirect user
        if ( wp_redirect( $_POST['_wp_http_referer'] ) ) {
            exit;
        }
    }
} 