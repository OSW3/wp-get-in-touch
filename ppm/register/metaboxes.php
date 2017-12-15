<?php

if (!class_exists('PPM_RegisterMetaboxes'))
{
    class PPM_RegisterMetaboxes extends PPM
    {
        private $config;
        private $settings;
        private $schemas;
        private $type;
        
        /**
         * Constructor
         */
        public function __construct( $params )
        {
            $this->config = $params['config'];
            $this->settings = $params['settings'];
            $this->schemas = $params['schemas'];
            $this->type = $params['type'];
            
            $this->add_metaboxes();
        }


        /**
         * Add "Novalidate" Attributes
         */
        public function add_form_novalidate()
        {
            echo ' novalidate="novalidate"';
        }
        
        /**
         * Add "enctype" Attributes
         */
        public function add_form_enctype()
        {
            echo ' enctype="multipart/form-data"';
        }
        
        /**
         * Admin Notice
         */
        public function add_warning_notices()
        {
            echo "<style>#message{display: none;}</style>";
            echo PPM::notice(
                "warning",
                __("The form has been saved with errors.", WPPPM_TEXTDOMAIN)
            );
        }
        public function add_success_notices()
        {
            echo "<style>#message{display: none;}</style>";
            echo PPM::notice(
                "success",
                __("The form has been saved successfully.", WPPPM_TEXTDOMAIN)
            );
        }


        /**
         * Generate MetaBox
         */
        public function add_metaboxes()
        {
            $settings = $this->settings;
            $schemas = $this->schemas;
            $type = $this->type;
            $metaboxes = isset($schemas[$type]) ? $schemas[$type] : null;
            $add_enctype = false;
            $add_novalidate = true;
            $show_admin_permalink = true;

            $metabox_view_file = $this->config->Path."views/metaboxes/".$type.".php";
            $generate_view = true;

            // Metabox form a view file
            foreach ($settings as $post)
            {
                if ($type === $post['type'])
                {
                    if (isset($post['view']) && true === $post['view'])
                    {
                        if (file_exists($metabox_view_file))
                        {

                            add_meta_box(
                                "metabox_".$type, 
                                __($post['label'], $this->config->Namespace), 
                                [$this, 'metabox_view'], 
                                $type,
                                'normal', 
                                'high', 
                                []
                            );
            
                            $generate_view = false;
                        }
                    }
                }
            }

            // Metaboxes generated automaticaly
            if ($generate_view)
            {
                if (is_array($metaboxes))
                {
                    foreach ($metaboxes as $key => $metabox)
                    {
                        $id             = "metabox_".$type."_".$key;
                        $title          = !empty($metabox['title']) ? $metabox['title'] : "-";
                        $callback       = [$this, 'metabox_fields'];
                        $screen         = $type;
                        $context        = !empty($metabox['context']) ? $metabox['context'] : 'normal';
                        $priority       = !empty($metabox['priority']) ? $metabox['priority'] : 'high';
                        $callback_args  = !empty($metabox['callback_args']) ? $metabox['callback_args'] : [];
                        add_meta_box( $id, $title, $callback, $screen, $context, $priority, $callback_args );
        
                        if (isset($metabox['schema']))
                        {
                            if ($add_novalidate)
                            {
                                if (isset($settings[$key]['novalidate']) && $settings[$key]['novalidate'] === false)
                                {
                                    $add_novalidate = false;
                                }
                            }
        
                            if ($show_admin_permalink)
                            {
                                if (isset($settings[$key]['show_admin_permalink']) && $settings[$key]['show_admin_permalink'] === false)
                                {
                                    $show_admin_permalink = false;
                                }
                            }
        
                            foreach ($metabox['schema'] as $field)
                            {
                                if ($field['type'] === "file")
                                {
                                    $add_enctype = true;
                                }
                            }
                        }
                    }
                }
            }

            if ($add_novalidate)
            {
                add_action('post_edit_form_tag', array( $this, 'add_form_novalidate'));
            }

            if ($add_enctype)
            {
                add_action('post_edit_form_tag', array( $this, 'add_form_enctype'));
            }
            
            if (!$show_admin_permalink)
            {
                add_filter( 'get_sample_permalink_html', function(){ return false; } );
            }

            if (isset($_SESSION[$type]) && !empty($_SESSION[$type]))
            {
                if (isset($_SESSION[$type]['errors']))
                {
                    add_action('admin_notices', array($this, 'add_warning_notices'));
                }
                else
                {
                    add_action('admin_notices', array($this, 'add_success_notices'));
                }
            }
        }

        /**
         * Generate a metabox form view
         */
        public function metabox_view( $wp_post, $args )
        {
            $metabox_view_file = $this->config->Path."views/metaboxes/".$this->type.".php";
            include_once $metabox_view_file;
        }

        /**
         * Generate Metaboxes automaticaly
         */
        public function metabox_fields( $wp_post, $args )
        {
            $output = '';
            $path = $this->config->Path;
            $settings = $this->settings;
            $schemas = $this->schemas;
            $type = $this->type;
            $metaboxes = isset($schemas[$type]) ? $schemas[$type] : null;

            wp_nonce_field( $this->config->Namespace, $this->config->Namespace.'[_wpnonce]' );

            foreach ($metaboxes as $key => $metabox)
            {
                $metabox_id = "metabox_".$type."_".$key;
                $generate_view = true;

                if ($metabox_id === $args['id'])
                {
                    // Metabox content in a View file
                    if (isset($metabox['view']) && !empty($metabox['view']))
                    {
                        $metabox_view_file = $this->config->Path."views/metabox_section.".$metabox['view'].".php";

                        if (file_exists($metabox_view_file))
                        {
                            include_once $metabox_view_file;
                            $generate_view = false;
                        }
                    }

                    // Metabox generated automaticaly
                    if ($generate_view)
                    {
                        // Metabox content generated
                        if (isset($metabox['schema']))
                        {
                            foreach ($metabox['schema'] as $field)
                            {
                                $field = (object) $field;
                                
                                if (isset($field->key))
                                {
                                    require_once $this->config->Path.'ppm/form/form.php';
                                    require_once $this->config->Path.'ppm/form/'.$field->type.'.php';
                                    
                                    $classType = ucfirst(strtolower($field->type));
                                    $classType = "PPM_".$classType."Type";
    
                                    $formType = new $classType([
                                        "config"            => $this->config,
                                        "attributes"        => $field, 
                                        "addLabelTag"       => true, 
                                        "addWrapper"        => true, 
                                        "attrNameAsArray"   => true,
                                        "schemaID"          => "CustomPosts",
                                        "errors"            => isset($_SESSION[$type]['errors']) ? $_SESSION[$type]['errors'] : []
                                    ]);
    
                                    // Show preview for files
                                    if ('file' === $field->type && false !== $field->preview)
                                    {
                                        $value = $formType->getValue();
                                        $output.= $formType->render( $value, true );
                                    }
                                    else
                                    {
                                        $output.= $formType->render();
                                    }
                                }
                            }
                        }
                    }

                    $isLastMetaBox = $key === count($metaboxes)-1;
                }
            }

            echo $output;

            if ($isLastMetaBox)
            {
                if (isset($_SESSION[$type]))
                {
                    unset($_SESSION[$type]);
                }
            }
        }
        
    }
}