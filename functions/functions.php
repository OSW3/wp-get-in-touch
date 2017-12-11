<?php

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) 
{
    echo "Hi there!<br>Do you want to plug me ?<br>";
	echo "If you looking for more about me, you can read at http://osw3.net/wordpress/plugins/please-plug-me/";
    exit;
}

if (!function_exists('GetInTouch_GetNewMessages'))
{
    function GetInTouch_GetNewMessages()
    {
        return new WP_Query([
            'post_type' => 'get_in_touch',

            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => 'isRead',
                    'value' => '1',
                    'compare' => '!='
                ],
                [
                    'key' => 'isRead',
                    'compare' => 'NOT EXISTS'
                ]
            ]
        ]);
    }
}

// add_action('admin_head', 'hidePreviewButtonSaAdmin');
// function hidePreviewButtonSaAdmin() {
//   echo '<style>
//   #postbox-container-1 {
//             display:none !important;
//         }               
//       </style>';
// }