<?php

if (!class_exists('PPM_RegisterWidgets'))
{
    class PPM_RegisterWidgets extends PPM
    {
        private $config;
        private $widgets;
        private $viewdir;

        /**
         * Constructor
         */
        public function __construct( $params )
        {
            $this->config = $params['config'];
            $this->widgets = $this->config->Widgets;
            $this->viewdir = $this->config->Path."views/widgets/";            

            // Load admin widgets
            if (is_admin())
            {
                add_action( 'current_screen', [$this, 'load_widgets'] );
                add_action( 'wp_ajax_widgets_action', [$this, 'widget_action']);
                add_action( 'wp_ajax_nopriv_widgets_action', [$this, 'widget_action']);
            }
        }


        /**
         * Add Widgets
         */
        public function load_widgets() 
        {
            $currentScreen = get_current_screen();

            if ("dashboard" === $currentScreen->id)
            {
                add_action('wp_dashboard_setup', function() {

                    foreach ($this->widgets as $key => $widget)
                    {

                        switch ($widget->type)
                        {
                            case 'comments':
                                $callback = "load_widget_comments";
                                break;

                            case 'quick-add':
                                $callback = "load_widget_quickadd";
                                break;

                            case 'list':
                                $callback = "load_widget_list";
                                break;

                            case 'view':
                                $callback = "load_widget_view";
                                break;
                        }

                        wp_add_dashboard_widget(
                            $widget->ID,
                            __($widget->label, $this->config->Namespace),
                            [$this, $callback],
                            $widget->control,
                            $widget->args
                        );
                    }
                });
            }
        }

        public function load_widget_comments($var, $params)
        {
            // echo "load_widget_comments";

            // Inject the script with the Ajax Query and pass some parameters
            $this->widget_script([
                "widget_ID" => $params['args']['widget_ID'],
                "posttype" => $params['args']['posttype'],
                "param1" => "Value 1",
                "param2" => "Value 2",
            ]);
        }

        public function load_widget_quickadd($var, $params)
        {
            // echo "load_widget_quickadd";

            // Inject the script with the Ajax Query and pass some parameters
            $this->widget_script([
                "widget_ID" => $params['args']['widget_ID'],
                "posttype" => $params['args']['posttype'],
                "param1" => "Value 1",
                "param2" => "Value 2",
            ]);
        }

        public function load_widget_list($var, $params)
        {
            $template_ID = "list-item-template";

            // List container
            echo "<ul id=\"".$params['args']['widget_ID']."-list\" class=\"widget-list\"></ul>";

            // List item (JS template)
            echo "<script data-template=\"$template_ID\" type=\"text/x-jquery-tmpl\">";
            echo "<li class=\"widget-list-item wp-clearfix\">";
                echo "<div class=\"item-title\">";
                    echo "<a href=\"".add_query_arg([ "post" => "\${ID}", "action" => "edit" ], admin_url('post.php'))."\">";
                        echo "\${post_title}";
                    echo "</a>";
                echo "</div>";
                echo "<div class=\"item-date\">";
                        echo "\${post_date}";
                echo "</div>";
            echo "</li>";
            echo "</script>";

            // Inject the script with the Ajax Query and pass some parameters
            $this->widget_script([
                // JS Function on ajax callback
                "callback" => "widget_action_callback",

                // JS Template ID
                "template_ID" => $template_ID,

                // ID of the widget
                "widget_ID" => $params['args']['widget_ID'],

                // WP_Query params
                "posttype" => $params['args']['posttype'],
                "per_page" => 3,
            ]);
        }

        /**
         * Load widget from a specific view
         */
        public function load_widget_view($var, $params)
        {
            // View is not defined
            if (!isset($params['args']['widget_view']) || empty($params['args']['widget_view']))
            {
                $this->widget_notice(
                    "danger",
                    __("Undefined view file.", WPPPM_TEXTDOMAIN) // TODO: Translation
                );
                return;
            }

            $file = $params['args']['widget_view'] . ".php";
            $view = $this->viewdir . $file;

            // View file not found
            if (!file_exists($view))
            {
                $this->widget_notice(
                    "danger",
                    __("View file is not found.", WPPPM_TEXTDOMAIN), // TODO: Translation
                    $view
                );
                return;
            }

            include_once $view;
            return;
        }

        /**
         * Widget Notice
         */
        private function widget_notice( $type, $title, $message=null )
        {
            $class = [];

            array_push($class, "widget-notice");
            array_push($class, "notice-".$type);

            echo '<div class="'. implode(" ", $class) .'">';
            echo "<h4>".$title ."</h4>";
            if (null != $message)
            {
                echo "<p>".$message ."</p>";
            }
            echo '</div>';
        }

        private function widget_script( $params )
        {
            $callback = $params['callback'];
            unset($params['callback']);

            $params = array_merge($params, [
                "action" => "widgets_action"
            ]);

            echo "<script type=\"text/javascript\">jQuery(document).ready(function($) { jQuery.post( '".admin_url('admin-ajax.php')."', ".json_encode($params).",  function(r) { ".$callback."(r); }); });</script>";
        }
        public function widget_action()
        {
            $query = [];

            if (isset($_POST['posttype']))
            {
                $query['post_type'] = $_POST['posttype'];
            }

            if (isset($_POST['per_page']))
            {
                $query['posts_per_page'] = $_POST['per_page'];
            }

            $wp_query = new WP_Query($query);

            echo json_encode(array(
                "widget_ID"     => $_POST['widget_ID'],
                "template_ID"   => $_POST['template_ID'],
                "post_count"    => $wp_query->post_count,
                "found_posts"   => $wp_query->found_posts,
                "posts"         => $wp_query->posts
            ));
            wp_die(); 
        }
    }
}