<?php
$fields = $args['callback'][0]->config->SchemasFields->CustomPosts[$this->type];
$post_ID = $wp_post->ID;

// Set as Read
update_post_meta($post_ID, "isRead", "1");

// -- Message Data

// Is read
$isRead = get_post_meta($post_ID, 'isRead', true);

// Message date
$message_date = PPM::date("l d F Y à H \h i \m\i\\n", $wp_post->post_date);

// Message sender
$message_sender_name = get_post_meta($post_ID, 'name', true);
$message_sender_email = get_post_meta($post_ID, 'email', true);
$message_sender_phone = get_post_meta($post_ID, 'phone', true);

// Message 
$message_content = get_post_meta($post_ID, 'message', true);
?>



<div class="item-header wp-clearfix">
    <div class="sender">
        <i class="dashicons dashicons-admin-users"></i> <?= $message_sender_name ?> 
        <?php if (!empty(message_sender_email)): ?><small>(<?= $message_sender_email ?>)</small><?php endif; ?>
    </div>
    <div class="date">
        <?= $message_date ?>
    </div>
</div>

<hr>

<div>
    <i class="dashicons dashicons-phone"></i> <?= $message_sender_phone ?> 
</div>

<hr>

<!-- Message -->
<div>
    <i class="dashicons dashicons-email"></i> <?= nl2br($message_content) ?> 
</div>

<?php 
// Force la suppression de la session
if (isset($_SESSION['get_in_touch']))
{
    if ($_SESSION['get_in_touch']['isValid'] == 1)
    {
        unset($_SESSION['get_in_touch']);
    }
}
?>