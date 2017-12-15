<?php

if (!class_exists('PPM_RegisterPosts'))
{
    class PPM_RegisterPosts extends PPM
    {
        private $config;
        private $posts;
        private $schema;
        private $type;
        private $available_capability_type = ['post', 'page'];
        private $available_ep_mask = ["EP_NONE", "EP_PERMALINK", "EP_ATTACHMENT", "EP_DATE", "EP_YEAR", "EP_MONTH", "EP_DAY", "EP_ROOT", "EP_COMMENTS", "EP_SEARCH", "EP_CATEGORIES", "EP_TAGS", "EP_AUTHORS", "EP_PAGES", "EP_ALL_ARCHIVES", "EP_ALL"];
        private $posts_type = [];

        /**
         * Constructor
         */
        public function __construct( $params )
        {
            $this->config = $params['config'];
            
            $this->setPostType();
            $this->setPostsSettings();
            $this->setPostsSchemas();
            $this->add_custom_posts();
        }
        
        
        /**
         * Set Custom posts settings
         */
        private function setPostsSettings()
        {
            $this->posts = isset($this->config->Registers->CustomPosts)
                ? (array) $this->config->Registers->CustomPosts
                : [];
        }
        /**
         * Get ScCustom posts settingshema
         */
        private function getPostsSettings()
        {
            return $this->posts;
        }
        
        
        /**
         * Set Schema for custom posts
         */
        private function setPostsSchemas()
        {
            $this->schema = isset($this->config->Schemas->CustomPosts)
                ? $this->config->Schemas->CustomPosts
                : [];
        }
        /**
         * Get Schema
         */
        private function getPostsSchemas()
        {
            return $this->schema;
        }

        /**
         * 
         */
        private function setPostType()
        {
            if (isset($_GET['post_type']))
            {
                $this->type = $_GET['post_type'];
            }
            elseif (isset($_GET['post'])) 
            {
                $this->type = get_post_type($_GET['post']);
            }
            else 
            {
                $this->type = null;
            }
        }
        /**
         * 
         */
        private function getPostType()
        {
            return $this->type;
        }


        /**
         * Add Custom Posts (to the register)
         * Custom Posts Settings
         */
        private function add_custom_posts()
        {
            $settings = $this->getPostsSettings();
            $schemas = $this->getPostsSchemas();

            foreach ($settings as $key => $post)
            {
                $post = (object) $post;

                // Generate default post Key
                if (!isset($post->type))
                {
                    $type = $this->config->Namespace;
                    $type.= "_custom_post_";
                    $type.= $key;

                    $type = substr(md5($type), 0, 19);
                    $type = "_".$type;

                    $post->type = $type;
                }

                // Format the post key
                // Must not exceed 20 characters
                if (strlen($post->type) > 20)
                {
                    $post->type = substr($post->type, 0, 20);
                }


                // Menu label
                if (!isset($post->label))
                {
                    $post->label = $this->config->Name;
                }
                $post->label = __($post->label, $this->config->Namespace);
                
                // Menu position
                if (isset($post->menu_position))
                {
                    if (!is_integer($post->menu_position))
                    {
                        $post->menu_position = intval($post->menu_position);
                        
                        if (0 === $post->menu_position)
                        {
                            $post->menu_position = null;
                        }
                    }
                }
                else
                {
                    $post->menu_position = null;
                }
                
                // Menu Icon
                if (!isset($post->menu_icon) || !preg_match("/^(dashicons-|image:)/i", $post->menu_icon))
                {
                    $post->menu_icon = "dashicons-welcome-add-page";
                }
                else if (preg_match("/^image:/i", $post->menu_icon))
                {
                    $image = preg_replace("/^image:/", null, $post->menu_icon);
                    $path_image = $this->config->Path."assets/images/".$image;
                    $url_image = $this->config->Url."assets/images/".$image;

                    if (file_exists($path_image))
                    {
                        $post->menu_icon = $url_image;
                    }
                    else
                    {
                        $post->menu_icon = "dashicons-welcome-add-page";
                    }
                }
                
                
                // Post description
                if (!isset($post->description))
                {
                    $post->description = $this->config->Description;
                }
                
                
                // Post is public
                if (!isset($post->public) || false !== $post->public)
                {
                    $post->public = true;
                }
                
                
                // Post is hierarchical
                if (!isset($post->hierarchical) || $post->hierarchical !== true)
                {
                    $post->hierarchical = false;
                }
                
                
                // Post is excluded from front end search result
                if (!isset($post->exclude_from_search) || !is_bool($post->exclude_from_search))
                {
                    $post->exclude_from_search = !$post->public;
                }
                
                
                // Post queries can be performed
                if (!isset($post->publicly_queryable) || !is_bool($post->publicly_queryable))
                {
                    $post->publicly_queryable = $post->public;
                }
                
                
                // Generate UI
                if (!isset($post->show_ui) || !is_bool($post->show_ui))
                {
                    $post->show_ui = $post->public;
                }
                if (!isset($post->show_in_menu) || !is_bool($post->show_in_menu))
                {
                    $post->show_in_menu = $post->show_ui;
                }
                if (!isset($post->show_in_nav_menus) || !is_bool($post->show_in_nav_menus))
                {
                    $post->show_in_nav_menus = $post->show_ui;
                }
                if (!isset($post->show_in_admin_bar) || !is_bool($post->show_in_admin_bar))
                {
                    $post->show_in_admin_bar = $post->show_in_menu;
                }
                
                
                // Add the post type route in the REST API 'wp/v2' namespace
                if (!isset($post->show_in_rest) || $post->show_in_rest !== true)
                {
                    $post->show_in_rest = false;
                }
                if (!isset($post->rest_base) || !is_string($post->rest_base))
                {
                    $post->rest_base = $post->type;
                }

                if (!isset($post->rest_controller_class) || !is_string($post->rest_controller_class))
                {
                    $post->rest_controller_class = "WP_REST_Posts_Controller";
                }
                else
                {
                    $post_controller_class_directory = $this->config->Path."rest/";
                    $post_controller_class_file = $post->rest_controller_class.".php";

                    if (file_exists($post_controller_class_directory.$post_controller_class_file) && !class_exists($post->rest_controller_class))
                    {
                        include_once $post_controller_class_directory.$post_controller_class_file;
                    }
                    else 
                    {
                        $post->rest_controller_class = "WP_REST_Posts_Controller";
                    }
                }
                
                
                // Capability type
                if (!isset($post->capability_type) || !in_array($post->capability_type, $this->available_capability_type))
                {
                    $post->capability_type = "post";
                }
                
                
                // Capabilities
                // TODO: Revoir cette  // Droits d'actions
                if (!isset($post->capabilities))
                {
                    $post->capabilities = [];
                }
                array_merge($post->capabilities, [$post->capability_type => $post->capability_type."s"]);
                
                // Map Meta Cap
                if (!isset($post->map_meta_cap) || $post->map_meta_cap !== true)
                {
                    $post->map_meta_cap = false;
                }
                
                
                // Supports
                // "title", "editor", "comments", "revisions", "trackbacks", "author", "excerpt", "page-attributes", "thumbnail", "custom-fields", "post-formats"
                if (!isset($post->supports) || empty($post->supports))
                {
                    $post->supports = false;
                }
                else 
                {
                    if (!is_array($post->supports))
                    {
                        $post->supports = [$post->supports];
                    }
                }
                
                
                // Map Meta Cap
                if (!isset($post->register_meta_box_cb) || empty($post->register_meta_box_cb) || !function_exists($post->register_meta_box_cb))
                {
                    $post->register_meta_box_cb = null;
                }
                
                
                // Labels
                if (isset($post->labels) && is_array($post->labels))
                {
                    // All items
                    if (isset($post->labels['all_items']) && is_bool($post->labels['all_items']) && false === $post->labels['all_items'])
                    {
                        add_action('admin_menu', [$this, 'remove_admin_menu_all_items']);
                    }

                    // Add New
                    if (isset($post->labels['add_new']) && is_bool($post->labels['add_new']) && false === $post->labels['add_new'])
                    {
                        add_action('admin_menu', [$this, 'remove_admin_menu_add_new']);
                    }

                    // Force to delete entry
                    $post->labels['name'] = null;
                    $post->labels['menu_name'] = null;
                    $post->labels['filter_items_list'] = null;
                    $post->labels['items_list'] = null;
                    $post->labels['uploaded_to_this_item'] = null;
                    $post->labels['new_item'] = null;
                    $post->labels['update_item'] = null;
                    $post->labels['parent_item_colon'] = null;
                    $post->labels['archives'] = null;
                    $post->labels['remove_featured_image'] = null;
                    $post->labels['use_featured_image'] = null;
                    $post->labels['items_list_navigation'] = null;

                    foreach ($post->labels as $label_key => $label_value)
                    {
                        if (empty($post->labels[$label_key]))
                        {
                            unset($post->labels[$label_key]);
                        }
                        else
                        {
                            $post->labels[$label_key] = __($post->labels[$label_key], $this->config->Namespace);
                        }
                    }
                }
                
                
                
                
                // Post has archive
                if (!isset($post->has_archive) || $post->has_archive !== true)
                {
                    $post->has_archive = false;
                }
                
                
                // Rewrite
                if (!isset($post->rewrite))
                {
                    $post->rewrite = true;
                }

                if (is_array($post->rewrite))
                {
                    // Slug
                    $post->rewrite['slug'] = PPM::tryToDo( $post->rewrite['slug'] );
                    if (!isset($post->rewrite["slug"]) || empty($post->rewrite["slug"]))
                    {
                        $post->rewrite["slug"] = $post->type;
                    }
                    
                    // With Front
                    if (!isset($post->rewrite["with_front"]) || !is_bool($post->rewrite["with_front"]))
                    {
                        $post->rewrite["with_front"] = true;
                    }
                    
                    // Feeds
                    if (!isset($post->rewrite["feeds"]) || !is_bool($post->rewrite["feeds"]))
                    {
                        $post->rewrite["feeds"] = $post->has_archive;
                    }
                    
                    // Pages
                    if (!isset($post->rewrite["pages"]) || !is_bool($post->rewrite["pages"]))
                    {
                        $post->rewrite["pages"] = true;
                    }
                    
                    // EndPoint Mask
                    if (!isset($post->rewrite["ep_mask"]) || !in_array($post->rewrite["ep_mask"], $this->available_ep_mask))
                    {
                        $post->rewrite["ep_mask"] = "EP_PERMALINK";
                    }
                }
                
                
                // Can Export
                if (!isset($post->can_export) || $post->can_export !== false)
                {
                    $post->can_export = true;
                }
                
                
                // Delete Post with user delation
                if (!isset($post->delete_with_user) || !is_bool($post->delete_with_user))
                {
                    $post->delete_with_user = false;
                }
                
                
                if (isset($post->query_var)) unset($post->query_var);
                if (isset($post->_edit_link)) unset($post->_edit_link);
                if (isset($post->_builtin)) unset($post->_builtin);
                
                
                // Query Var
                if (!isset($post->query_var))
                {
                    $post->query_var = $post->type;
                }


                // Add Post Type to the Wordpress Register
                register_post_type( $post->type, (array) $post );


                // Retrieve current page post_type
                if (!isset($_REQUEST['post_type']) || null === $_REQUEST['post_type'])
                {
                    if (isset($_REQUEST['post']))
                    {
                        $wp_post = get_post($_REQUEST['post']);
                        if (isset($wp_post->post_type))
                        {
                            $_REQUEST['post_type'] = $wp_post->post_type;
                        }
                    }
                }

                // Add MetaBoxes to the form
                if (isset($_REQUEST['post_type']) && ($post->type === $_REQUEST['post_type'] && isset($schemas[ $post->type ]) && is_admin()))
                {
                    add_action('save_post', array($this, "custompost_submission"));
                    add_action('admin_init', array($this, 'customposts_view'));
                }


                // Add Post Categories
                if (isset($post->categories))
                {
                    $post->categories['hierarchical'] = true;
                    $post->categories = $this->add_taxonomy(
                        "cats_", 
                        $post->categories, 
                        $post
                    );
                }


                // Add Post Tag
                if (isset($post->tags))
                {
                    $post->tags['hierarchical'] = false;
                    $post->tags = $this->add_taxonomy(
                        "tags_", 
                        $post->tags, 
                        $post
                    );
                }

                
                // Manage Columns list for custom post
                if (isset($post->admin_columns))
                {
                    add_filter( "manage_{$post->type}_posts_columns", array($this, 'set_custom_columns') );
                    add_action( "manage_{$post->type}_posts_custom_column" , array($this, 'custom_column_data'), 10, 2 );
                    add_filter( "manage_edit-{$post->type}_sortable_columns", array($this, 'custom_columns_sortable') );
                }


                // Remove Row Actions on Items list
                if (isset($post->remove_admin_row_actions) && (true === $post->remove_admin_row_actions || is_array($post->remove_admin_row_actions)))
                {
                    add_filter( 'post_row_actions', array($this, 'remove_admin_row_actions'), 10, 1 );
                }


                // Custom Post Shortcodes
                if (isset($schemas[ $post->type ]) && !is_admin())
                {
                    // Schemas section
                    foreach ($schemas[ $post->type ] as $sections)
                    {
                        if (isset($sections['schema']))
                        {
                            $sections = $sections['schema'];
                        }

                        if (is_array($sections))
                        {
                            foreach ($sections as $field)
                            {
                                if (isset($field['key']) && (!isset($field['shortcode']) || false !== $field['shortcode']))
                                {
                                    $shortcode_ID = implode(":",array(
                                        $this->config->Namespace,
                                        $post->type,
                                        $field['key']
                                    ));
                                    
                                    add_shortcode(
                                        $shortcode_ID, 
                                        [&$this,"shortcode_callback"]
                                    );
                                }
                            }
                        }      
                    }
                }
            }
        }

        public function shortcode_callback( $attrs, $content = "", $tag )
        {
            list($namespace, $posttype, $key) = explode(":", $tag);
            $schemas = $this->getPostsSchemas();
            
            if (is_array($schemas[ $posttype ]))
            {
                foreach ($schemas[ $posttype ] as $schema)
                {
                    if (is_array($schema['schema']))
                    {
                        foreach ($schema['schema'] as $field)
                        {
                            if (isset($field['type']) && !empty($field['type']))
                            {
                                if (isset($field['key']) && $field['key'] === $key && (!isset($field['shortcode']) || false !== $field['shortcode']))
                                {
                                    foreach ($attrs as $attr_key => $attr_value)
                                    {
                                        // Boolean value
                                        if (in_array(strtolower($attr_value), ["true", "yes", "y", "on", "1"]))
                                        {
                                            $attrs[$attr_key] = true;
                                        }
                                        else if (in_array(strtolower($attr_value), ["false", "non", "n", "off", "0"]))
                                        {
                                            $attrs[$attr_key] = false;
                                        }
                                    }
                                    
                                    $field = (object) array_merge( $field, $attrs );
    
                                    require_once $this->config->Path.'ppm/form/form.php';
                                    require_once $this->config->Path.'ppm/form/'.$field->type.'.php';
                                    
                                    $classType = ucfirst(strtolower($field->type));
                                    $classType = "PPM_".$classType."Type";
    
                                    $formType = new $classType([
                                        "config"            => $this->config,
                                        "attributes"        => $field, 
                                        "addLabelTag"       => is_bool($field->label) ? $field->label : true,
                                        "addWrapper"        => false, 
                                        "attrNameAsArray"   => false,
                                        "schemaID"          => "CustomPosts",
                                        "errors"            => isset($_SESSION[$posttype]['errors']) ? $_SESSION[$posttype]['errors'] : []
                                    ]);
    
                                    return $formType->render();
                                }
                            }
                        }
                    }
                }
                return false;
            }
        }


        /**
         * ReSet & return post taxonomies
         */
        private function add_taxonomy($prefix, $taxonomy, $post)
        {
            // Generate Taxonomy key
            if (!isset($taxonomy['key']))
            {
                $taxonomy['key'] = $prefix.$post->type;
            }
            // Format the post category key
            // Must not exceed 32 characters
            if (strlen($taxonomy['key']) > 32)
            {
                $taxonomy['key'] = substr($taxonomy['key'], 0,32);
            }
            
            
            // Taxonomy is public
            $taxonomy['public'] = (!isset($taxonomy['public']) || false !== $taxonomy['public']) ? true : false;
            
            
            // Show taxonomy in admin columns
            $taxonomy['show_admin_column'] = (!isset($taxonomy['show_admin_column']) || true === $taxonomy['show_admin_column']) ? true : false;


            // Associated objects
            if (!isset($taxonomy['rel_objects']))
            {
                $taxonomy['rel_objects'] = [];
            }
            else
            {
                if (is_string($taxonomy['rel_objects']))
                {
                    $taxonomy['rel_objects'] = [$taxonomy['rel_objects']];
                }
            }
            array_push($taxonomy['rel_objects'], $post->type);


            // Labels
            // TODO: Add translation
            if (isset($taxonomy['labels']) && is_array($taxonomy['labels']))
            {
                $taxonomy['labels']['menu_name'] = null;
                $taxonomy['labels']['items_list'] = null;
                $taxonomy['labels']['items_list_navigation'] = null;
                $taxonomy['labels']['update_item'] = null;
                
                foreach ($taxonomy['labels'] as $label_key => $label_value)
                {
                    if (empty($taxonomy['labels'][$label_key]))
                    {
                        unset($taxonomy['labels'][$label_key]);
                    }
                    else
                    {
                        $taxonomy['labels'][$label_key] = __($label_value, WPPPM_TEXTDOMAIN );
                    }
                }
            }
            

            if (isset($taxonomy['rewrite']))
            {
                if (is_array($taxonomy['rewrite']))
                {
                    $taxonomy['rewrite']['slug'] = PPM::tryToDo($taxonomy['rewrite']['slug']);
                    if (!isset($taxonomy->rewrite["slug"]) || empty($post->rewrite["slug"]))
                    {
                        $taxonomy['rewrite']["slug"] = $taxonomy['key'];
                    }
            
                    // With Front
                    if (!isset($taxonomy['rewrite']["with_front"]) || !is_bool($taxonomy['rewrite']["with_front"]))
                    {
                        $taxonomy['rewrite']["with_front"] = true;
                    }
                    
                    // hierarchical
                    if (!isset($taxonomy['rewrite']["hierarchical"]) || !is_bool($taxonomy['rewrite']["hierarchical"]))
                    {
                        $taxonomy['rewrite']["hierarchical"] = false;
                    }
                    
                    // EndPoint Mask
                    if (!isset($taxonomy['rewrite']["ep_mask"]) || !in_array($taxonomy['rewrite']["ep_mask"], $this->available_ep_mask))
                    {
                        $taxonomy['rewrite']["ep_mask"] = "EP_NONE";
                    }
                }
            }

            register_taxonomy(
                $taxonomy['key'], 
                $taxonomy['rel_objects'],
                $taxonomy
            );

            return $taxonomy;
        }

        /**
         * Set custom columns 
         */
        public function set_custom_columns($columns)
        {
            $settings = $this->getPostsSettings();
            
            foreach ($settings as $key => $post)
            {
                $post = (object) $post;
                foreach ($post->admin_columns as $column)
                {
                    $column = (object) $column;
                    if (!empty($column->label))
                    {
                        if (!isset($column->key))
                        {
                            $column->key = PPM::slugify($column->label);
                        }
    
                        $columns[ $column->key ] = __($column->label, $this->config->Namespace);
                    }

                    if (isset($column->key))
                    {
                        if (false === $column->public)
                        {
                            unset( $columns[ $column->key ] );
                        }
                    }
                }
            }

            return $columns;
        }

        /**
         * Sortable columns
         */
        public function custom_columns_sortable($columns)
        {
            $settings = $this->getPostsSettings();
            $post = $this->getPostsSettings();
            
            foreach ($settings as $key => $post)
            {
                $post = (object) $post;
                foreach ($post->admin_columns as $column)
                {
                    $column = (object) $column;

                    if (!empty($column->label))
                    {
                        if (!isset($column->key))
                        {
                            $column->key = PPM::slugify($column->label);
                        }
    
                        if (isset($column->sortable) && $column->sortable === true)
                        {
                            $columns[ $column->key ] = $column->key;
                        }
                    }
                }
            }

            return $columns;
        }

        /**
         * Column data
         */
        public function custom_column_data($columnName, $post_id)
        {
            $settings = $this->getPostsSettings();
            
            foreach ($settings as $key => $post)
            {
                $post = (object) $post;
                foreach ($post->admin_columns as $column)
                {
                    $column = (object) $column;

                    if (!empty($column->label))
                    {
                        if (!isset($column->key))
                        {
                            $column->key = PPM::slugify($column->label);
                        }
                    }

                    if ($columnName === $column->key)
                    {
                        if (is_array($column->data))
                        {
                            $glue = $column->data[0];
                            $stack = [];
                            unset($column->data[0]);

                            foreach ($column->data as $data) 
                            {
                                $post_meta = get_post_meta($post_id , $data, true);
                                if (!empty($post_meta))
                                {
                                    array_push($stack, $post_meta);
                                }
                            }
                            echo implode($glue, $stack); 
                        }
                        else
                        {
                            echo get_post_meta($post_id , $column->data , true); 
                        }
                    }
                }
            }
        }


        /**
         * Remove row actions
         */
        public function remove_admin_row_actions( $actions )
        {
            if (isset($this->config->Registers->CustomPosts))
            {
                foreach ($this->config->Registers->CustomPosts as $post)
                {
                    if ($post['type'] === get_post_type() && isset($post['remove_admin_row_actions'])) 
                    {
                        if (true === $post['remove_admin_row_actions'])
                        {
                            return array();
                        }
                        else if (is_array($post['remove_admin_row_actions']))
                        {
                            if (in_array("view", $post['remove_admin_row_actions'])) unset( $actions['view'] );
                            if (in_array("quick-edit", $post['remove_admin_row_actions'])) unset( $actions['inline hide-if-no-js'] );
                            if (in_array("edit", $post['remove_admin_row_actions'])) unset( $actions['edit'] );
                            if (in_array("trash", $post['remove_admin_row_actions'])) unset( $actions['trash'] );
                        }
                    }
                }
            }
            return $actions;
        }

        public function remove_admin_menu_all_items()
        {
            $this->remove_admin_from_menu('all_items');
        }

        public function remove_admin_menu_add_new()
        {
            $this->remove_admin_from_menu('add_new');
        }

        private function remove_admin_from_menu( $item )
        {
            $links = array(
                "all_items" => "edit.php?post_type=",
                "add_new" => "post-new.php?post_type=",
            );

            $settings = $this->getPostsSettings();

            // print_r( $GLOBALS['submenu'] );

            foreach ($settings as $key => $post)
            {
                $post = (object) $post;
                if (isset($post->labels) && is_array($post->labels))
                {
                    foreach ($post->labels as $label_key => $label_value)
                    {
                        if ($item === $label_key && isset($links[$item]))
                        {
                            if (isset($GLOBALS['submenu']['edit.php?post_type='.$post->type]))
                            {
                                foreach ($GLOBALS['submenu']['edit.php?post_type='.$post->type] as $key => $entry)
                                {
                                    if (isset($entry[2]) && $entry[2] === $links[$item].$post->type)
                                    {
                                        unset($GLOBALS['submenu']['edit.php?post_type='.$post->type][$key]);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        

        /**
         * Show the custom form
         * -> generate MetaBox
         */
        public function customposts_view()
        {
            $class_file = $this->config->Path."PPM/register/metaboxes.php";
            $class_name = "PPM_RegisterMetaboxes";
            $class_params = array(
                "config" => $this->config,
                "settings" => $this->getPostsSettings(),
                "schemas" => $this->getPostsSchemas(),
                "type" => $this->getPostType()
            );

            PPM::include_class( $class_file, $class_name, $class_params );
        }

        /**
         * Do on submit
         */
        public function custompost_submission( $pid )
        {
            if ('POST' === $_SERVER['REQUEST_METHOD']) 
            {
                $type = $_REQUEST['post_type'];
                $schema = $this->getPostsSchemas();

                // Format responses
                $responses = PPM::responses([
                    "config" => $this->config,
                    "schema" => $schema[$type]
                ]);

                if (!empty($responses))
                {
                    // check response validation
                    $validate = PPM::validate([
                        "config" => $this->config,
                        "post_type" => $type,
                        "responses" => $responses
                    ]);
    
                    if (!$validate->isValid)
                    {
                        $this->errors = $validate->errors;
                    }
    
                    foreach ($responses as $key => $response)
                    {
                        if (!isset($this->errors[$key]))
                        {
                            // Save File
                            if ('file' === $response->type)
                            {
                                if (!empty($response->files)) // Prevent to remove the previous image
                                {
                                    $uploads = PPM::upload( $response, $pid, $this->config );
                                    update_post_meta($pid, $key, $uploads); 
                                }
                            }
                            
                            // Save data in wp_postmeta
                            else
                            {
                                update_post_meta($pid, $key, $response->value);
                            }
                        }
                    }
    
                    $_SESSION[$type]['errors'] = $this->errors;
                }
            }
        }
    }
}
