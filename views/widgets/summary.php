<?php 
// the query
$posts_per_page = 6;
$query = GetInTouch_GetNewMessages( $posts_per_page );
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
                    <a href="<?= $message_link ?>">
                        <?= get_post_meta($post->ID, 'name', true) ?> <small>(<?= get_post_meta($post->ID, 'email', true) ?>)</small>
                    </a>
                </div>
                <div class="date">
                    <a href="<?= $message_link ?>">
                        <?= PPM::date("D d M Y H:i", $post->post_date) ?>
                    </a>
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

    <?php if ($query->found_posts > $posts_per_page): ?>
        <li class="widget-list-item wp-clearfix">
            <a href="<?= add_query_arg( array(
                'post_type' => 'get_in_touch',
            ), admin_url('edit.php') ); ?>">View <?= ($query->found_posts - $posts_per_page) ?> more</a>
        </li>
    <?php endif; ?>
    </ul>
<?php else: ?>
    <?= __("There is no new message.", $this->config->Namespace); ?>
<?php endif; ?>