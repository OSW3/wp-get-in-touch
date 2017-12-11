<?php 
// the query
$query = GetInTouch_GetNewMessages();

?>

<?php if (count($query->posts) > 0): ?>
    <ul class="widget-get-in-touch widget-list">
    <?php foreach($query->posts as $post): ?>
    <?php
        $message_link = add_query_arg( array(
            'post' => $post->ID,
            'action' => 'edit',
        ), admin_url('post.php') );
    ?>
        <li class="widget-list-item wp-clearfix">
            <div class="item-header wp-clearfix">
                <div class="sender">
                    <?= get_post_meta($post->ID, 'name', true) ?> <small>(<?= get_post_meta($post->ID, 'email', true) ?>)</small>
                </div>
                <div class="date">
                    <?= PPM::date("D d M Y H:i", $wp_post->post_date) ?>
                </div>
            </div>
            <div class="item-content">
                <a href="<?= $message_link ?>">
                    <?php
                        $message = get_post_meta($post->ID, 'message', true);
                        $message_length = strlen($message);

                        if ($message_length > 200)
                        {
                            $message = substr($message, 0, 200);
                        }
                    ?>
                    <?= $message ?>
                </a>
            </div>
        </li>
    <?php endforeach; ?>
    </ul>
<?php else: ?>
    <?= __("There is no new message.", $this->config->Namespace); ?>
<?php endif; ?>