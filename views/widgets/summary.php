<?php 
// the query
$query = GetInTouch_GetNewMessages();

// New Message list
if (count($query->posts) > 0):

    foreach($query->posts as $post): 
        $message_link = add_query_arg( array(
            'post' => $post->ID,
            'action' => 'edit',
        ), admin_url('post.php') );
?>
    <div>
        <a href="<?= $message_link ?>">
            <strong><?= get_post_meta($post->ID, 'name', true) ?> <small>(<?= get_post_meta($post->ID, 'email', true) ?>)</small></strong><br>
        </a>
        <?= get_post_meta($post->ID, 'message', true) ?>
    </div>
<?php 
    endforeach;

// No message
else:
    echo __("No message", $this->config->Namespace);
endif;
?>