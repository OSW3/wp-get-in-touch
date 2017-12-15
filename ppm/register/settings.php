<?php

if (!class_exists('PPM_RegisterSettings'))
{
    class PPM_RegisterSettings extends PPM
    {
        private $config;
        private $errors = [];
        
        /**
         * Constructor
         */
        public function __construct( $params )
        {
            $this->config = $params['config'];
            
            // Inject links of the settings page
            if (isset($this->config->Menus))
            {
                foreach ($this->config->Menus['locations'] as $menu)
                {
                    switch ($menu)
                    {
                        // Main Admin Menu
                        case 'admin':
                            add_action(
                                'admin_menu', 
                                function () {
                                    add_menu_page( 
                                        $this->config->Name, 
                                        __($this->config->Name, $this->config->Namespace),
                                        'manage_options', 
                                        $this->config->Namespace, 
                                        array($this, 'settings_builder'),
                                        $this->config->Menus['icon']
                                    );
                                }
                            );
                            break;

                        // Avtive / Deactivate plugin menu
                        case 'action':
                            $basename = plugin_basename($this->config->Path."index.php");
                            add_filter(
                                "plugin_action_links_".$basename, 
                                function ($links) {
                                    $settings_link = '<a href="options-general.php?page='.$this->config->Namespace.'">' . __( 'Settings', WPPPM_TEXTDOMAIN ) . '</a>';
                                    array_push( $links, $settings_link );
                                    return $links;
                                }
                            );
                            break;

                        // Submenu of Settings Menu
                        case 'settings':
                            add_action( 
                                'admin_menu', 
                                function () {
                                    add_options_page( 
                                        $this->config->Name, 
                                        __($this->config->Name, $this->config->Namespace),
                                        'manage_options', 
                                        $this->config->Namespace,
                                        array($this, 'settings_builder')
                                    );
                                }
                            );
                            break;
                    }
                }
            }
        }

        /**
         * Settings Builder
         * Process to build the settings page
         */
        public function settings_builder()
        {
            $this->settings_submission();
            $this->settings_view();
        }

        /**
         * Settings Form Builder
         * Generate the settings page
         */
        public function settings_view()
        {
            global $schema;

            $schema = $this->schema();
            $params = $this->config->Registers->Settings;

            $settings_view_file = $this->config->Path."views/settings/index.php";
            $generate_view = true;
            
            // Page settings from a view file
            if (true === $params->view)
            {
                if (file_exists($settings_view_file))
                {
                    include_once $settings_view_file;
                    $generate_view = false;
                }
            }

            // Page settings generated automaticaly
            if ($generate_view)
            {
                $has_file = false;

                echo '<div class="wrap wpppm">';
                echo '<h3>'.$this->config->Name.'</h3>';
                echo '<p>'.$this->config->Description.'</p>';
                echo '<p>';
                echo __('Version', WPPPM_TEXTDOMAIN ).' '.$this->config->Version;
                echo ' - ';
                echo '<a href="'.$this->config->PluginUri.'" target="_blank">'.__('more info', WPPPM_TEXTDOMAIN ).'</a>';
                echo '</p>';
                echo '<hr>';
    
                if ('POST' === $_SERVER['REQUEST_METHOD'])
                {
                    if (empty($this->errors))
                    {
                        echo PPM::notice(
                            "success",
                            __('The form has been updated successfully.', WPPPM_TEXTDOMAIN )
                        );
                    } 
                    else
                    {
                        echo PPM::notice(
                            "error",
                            __('The form contains errors.', WPPPM_TEXTDOMAIN )
                        );
                    }
                }

                // Define if the form has 
                foreach ( $schema as $section_key => $section_data )
                {
                    if (isset($section_data['schema'])) 
                    {
                        foreach ( $section_data["schema"] as $field )
                        {
                            if ($field['type'] === 'file')
                            {
                                $has_file = true;
                            }
                        }
                    }
                }

                echo '<form method="post" novalidate';
                echo $has_file ? ' enctype="multipart/form-data"' : null;
                echo '>';
    
                foreach ( $schema as $section_key => $section_data )
                {
                    $section_namespace = $section_data['namespace'].$section_data['ID'];
                    
                    add_settings_section(
                        $section_namespace,
                        __($section_data['title'], $this->config->Namespace ),
                        function ($data)
                        {
                            global $schema;
                            foreach ($schema as $section)
                            {
                                if ($data['id'] === $section['namespace'].$section['ID'])
                                {
                                    echo __($section['description'], $this->config->Namespace );
                                }
                            }
                        },
                        $this->config->Namespace
                    );
    
                    if (isset($section_data['schema'])) 
                    {
                        foreach ($section_data['schema'] as $field) 
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
                                    "addLabelTag"       => false, 
                                    "addWrapper"        => false, 
                                    "attrNameAsArray"   => true,
                                    "schemaID"          => "Settings",
                                    "errors"            => $this->errors
                                ]);
                                
                                
                                // Prepare field for File Type
                                if ('file' === $field->type && (!isset($field->preview) || false !== $field->preview))
                                {
                                    $callback = "echo '". $formType->render( $field->value, true ) ."';";
                                }

                                // Prepare field for other
                                else
                                {
                                    $callback = "echo '". $formType->render() ."';";
                                }

                                
                                // Add field
                                add_settings_field( 
                                    $field->key, 
                                    __($field->label, $this->config->Namespace ),
                                    create_function("", $callback),
                                    $this->config->Namespace, 
                                    $section_namespace
                                );
                            }
                        }
                    }
                }
                
                settings_fields( $this->config->Namespace );
                do_settings_sections( $this->config->Namespace );
                submit_button();
    
                echo '</form>';
                echo '</div>';
            }
            
        }

        /**
         * Settings Form Submit
         * Check and save the settings form
         */
        private function settings_submission()
        {
            if ('POST' === $_SERVER['REQUEST_METHOD']) 
            {
                // Format responses
                $responses = PPM::responses([
                    "config" => $this->config,
                    "schema" => $this->schema()
                ]);
                
                // check response validation
                $validate = PPM::validate([
                    "config" => $this->config,
                    // "post_type" => $post_type,
                    "responses" => $responses
                ]);

                $this->errors = $validate->errors;
                
                // Save files
                foreach ($responses as $key => $response)
                {
                    if ('file' === $response->type && !empty($response->files))
                    {
                        $uploads = PPM::upload( $response, null, $this->config );
    
                        if (isset($uploads['errors']))
                        {
                            $this->errors = array_merge(
                                $this->errors,
                                $uploads['errors']
                            );
                        }
                        else if (!empty($uploads))
                        {
                            $responses[$key] = $uploads;
                        }
                    }
                }

                // Update database ...
                if (true === $validate->isValid)
                {
                    $responses = array_merge(
                        $this->config->Options, 
                        PPM::responses_sanitized($responses)
                    );
                    
                    update_option(
                        $this->config->Namespace, 
                        $responses
                    );
                }
            }
        }

        /**
         * Settings Form Schema
         * Get schema for the settings
         */
        public function schema()
        {
            $namespace = $this->config->Namespace;
            $options = get_option( $namespace );

            $schema = isset($this->config->Schemas->Settings)
                ? $this->config->Schemas->Settings
                : [];

            foreach ( $schema as $section_key => $section_value )
            {
                if (!isset($section_value['ID']))
                    $schema[$section_key]['ID'] = uniqid();
                    
                if (!isset($section_value['namespace']))
                    $schema[$section_key]['namespace'] = $this->config->Namespace.'_'.$schema[$section_key]['ID'];
                    
                if (!isset($section_value['schema']))
                    $schema[$section_key]['schema'] = [];

                foreach ($schema[$section_key]['schema'] as $field_key => $field_value) 
                {
                    if (isset($field_value['key']))
                    {
                        if (!isset($field_value['ID']))
                            $schema[$section_key]['schema'][$field_key]['ID'] = uniqid();
                        
                        $value = null;
                        
                        if (isset($field_value['default'])) $value = $field_value['default'];
                        if (isset($options[$field_value['key']])) $value = $options[$field_value['key']];
                        
                        $schema[$section_key]['schema'][$field_key]['value'] = $value;
                        
                        if (!isset($field_value['section']))
                        $schema[$section_key]['schema'][$field_key]['section'] = $schema[$section_key]['ID'];
                    }
                }
            }
            return $schema;
        }
    }
}
