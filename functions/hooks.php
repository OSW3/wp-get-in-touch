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
        $options = get_option('get_in_touch');

        // Make shure we are on Front side only
        if (!is_admin())
        {
            // Make shure we recieve the form we have generate
            if ($_SERVER['REQUEST_METHOD'] === 'POST'
                && isset($_POST[$post_type.'_nonce'])
                && wp_verify_nonce($_POST[$post_type.'_nonce'], $post_type)
            ) {
                
                // -- RETRIEVE AND CONTROL
                // --

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
                    "post_type" => $post_type,
                    "responses" => $responses
                ]);
                
    
                // -- SAVE DATA
                // --

                
                if ($validate->isValid)
                {
                    $senderName = $responses['firstname']->value." ".$responses['lastname']->value;
                    $post_id = wp_insert_post([
                        'post_title'    => wp_strip_all_tags( $senderName ),
                        'post_content'  => $responses['message']->value,
                        'post_type'     => $post_type,
                        'post_status'   => 'private',
                        'comment_status'=> 'closed',
                        'ping_status'   => 'closed', 
                    ]); 
                    update_post_meta( $post_id, "name", $senderName );
                    update_post_meta( $post_id, "email", $responses['email']->value );
                    update_post_meta( $post_id, "phone", $responses['phone']->value );
                    update_post_meta( $post_id, "message", $responses['message']->value );
                    update_post_meta( $post_id, "isRead", "0" );

                    $post_date = get_post($post_id)->post_date;
                    
                    
                    // -- SEND NOTIFICATION
                    // --

                    // Header
                    $headers  = "MIME-Version: 1.0" . "\r\n";
                    $headers .= "Content-type: text/html; charset=".get_bloginfo('charset')."" . "\r\n";
                    $headers .= "From: ". get_option('blogname') ." <". get_option('admin_email') .">" . "\r\n";

                    // Subject
                    $notification_title = $settings['notification_title'];
                    $notification_title = preg_replace("/\[\[blogname\]\]/", get_option('blogname'), $notification_title);

                    // Message
                    $notification_body = $options['notification_body'];
                    $notification_body = preg_replace("/\[\[blogname\]\]/", get_option('blogname'), $notification_body);
                    $notification_body = preg_replace("/\[\[name\]\]/", $senderName, $notification_body);
                    $notification_body = preg_replace("/\[\[email\]\]/", $responses['email']->value, $notification_body);
                    $notification_body = preg_replace("/\[\[phone\]\]/", $responses['phone']->value, $notification_body);
                    $notification_body = preg_replace("/\[\[message\]\]/", $responses['message']->value, $notification_body);
                    $notification_body = preg_replace("/\[\[datetime\]\]/", PPM::date("D d M Y H:i", $post_date), $notification_body);
                    $notification_body = preg_replace("/\\n/ius", "<br>", $notification_body);

                    // Send
                    $to = explode("\n", $options['notification_to']);
                    foreach ($to as $key => $value) 
                    {
                        $to[$key] = preg_replace("/\[\[admin_email\]\]/", get_option('admin_email'), $value);
                        wp_mail(trim($to[$key]), $notification_title, $notification_body, $headers);
                    }
                    // echo "<pre>";
                    // print_r($to);
                    // echo "</pre>";
                    // exit;
                    
                    
                    // -- SEND COPY
                    // --

                    if ($options['send_response'] == "on")
                    {
                        // Subject
                        $response_subject = $options['response_subject'];
                        $response_subject = preg_replace("/\[\[blogname\]\]/", get_option('blogname'), $response_subject);
    
                        // Message
                        $response_body = $options['response_body'];
                        $response_body = preg_replace("/\[\[blogname\]\]/", get_option('blogname'), $response_body);
                        $response_body = preg_replace("/\[\[name\]\]/", $senderName, $response_body);
                        $response_body = preg_replace("/\[\[email\]\]/", $responses['email']->value, $response_body);
                        $response_body = preg_replace("/\[\[phone\]\]/", $responses['phone']->value, $response_body);
                        $response_body = preg_replace("/\[\[message\]\]/", $responses['message']->value, $response_body);
                        $response_body = preg_replace("/\[\[datetime\]\]/", date("d-m-Y h:i:s"), $response_body);
                        $response_body = preg_replace("/\\n/ius", "<br>", $response_body);
                        
                        wp_mail($responses['email']->value, $response_subject, $response_body, $headers);

                    }
                }
            }        
        }

        // redirect user
        if (isset($_POST['_wp_http_referer']))
        {
            if (wp_redirect( $_POST['_wp_http_referer'] ))
            {
                exit;
            }
        }
    }
} 
