<?php

if (!class_exists('PPM_RegisterWidgets'))
{
    class PPM_RegisterWidgets extends PPM
    {
        private $config;
        private $widgets;

        /**
         * Constructor
         */
        public function __construct( $params )
        {
            $this->config = $params['config'];
            $this->widgets = $this->config->Widgets;

            // Load admin widgets
            if (is_admin())
            {
                add_action( 'current_screen', [$this, 'load_widgets'] );
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
                        $view_path = $this->config->Path."views/widgets/";
                        $view_file = $view_path.$widget->args['view'].".php";
                        

                        if (file_exists($view_file))
                        {
                            wp_add_dashboard_widget(
                                $widget->ID, 
                                __($widget->label, $this->config->Namespace), 
                                function($var, $params)
                                {
                                    $view_path = $this->config->Path."views/widgets/";
                                    $view_file = $view_path.$params['args']['view'].".php";
                                    
                                    if (file_exists($view_file))
                                    {
                                        include_once $view_file;
                                    }
                                },
                                $widget->control,
                                $widget->args
                            );
                        }
                    }
                });
            }
        }
    }
}