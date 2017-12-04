<?php
$fields = $args['callback'][0]->config->SchemasFields->CustomPosts[$this->type];
$post_ID = $wp_post->ID;

// Set as Read
update_post_meta($post_ID, "isRead", "1");

// -- Message Data

// Is read
$isRead = get_post_meta($post_ID, 'isRead', true);
if (1 != $isRead)
{
    $isRead = "Non lu";
}
else
{
    $isRead = "Lu";
}

// Message date
$message_date = new DateTime($wp_post->post_date);
$message_date = $message_date->format("d-m-Y à H \h i \m\i\\n");

// Message sender
$message_sender_name = get_post_meta($post_ID, 'name', true);
$message_sender_email = get_post_meta($post_ID, 'email', true);
$message_sender_phone = get_post_meta($post_ID, 'phone', true);

// Message 
$message_content = get_post_meta($post_ID, 'message', true);


if (isset($_SESSION[$this->type])) unset($_SESSION[$this->type]);
?>


<!-- Message Date -->
<div>
    <strong><?= __("__ Date", $this->config->Namespace) ?></strong> : <?= $message_date ?>
    <hr>
</div>

<!-- Message Sender -->
<div>
    <strong><?= __("__ Expéditeur", $this->config->Namespace) ?></strong> : <?= $message_sender_name ?> 
    <?php if (!empty(message_sender_email)): ?>
    <small>(<?= $message_sender_email ?>)</small>
    <?php endif; ?>
    <hr>
</div>
<div>
    <strong><?= __("__ Phone", $this->config->Namespace) ?></strong> : <?= $message_sender_phone ?> 
    <hr>
</div>

<!-- Message -->
<div>
    <strong><?= __("__ Message", $this->config->Namespace) ?></strong> : <br>
    <?= $message_content ?> 
</div>
