<?php

if (!defined('WPPPM_TEXTDOMAIN'))
{
    define('WPPPM_TEXTDOMAIN', 'wpppm');
}


if (!class_exists('PPM'))
{
    abstract class PPM
    {
        private $path;
        private $url;
        private $basename;
        private $name;
        private $settingsNamespace;
        private $namespace;
        private $config;
        private $prefix;
        private $prefixTable;
        private $version;
        private $description;
        private $assetsFrontStyles;
        private $assetsFrontScripts;
        private $assetsAdminStyles = [[
            "name" => "bxslider",
            "path" => "https://cdn.jsdelivr.net/bxslider/4.2.12/jquery.bxslider.css"
        ],[
            "name" => "ppm",
            "path" => "ppm"
        ]];
        private $assetsAdminScripts = [[
            "name" => "jquery-3.1.1",
            "path" => "https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"
        ],[
            "name" => "bxslider",
            "path" => "https://cdn.jsdelivr.net/bxslider/4.2.12/jquery.bxslider.min.js"
        ],[
            "name" => "ppm",
            "path" => "ppm"
        ]];
        private $shortcodes;
        private $hooks;
        private $imagesSizes;
        private $menus;
        private $submenus;
        private $options = [];
        private $register;
        private $schemas;
        private $schemasFields;
        private $settings;
        private $posts;
        private $plugin_uri;
        private $widgets;
        private $currentScreen;
        
        /**
         * Constructor
         */
        public function __construct( $directory=null )
        {
            session_start();
            
            // Define the plugin path
            $this->setPath( $directory );
            $this->setUrl( trailingslashit( plugins_url( '/', $this->getPath()."index.php" ) ) );
            
            // Read the configuration
            $this->configuration();

            // Define registers
            $this->setRegister();
            $this->setRegisterPosts();
            $this->setRegisterSettings();
            
            $this->setSchemas();
            $this->setSchemasFields();
            $this->setImagesSizes();
            $this->setHooks();
            $this->setMenus();
            $this->setName();
            $this->setNamespace();
            $this->setOptions();
            $this->setPluginUri();
            $this->setPrefix();
            $this->setPrefixTable();
            $this->setShortcodes();
            $this->setVersion();
            $this->setWidgets();
            $this->setDescription();

            // echo "<pre>";
            // print_r( $this->getConfig()->Shortcodes );
            // echo "</pre>";
        }
        
        /**
         * The Installer
         * 
         * Include & Instantiate the PPM_Installer class
         * PPM_Installer contain the activate() & deactivate() methods
         * 
         * @return (object) PPM_Installer
         */
        protected function installer()
        {
            $class_file = __DIR__."/installer.php";
            $class_name = "PPM_Installer";
            $class_params = array(
                "config" => $this->getConfig()
            );
            
            return PPM::include_class( $class_file, $class_name, $class_params );
        }
        
        /**
         * Initialize the plugin
         * The initialisation process when the plugin is activate
         */
        protected function init()
        {
            // -- Assets

            // Assets injections for front
            add_action('wp_print_styles', array($this, 'enqueue_front_styles'));
            add_action('wp_enqueue_scripts', array($this, 'enqueue_front_scripts'));
            // Assets injections for Admin
            add_action('admin_print_styles', array($this, 'enqueue_admin_styles'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));


            // -- Functions, Hooks & Shortcodes

            $this->load_functions();


            // -- Init registers

            $params = array(
                "config" => $this->getConfig()
            );

            // Init Custom Posts register
            PPM::include_class(
                $this->getPath().'ppm/register/posts.php', 
                'PPM_RegisterPosts',
                $params
            );
            
            // Init Settings register
            PPM::include_class(
                $this->getPath().'ppm/register/settings.php', 
                'PPM_RegisterSettings',
                $params
            );
            
            // Init Widgets register
            PPM::include_class(
                $this->getPath().'ppm/register/widgets.php', 
                'PPM_RegisterWidgets',
                $params
            );
        }
        

        // Configuration builder ///////////////////////////////////////////////
        
        /**
         * Configuration builder
         */
        private function configuration()
        {
            $config = [];
            $config_json = $this->getPath().'config.json';
            $config_php = $this->getPath().'config.php';

            // Parse config from jSon file
            if (file_exists($config_json))
            {
                $config = array_merge(
                    $config, 
                    $this->json( $config_json, true )
                );
            }
            else if (file_exists($config_php))
            {
                include_once $config_php;
            }

            // Parse config from Index.php
            $config = array_merge(
                $config,
                self::configuration_index($this->getPath())
            );

            $this->config = (object) $config;
        }

        /**
         * Get configuration from the index.php
         */
        private static function configuration_index( $path )
        {
            $config = [];

            $config_php = $path."index.php";
            
            if (file_exists($config_php)) 
            {
                $config_php = file_get_contents($config_php);
                
                preg_match(
                    "/\/\*\*(.*)\*\//uis", 
                    $config_php, 
                    $config_params
                );
                $config_params = explode("* ", $config_params[0]);

                foreach( $config_params as $config_param )
                {
                    list($config_param_key, $config_param_value) = explode(": ", $config_param);
                    $config_param_key   = PPM::slugify($config_param_key, "_");
                    $config_param_value = trim(preg_replace("/\*\//", null, $config_param_value));

                    if (!empty($config_param_key))
                    {
                        $config[$config_param_key] = trim($config_param_value);
                    }
                }
            }

            return $config;
        }
        

        // Gettre / Setter /////////////////////////////////////////////////////
        
        /**
         * Get Plugin Name
         * @return (string)
         */
        public function getConfig()
        {
            return (object) [
                "Name"                  => $this->getName(),
                "Namespace"             => $this->getNamespace(),
                "Classname"             => self::getClassname($this->path),
                "Prefix"                => $this->getPrefix(),
                "PrefixTable"           => $this->getPrefixTable(),
                "Version"               => $this->getVersion(),
                "Description"           => $this->getDescription(),

                "Path"                  => $this->getPath(),
                "Url"                   => $this->getUrl(),
                "PluginUri"             => $this->getPluginUri(),
                "Menus"                 => $this->getMenus(),

                "Hooks"                 => $this->getHooks(),
                "Shortcodes"            => $this->getShortcodes(),

                "Options"               => $this->getOptions(),
                "Registers"             => $this->getRegistersWithoutSchemas(),
                "Schemas"               => $this->getSchemas(),
                "SchemasFields"         => $this->getSchemasFields(),
                "ImagesSizes"           => $this->getImagesSizes(),
                "Widgets"               => $this->getWidgets(),

                "AssetsStyles"          => $this->getAssetsStyles(),
                "AssetsScripts"         => $this->getAssetsScripts(),
                "AssetsAdminStyles"     => $this->getAssetsAdminStyles(),
                "AssetsAdminScripts"    => $this->getAssetsAdminScripts(),
            ];
        }

        public function setConfig_toFilters()
        {
            return $this->getConfig();
        }

        
        /**
         * Get Plugin Classname
         * @return (string)
         */
        public static function getClassname()
        {
            $base = self::base(__DIR__);
            $conf = self::configuration_index( $base."/" );

            return self::slugify(implode(" ", [
                $conf['author'],
                $conf['plugin_name']
            ]), "_");
        }


        /**
         * Set Plugin Hooks
         */
        private function setHooks()
        {
            $this->hooks = isset($this->config->hooks) 
                ? (object) $this->config->hooks 
                : (object) [];
        }
        /**
         * Get Plugin Hooks
         * @return (object)
         */
        public function getHooks()
        {
            return $this->hooks;
        }
        

        /**
         * Set Plugin ImagesSizes
         */
        private function setImagesSizes()
        {
            $posts = $this->posts;
            $settings = $this->settings;
            $imagesSizes = [];

            // Get ImagesSizes for the Settings Register
            if (isset($settings->thumbnails) && is_array($settings->thumbnails) && !empty($settings->thumbnails))
            {
                if (!isset( $imagesSizes['Settings'] )) $imagesSizes['Settings'] = [];
                $imagesSizes['Settings'] = $this->parseImagesSizes( $settings->thumbnails );
            }

            
            // Get ImagesSizes for the Custom posts Register
            foreach ($posts as $post)
            {
                $post = (object) $post;

                if (isset($post->thumbnails) && is_array($post->thumbnails) && !empty($post->thumbnails))
                {
                    if (!isset( $imagesSizes['CustomPosts'] )) $imagesSizes['CustomPosts'] = [];
                    if (!isset( $imagesSizes['CustomPosts'][$post->type] )) $imagesSizes['CustomPosts'][$post->type] = [];
                    $imagesSizes['CustomPosts'][$post->type] = $this->parseImagesSizes( $post->thumbnails );
                }
            }


            // Retrieve Thumbnails filters only
            $filters = [];
            if (is_array($imagesSizes['Settings']['sizes']))
            {
                $filters = array_merge($filters, $imagesSizes['Settings']['sizes']);
            }
            if (is_array($imagesSizes['CustomPosts']))
            {
                foreach ($imagesSizes['CustomPosts'] as $CustomPosts)
                {
                    if (is_array($CustomPosts['sizes']))
                    {
                        $filters = array_merge($filters, $CustomPosts['sizes']);
                    }
                }
            }

            // Add images Sizes to wp register
            foreach ($filters as $filter)
            {
                add_image_size( 
                    $filter['name'], 
                    $filter['width'], 
                    $filter['height'], 
                    $filter['crop'] 
                );
            }
            
            $this->imagesSizes = (object) $imagesSizes;
        }
        private function parseImagesSizes( $input )
        {
            // Default output
            $output = array(
                "strict" => false,
                "preserve_wp_sizes" => true,
                "sizes" => array(),
            );

            // Override the Strict parameter
            if (isset($input['strict']) && is_bool($input['strict']))
            {
                $output['strict'] = $input['strict'];
            }

            // Override the Preserve WP Sizes parameter
            if (isset($input['preserve_wp_sizes']) && is_bool($input['preserve_wp_sizes']))
            {
                $output['preserve_wp_sizes'] = $input['preserve_wp_sizes'];
            }

            // Override the Sizes parameter
            if (isset($input['sizes']) && is_array($input['sizes']))
            {
                $output['sizes'] = $input['sizes'];
            }
            else if (is_array($input))
            {
                $output['sizes'] = $input;
            }

            // Checking Sizes data
            foreach ($output['sizes'] as $key => $size)
            {
                if (
                    (!isset($size['name']) || !is_string($size['name'])) ||
                    (!isset($size['width']) || !is_integer($size['width'])) ||
                    (!isset($size['height']) || !is_integer($size['height']))
                )
                {
                    unset($output['sizes'][$key]);
                    continue;
                }

                $output['sizes'][$key] = array(
                    "name" => $size['name'],
                    "width" => $size['width'],
                    "height" => $size['height'],
                    "crop" => false
                );

                if (isset($size['crop']) && (is_bool($size['crop']) || is_array($size['crop'])))
                {
                    $output['sizes'][$key]['crop'] = $size['crop'];
                }
            }

            return $output;
        }
        /**
         * Get Plugin ImagesSizes
         * @return (object)
         */
        public function getImagesSizes()
        {
            return $this->imagesSizes;
        }


        /**
         * Set Plugin Name
         * The name of the plugin was defined in the index.php of the plugin at the line "Plugin Name"
         */
        private function setName()
        {
            $this->name = isset($this->config->plugin_name) 
                ? $this->config->plugin_name 
                : null;
        }
        /**
         * Get Plugin Name
         * @return (string)
         */
        public function getName()
        {
            return $this->name;
        }


        /**
         * Set Plugin NameSpace
         * The namespace of the plugin was defined in the config.json as the line "namespace"
         * If the namespace is not set in the config file, The plugin namespace will automatically defined by the plugin name's slug (with underscore)
         */
        private function setNamespace()
        {
            $this->namespace = self::slugify($this->getName(), "_");

            if (isset($this->config->namespace) )
            {
                if (!empty($this->config->namespace) )
                {
                    $this->namespace = self::slugify($this->config->namespace, "_");
                }
            }
        }
        /**
         * Get Plugin NameSpace
         * @return (string)
         */
        public function getNamespace()
        {
            return $this->namespace;
        }
        

        /**
         * Set Plugin Menus
         * Define the menus location for the plugin
         */
        private function setMenus()
        { 
            $this->menus = [
                "locations" => [],
                "icon" => null
            ];

            $menus = isset($this->settings->menus ) 
                ? (object) $this->settings->menus 
                : (object) [];

            if (true === $menus->admin) array_push($this->menus['locations'], "admin");
            if (true === $menus->action) array_push($this->menus['locations'], "action");
            if (true === $menus->settings) array_push($this->menus['locations'], "settings");

            if (isset($menus->icon))
            {
                if (preg_match("/^image:/", $menus->icon))
                {
                    $this->menus['icon'] = $this->getUrl()."assets/images/".preg_replace("/^image:/", null, $menus->icon);
                }
                else 
                {
                    $this->menus['icon'] = $menus->icon;
                }
            }
        }
        /**
         * Get Plugin Menus
         * @return (string)
         */
        public function getMenus()
        {
            return $this->menus;
        }
        

        /**
         * Set Plugin Options
         */
        private function setOptions( $data=null )
        {
            if (null === $data)
            {
                $data = $this->getSchemas();
            }

            foreach ($data as $key => $value) {
                if (is_array($value))
                {
                    $this->setOptions($value);
                }
                else {
                    if ('default' === $key)
                    {
                        $this->options[$data['key']] = $value;
                    }
                }
            }

            if (isset($this->config->options))
            {
                $this->options = array_merge(
                    $this->options,
                    $this->config->options
                );
            }
        }
        /**
         * Get Plugin Options
         * @return (string)
         */
        public function getOptions( $key=null )
        {
            if (null !== $key) {
                return isset($this->options->$key) 
                    ? $this->options->$key 
                    : null;
            }
            
            return $this->options;
        }
        

        /**
         * Set Plugin Path
         */
        private function setPath( $path )
        {
            $this->path = $path.DIRECTORY_SEPARATOR;
        }
        /**
         * Get Plugin Path
         * @return (string) something like : /var/www/my_webstie/wp-content/plugins/plugin-name/
         */
        public function getPath()
        {
            return $this->path;
        }
        

        /**
         * Set Plugin URI
         * The name of the plugin was defined in the index.php of the plugin at the line "Plugin Name"
         */
        private function setPluginUri()
        {
            $this->plugin_uri = isset($this->config->plugin_uri) ? $this->config->plugin_uri : null;
        }
        /**
         * Get Plugin URI
         * @return (string)
         */
        public function getPluginUri()
        {
            return $this->plugin_uri;
        }


        /**
         * Set Plugin Prefix
         */
        private function setPrefix()
        {
            $this->prefix = $this->getNamespace()."_";
        }
        /**
         * Get Plugin Prefix
         * @return (string)
         */
        public function getPrefix()
        {
            return $this->prefix;
        }
        

        /**
         * Set Plugin Database Table Prefix
         */
        private function setPrefixTable()
        {
            global $wpdb;
            $this->prefixTable =  $wpdb->prefix.$this->getPrefix();
        }
        /**
         * Get Plugin Database Table Prefix
         * @return (string)
         */
        public function getPrefixTable()
        {
            return $this->prefixTable;
        }
        

        /**
         * Set Plugin Schemas
         */
        private function setSchemas()
        {
            $posts = $this->posts;
            $settings = $this->settings;
            $schemas = (object) [];

            // Build Custom posts schema
            foreach ($posts as $post)
            {
                if (isset($post['type']) && !empty($post['type']))
                {
                    if (isset($post['metas']))
                    {
                        $schemas->CustomPosts[$post['type']] = $post['metas'];
                    }
                }
            }

            // Build Settings schema
            $schemas->Settings = [];
            if (isset($settings->sections) && !empty($settings->sections))
            {
                foreach ($settings->sections as $section_key => $section_data)
                {
                    if (isset($section_data['schema']) && !empty($section_data['schema']))
                    {
                        array_push(
                            $schemas->Settings, 
                            $section_data
                        );
                    }
                }
            }

            $this->schemas = $schemas;
        }
        /**
         * Get Plugin Schemas
         * @return (object)
         */
        public function getSchemas()
        {
            return $this->schemas;
        }


        /**
         * Retrieve Fields Only for each Schemas
         */
        private function setSchemasFields()
        {
            $schemas = $this->getSchemas();
            $fields = [];

            // Fields for settings
            if (isset($schemas->Settings))
            {
                foreach ($schemas->Settings as $section)
                {
                    if (isset($section['schema']))
                    {
                        if (!isset($fields['Settings']))
                        {
                            $fields['Settings'] = [];
                        }

                        foreach ($section['schema'] as $field)
                        {
                            array_push($fields['Settings'], $field);
                        }
                    }
                }
            }

            // Fields for Custom Posts
            if (isset($schemas->CustomPosts))
            {
                foreach ($schemas->CustomPosts as $type => $customPost)
                {
                    foreach ($customPost as $section)
                    {
                        if (isset($section['schema']))
                        {
                            if (!isset($fields['CustomPosts']))
                            {
                                $fields['CustomPosts'] = [];
                            }
                            if (!isset($fields['CustomPosts'][$type]))
                            {
                                $fields['CustomPosts'][$type] = [];
                            }

                            foreach ($section['schema'] as $field)
                            {
                                array_push($fields['CustomPosts'][$type], $field);
                            }
                        }
                    }
                }
            }

            $this->schemasFields = (object) $fields;

        }
        private function getSchemasFields()
        {
            return $this->schemasFields;
        }
        

        /**
         * Set Plugin Shortcodes
         */
        private function setShortcodes()
        {
            $this->shortcodes = isset($this->config->shortcodes) 
                ? (object) $this->config->shortcodes 
                : (object) [];
        }
        /**
         * Get Plugin Shortcodes
         * @return (object)
         */
        public function getShortcodes()
        {
            return $this->shortcodes;
        }
        

        /**
         * Set Plugin URL
         */
        private function setUrl( $url )
        {
            $this->url = preg_replace("/ppm\/$/", null, $url);
        }
        /**
         * Get Plugin URL
         * @return (string) something like : http://my-website.com/wp-content/plugins/plugin-name/
         */
        public function getUrl()
        {
            return $this->url;
        }
        

        /**
         * Set Plugin Version
         */
        private function setVersion()
        {
            $this->version = isset($this->config->version) ? $this->config->version : null;
        }
        /**
         * Get Plugin Version
         * @return (string)
         */
        public function getVersion()
        {
            return $this->version;
        }
        

        /**
         * Set Plugin Widgets
         */
        private function setWidgets()
        {
            $register = $this->register;
            $posts = $this->posts;
            $widgets = [];

            // Get Widgets from the register
            if (isset($register->widgets) && is_array($register->widgets))
            {
                foreach ($register->widgets as $widget)
                {
                    if (!empty($widget))
                    {
                        $widget['type'] = null;
                        array_push($widgets, $this->parseWidgets($widget));
                    }
                }
            }

            // Get Widgets from custom post
            foreach ($posts as $post)
            {
                $post = (object) $post;

                if (isset($post->widget))
                {
                    array_push($widgets, $this->parseWidgets($post->widget, $post));
                }
            }
            
            $this->widgets = (object) $widgets;
        }
        private function parseWidgets( $input, $params=null )
        {
            $supported_types = ["comments", "list", "quick-add", "view"];
            static $serial = 1;

            // Default output
            $output = array(
                "ID"        => $this->getPrefix()."widget_".$serial,
                "label"     => $this->getName(),
                "type"      => "view",
                "view"      => null,
                "control"   => null,
                "args"      => []
            );


            // -- Override the Widget Label
            if (isset($input['label']) && !empty($input['label']))
            {
                $output['label'] = $input['label'];
            }


            // -- Overide the type
            if (isset($input['type']) && in_array($input['type'], $supported_types))
            {
                $output['type'] = $input['type'];
            }


            // -- Define the view file
            if ('view' === $output['type'])
            {
                if (isset($input['view']) && !empty($input['view'])) //&& 'view' === $output['type']
                {
                    $output['view'] = $input['view'];
                }
                else
                {
                    $output['view'] = $output['ID'];
                }
            }


            // -- Override the widget Control

            
            // -- Override the widget Args

            // View
            $args_ID = $output['ID'];

            // View
            $args_view = $output['view'];

            // PostType
            $args_posttype = null;

            if (isset($params->type))
            {
                $args_posttype = $params->type;
            }

            $output['args'] = array_merge($output['args'], [
                "widget_ID" => $args_ID,
                "widget_view" => $args_view,
                "posttype" => $args_posttype
            ]);


            $serial++;
            return (object) $output;
        }
        /**
         * Get Plugin Widgets
         * @return (object)
         */
        public function getWidgets()
        {
            return $this->widgets;
        }
        

        /**
         * Set Plugin Description
         */
        private function setDescription()
        {
            $this->description = isset($this->config->description) ? $this->config->description : null;
        }
        /**
         * Get Plugin Description
         * @return (string)
         */
        public function getDescription()
        {
            return $this->description;
        }


        /**
         * Assets
         */
        private function setAssets( $type, $side="front" )
        {
            // Admin assets
            if ("admin" === strtolower($side))
            {
                if (isset($this->config->assets_admin[$type]))
                    return $this->config->assets_admin[$type];
            }

            // Front assets
            else
            {
                if (isset($this->config->assets[$type]))
                    return $this->config->assets[$type];
            }

            return [];
        }
        public function getAssetsStyles()
        {
            return $this->setAssets("styles", "front");
        }
        public function getAssetsScripts()
        {
            return $this->setAssets("scripts", "front");
        }
        public function getAssetsAdminStyles()
        {
            return array_merge($this->assetsAdminStyles, $this->setAssets("styles", "admin"));
        }
        public function getAssetsAdminScripts()
        {
            return array_merge($this->assetsAdminScripts, $this->setAssets("scripts", "admin"));
        }
        
        
        /**
         * The Register (Settings & Custom Posts)
         */
        private function setRegister()
        {
            $this->register = isset($this->config->register)
                ? (object) $this->config->register
                : (object) [];
        }
        /**
         * Register : Posts
         */
        private function setRegisterPosts()
        {
            $this->posts = isset($this->register->posts)
                ? (object) $this->register->posts
                : (object) [];
        }
        /**
         * Register : Settings
         */
        private function setRegisterSettings()
        {
            $this->settings = isset($this->register->settings)
                ? (object) $this->register->settings
                : (object) [];
        }
        /**
         * Custom Post infos exept schemas
         */
        protected function getCustomPosts()
        {
            $posts = (array) $this->posts;

            foreach ($posts as $key => $post)
            {
                if (isset($post['metas']))
                {
                    unset($posts[$key]['metas']);
                }
            }

            return $posts;
        }
        private function getSettings()
        {
            $settings = (array) $this->settings;

            if (!isset($settings['view']) || $settings['view'] !== true)
            {
                $settings['view'] = false;
            }

            if (isset($settings['schema']))
            {
                unset($settings['schema']);
            }
            return $settings;
        }
        private function getRegistersWithoutSchemas()
        {
            return (object) array(
                "Settings" => (object) $this->getSettings(),
                "CustomPosts" => (object) $this->getCustomPosts()
            );
        }
        
        // Utils ///////////////////////////////////////////////////////////////

        /**
         * Include Classe, its dependencies & instantiate
         * @param (string) $file
         * @param (string) $className
         * @param (string) $params
         * @param (string) $dependencies
         */
        public static function include_class( $file, $className, $params=[], $dependencies=[] )
        {
            // Load dependencies
            foreach ($dependencies as $dependency) 
            {
                if (file_exists($dependency))
                {
                    require_once($dependency);
                }
            }

            // Load class
            if (file_exists($file))
            {
                require_once($file);

                if (is_array($className))
                {
                    $method = isset($className[1]) ? $className[1] : null;
                    $args = (isset($className[2]) && is_array($className[2])) ? $className[2] : [];
                    $className = $className[0];

                    if (class_exists($className))
                    {
                        if (null === $method)
                        {
                            return new $className( $params );
                        }
                        else
                        {
                            return $className::$method( $params );
                        }
                    }
                }
                else
                {
                    if (class_exists($className))
                    {
                        return new $className( $params );
                    }
                }

            }
        }

        /**
         * Slugify
         * Generate & return a slug string
         */
        public static function slugify( $text, $separator="-" )
        {
            $text = preg_replace('~[^\pL\d]+~u', $separator, $text);
            $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
            $text = preg_replace('~[^-\w]+~', '', $text);
            $text = trim($text, $separator);
            $text = preg_replace('~-+~', $separator, $text);
            $text = strtolower($text);

            if (empty($text)) return false;

            return $text;
        }

        /**
         * Load Functions
         * Check if the plugin have the functions files and include there
         */
        private function load_functions()
        {
            $functions_ppm = ["utils", "functions", "actions", "hooks", "shortcodes"];
            $functions_path = $this->getPath().'functions/';
            $functions_dir = scandir($functions_path);

            // -- Load PPM Functions Files

            // Load Utils
            if (file_exists($functions_path."utils.php"))
            {
                require_once($functions_path."utils.php");
            }
            
            // Load Functions
            if (file_exists($functions_path."functions.php"))
            {
                require_once($functions_path."functions.php");
            }
            
            // Load Actions
            if (file_exists($functions_path."actions.php"))
            {
                require_once($functions_path."actions.php");

                //
                add_filter(
                    $this->getNamespace(),
                    [$this, 'setConfig_toFilters']
                );
            }

            // Load Hooks
            if (file_exists($functions_path."hooks.php"))
            {
                require_once($functions_path."hooks.php");

                // Hooks injections
                foreach ($this->getHooks() as $hook => $event)
                {
                    if ( function_exists($hook) )
                    {
                        $order = $event == 'init' ? 1 : 10;
                        add_action($event, $hook, $order);
                    }
                }
            }

            // Load Shortcodes
            if (file_exists($functions_path."shortcodes.php"))
            {
                require_once($functions_path."shortcodes.php");

                // Shortcodes injections
                foreach ($this->getShortcodes() as $name => $function)
                {
                    if ( function_exists($function) )
                    {
                        add_shortcode($name, $function);
                    }
                }
            }


            // -- Load custom function files
            
            foreach ($functions_dir as $item)
            {
                $regex = "/\.php$/";
                if (preg_match($regex, $item))
                {
                    $filename = preg_replace($regex, null, $item);

                    if (!in_array($filename, $functions_ppm) && file_exists($functions_path.$item))
                    {
                        require_once($functions_path.$item);
                    }
                }
            }
        }

        /**
         * Try To Do
         * Try to execute a function
         */
        public static function tryToDo( $expression )
        {
            list($a, $b) = explode(":", $expression);

            if ("do" === $a && function_exists($b)) {
                return $b();
            }

            else if ("menu" === $a) {
                $o = [];
                $items = wp_get_nav_menu_items($b);

                if (is_array($items))
                {
                    foreach ($items as $item) 
                    {
                        $o[ PPM::slugify($item->title) ] = $item->title;
                    }
                }
                return $o;
            }

            return false;
        }

        public static function base( $directory )
        {
            return preg_replace("/\/ppm$/", null, $directory);
        }

        public static function notice( $type, $message, $dismissible=true )
        {
            $output = null;
            $class = [];

            array_push($class, "notice");
            array_push($class, "notice-".$type);
            ($dismissible) ? array_push($class, "is-dismissible") : null;

            $output.= '<h2 style="display: none;"></h2>';
            $output.= '<div class="'. implode(" ", $class) .'">';
            $output.= "<p>". $message ."</p>";
            $output.= '</div>';

            return $output;
        }

        public static function exptrim( $delimiter, $string )
        {
            $array = explode($delimiter, $string);
            
            foreach ($array as $key => $value)
            {
                $array[$key] = trim($value);
            }

            return $array;
        }

        /**
         * Read a json file (with comment), sanitize it and return an stdClass
         * or an Array
         *
         * @param [string] $src
         * @param boolean $assoc
         * @return void
         */
        public function json($src, $assoc=false)
        {
            $lines = file($src);
            
            foreach ($lines as $key => $line)
            {
                $lines[$key] = preg_replace("/((!\/\/).*)?(\/\/.*)/", "$1", $line);

                if (empty(trim($lines[$key])))
                {
                    unset($lines[$key]);
                }
            }
            
            return json_decode(
                implode("\n", $lines), 
                $assoc
            );
        }

        /**
         * All images sizes list
         *
         * @return array
         */
        public static function all_image_sizes()
        {
            global $_wp_additional_image_sizes;
            $wp_sizes = get_intermediate_image_sizes();
            $$sizes = [];
            
            foreach ( $wp_sizes as $size ) {
                $$sizes[ $size ][ 'width' ] = intval( get_option( "{$size}_size_w" ) );
                $$sizes[ $size ][ 'height' ] = intval( get_option( "{$size}_size_h" ) );
                $$sizes[ $size ][ 'crop' ] = get_option( "{$size}_crop" ) ? get_option( "{$size}_crop" ) : false;
            }
        
            if ( isset( $_wp_additional_image_sizes ) && count( $_wp_additional_image_sizes ) ) {
                $$sizes = array_merge( $$sizes, $_wp_additional_image_sizes );
            }

            return $$sizes;
        }
        
        // Assets //////////////////////////////////////////////////////////////

        public function enqueue_front_styles()
        {
            if (!is_admin()) $this->enqueue_assets(
                $this->getAssetsStyles(),
                "css"
            );
        }
        public function enqueue_front_scripts()
        {
            if (!is_admin()) $this->enqueue_assets(
                $this->getAssetsScripts() ,
                "js"
            );
        }
        public function enqueue_admin_styles()
        {
            if (is_admin()) $this->enqueue_assets(
                $this->getAssetsAdminStyles(),
                "css"
            );
        }
        public function enqueue_admin_scripts()
        {
            if (is_admin()) $this->enqueue_assets(
                $this->getAssetsAdminScripts() ,
                "js"
            );
        }

        private function enqueue_assets( $assets, $type )
        {
            $directory = "assets/$type/";
            $path = $this->getPath().$directory;
            $url = $this->getUrl().$directory;

            foreach ($assets as $asset)
            {
                $asset_id   = isset($asset['name']) ? $asset['name'] : uniqid();
                $asset_file = isset($asset['path']) ? $asset['path'] : null;
                $asset_dep  = [];

                if (!empty($asset_file))
                {
                    $asset_filename = $asset_file.".".$type;
                    
                    if (file_exists($path.$asset_filename))
                    {
                        if ($type === "css") 
                            wp_enqueue_style($asset_id, $url.$asset_filename);
                        
                        elseif ($type === "js") 
                            wp_enqueue_script($asset_id, $url.$asset_filename, $asset_dep);
                    }
                    else if (preg_match("/^http(s)?/i", $asset_file))
                    {
                        if ($type === "css") 
                            wp_enqueue_style($asset_id, $asset_file);
                        
                        elseif ($type === "js") 
                            wp_enqueue_script($asset_id, $asset_file, $asset_dep);
                    }
                }
            }
        }
        
        // Form Requests ///////////////////////////////////////////////////////

        public static function responses( $params )
        {
            $config = $params['config'];
            $schema = $params['schema'];
            $request = $_REQUEST[$config->Namespace];
            $files = !empty($_FILES[$config->Namespace]) ? $_FILES[$config->Namespace] : [];
            $responses = [];

            if (is_array($schema))
            {
                // Extract fields form sections of the schema
                foreach ($schema as $section)
                {
                    if (isset($section["schema"]))
                    {
                        foreach ($section['schema'] as $field) 
                        {
                            $error_messages = array(
                                "required"  => __("This field is required.", WPPPM_TEXTDOMAIN),
                                "email"     => __("This field is not a valid email address.", WPPPM_TEXTDOMAIN),
                                "url"       => __("This field is not a valid url.", WPPPM_TEXTDOMAIN),
                                "rule"      => __("This field is not valid.", WPPPM_TEXTDOMAIN)
                            );

                            // Default field settings
                            $field['allowed_types']     = isset($field['allowed_types']) ? $field['allowed_types'] : null;
                            $field['class']             = isset($field['class']) ? $field['class'] : false;
                            $field['choices']           = isset($field['choices']) ? $field['choices'] : false;
                            $field['cols']              = isset($field['cols']) ? $field['cols'] : false;
                            $field['default']           = isset($field['default']) ? $field['default'] : null;
                            $field['disabled']          = isset($field['disabled']) ? $field['disabled'] : false;
                            $field['expanded']          = isset($field['expanded']) ? $field['expanded'] : false;
                            $field['file']              = isset($field['file']) ? $field['file'] : null;
                            $field['helper']            = isset($field['helper']) ? $field['helper'] : null;
                            $field['key']               = isset($field['key']) ? $field['key'] : null;
                            $field['label']             = isset($field['label']) ? $field['label'] : null;
                            $field['max']               = isset($field['max']) ? $field['max'] : null;
                            $field['min']               = isset($field['min']) ? $field['min'] : null;
                            $field['multiple']          = isset($field['multiple']) ? $field['multiple'] : false;
                            $field['readonly']          = isset($field['readonly']) ? $field['readonly'] : false;
                            $field['required']          = isset($field['required']) ? $field['required'] : false;
                            $field['rows']              = isset($field['rows']) ? $field['rows'] : false;
                            $field['rule']              = isset($field['rule']) ? $field['rule'] : false;
                            $field['size']              = isset($field['size']) ? $field['size'] : 0;
                            $field['step']              = isset($field['step']) ? $field['step'] : null;
                            $field['type']              = isset($field['type']) ? $field['type'] : "text";
                            $field['error']             = false;
                            $field['thumbnails']        = false;

                            // Format data
                            if (!is_array($field['allowed_types']) && null !== $field['allowed_types'])
                            {
                                $field['allowed_types'] = self::exptrim(",", $field['allowed_types']);
                            }
    
                            // Add response to the field params
                            switch ($field['type'])
                            {
                                // Define checkbox value to ON or OFF
                                case 'checkbox':
                                    $field['value'] = isset($request[$field['key']]) ? "on" : "off";
                                    break;
    
                                // Hash the Password
                                case 'password':
                                    $field['value'] = !empty($request[$field['key']]) 
                                        ? password_hash($request[$field['key']], PASSWORD_DEFAULT) 
                                        : null;
                                    break;
                                    
                                // Retrieve file data
                                case 'file':
                                    if (!empty($files['name'][$field['key']]))
                                    {
                                        $field['files'] = [];
                                        foreach ($files as $key => $file)
                                        {
                                            if (isset($file[$field['key']]))
                                            {
                                                if (!is_array($file[$field['key']]))
                                                {
                                                    $field['files'][$key] = [$file[$field['key']]];
                                                }
                                                else
                                                {
                                                    $field['files'][$key] = $file[$field['key']];
                                                }
                                            }
                                        }
                                    }
                                    break;
    
                                // Add value
                                default:
                                    $field['value'] = $request[$field['key']];
                                    break;
                            }

                            // Custom error message
                            if (isset($field['error_messages']))
                            {
                                if (is_string($field['error_messages']))
                                {
                                    $error_messages = array(
                                        "required"  => __($field['error_messages'], $config->Namespce),
                                        "email"     => __($field['error_messages'], $config->Namespce),
                                        "url"       => __($field['error_messages'], $config->Namespce),
                                        "rule"      => __($field['error_messages'], $config->Namespce)
                                    );
                                }
                                else if (is_array($field['error_messages']))
                                {
                                    if (isset($field['error_messages']['required'])) $error_messages['required'] = $field['error_messages']['required'];
                                    if (isset($field['error_messages']['email'])) $error_messages['email'] = $field['error_messages']['email'];
                                    if (isset($field['error_messages']['url'])) $error_messages['url'] = $field['error_messages']['url'];
                                    if (isset($field['error_messages']['rule'])) $error_messages['rule'] = $field['error_messages']['rule'];
                                }
                            }
                            $field['error_messages'] = (object) $error_messages;                            
                            
                            if (!empty($field['key']))
                            {
                                $responses[$field['key']] = (object) $field;
                            }
                        }
                    }
                }
            }

            return $responses;
        }
        
        /**
         * Format response for wp_options
         */
        public static function responses_sanitized( $responses )
        {
            foreach ($responses as $response)
            {
                $responses[ $response->key ] = $response->value;
            }

            return $responses;
        }

        /**
         * Check response and generate errors tab
         */
        public static function validate( $params )
        {
            $config = $params['config'];
            $post_type = $params['post_type'];
            $responses = $params['responses'];
            $errors = [];
            $values = [];
            $success = [];

            if (!empty($responses) && is_array($responses))
            {
                foreach ($responses as $response)
                {
                    // Values
                    $values[$response->key] = $response->value;

                    // Is Required
                    if ($response->required && empty($response->value))
                    {
                        $response->error = true;
                        
                        $errors[$response->key] = array(
                            "field" => $response->key,
                            "message" => $response->error_messages->required
                        );
                    }
                    
                    // Is Email
                    else if ('email' === $response->type && !filter_var($response->value, FILTER_VALIDATE_EMAIL))
                    {
                        $response->error = true;

                        $errors[$response->key] = array(
                            "field" => $response->key,
                            "message" => $response->error_messages->email
                        );
                    }
                    
                    // Is url
                    else if ('url' === $response->type && !filter_var($response->value, FILTER_VALIDATE_URL))
                    {
                        $response->error = true;

                        $errors[$response->key] = array(
                            "field" => $response->key,
                            "message" => $response->error_messages->url
                        );
                    }
                    
                    // Is file
                    else if ('file' === $response->type && !empty($response->files))
                    {
                        $validate_file = self::validate_file( $response );

                        if (true !== $validate_file)
                        {
                            $response->error = true;
                            $errors[$response->key] = $validate_file;
                        }
                    }
                                        
                    // Rule Pattern
                    else if (!empty($response->rule) && !preg_match($response->rule, $response->value))
                    {
                        $response->error = true;

                        $errors[$response->key] = array(
                            "field" => $response->key,
                            "message" => $response->error_messages->rule
                        );
                    }

                    else
                    {
                        array_push($success, $response->key);
                    }
                }
            }

            $_SESSION[$post_type] = array(
                // If the submit has no error, we send $value as an empty array
                // to prevent to show data in form
                "values" => !empty($errors) ? $values : [],
                "errors" => $errors,
                "success" => $success,
                "isValid" => empty($errors)
            );

            return (object) $_SESSION[$post_type];
        }

        public static function validate_file( $field )
        {
            $files = [];
            $errors = [];

            // Rebuild $files array
            foreach ($field->files['name'] as $key => $file)
            {
                if (!empty($field->files['name'][$key]))
                {
                    $files[$key] = array(
                        "name" => $field->files['name'][$key],
                        "type" => $field->files['type'][$key],
                        "tmp_name" => $field->files['tmp_name'][$key],
                        "error" => $field->files['error'][$key],
                        "size" => $field->files['size'][$key]
                    );
                }
            }

            if (!empty($files))
            {
                foreach ($files as $file)
                {
                    // Defaults
                    $size = false;
                    $type = false;


                    // -- Check file size

                    // No sizes restrictions // Size is not defined
                    if (empty($field->size) || $field->size <= 0)
                    {
                        $size = true;
                    }
                    // File size is less or equal to the allowed size
                    else if ($file['size'] <= $field->size)
                    {
                        $size = true;
                    }


                    // -- Check file type
        
                    // All types are allowed, Allowed type are not defined
                    if (empty($field->allowed_types))
                    {
                        $type = true;
                    }
                    // Specific type is allowed
                    else if (in_array($file['type'], $field->allowed_types))
                    {
                        $type = true;
                    }
                    // Type like "image/*" is allowed
                    else
                    {
                        $iniversal_pattern = "/\/\*$/";
                        foreach ($field->allowed_types as $allowed_type)
                        {
                            if (preg_match($iniversal_pattern, $allowed_type))
                            {
                                $allowed_type = preg_replace($iniversal_pattern, null, $allowed_type);
        
                                if (preg_match("/^".$allowed_type."/", $file['type'])) 
                                {
                                    $type = true;
                                    break;
                                }
                            }
                        }
                    }


                    // -- Set errors

                    if (!$size)
                    {
                        array_push( $errors, $file['name']." - ". __("File is oversized.", WPPPM_TEXTDOMAIN));
                    }
                    if (!$type)
                    {
                        array_push( $errors, $file['name']." - ". __("File type is not allowed.", WPPPM_TEXTDOMAIN));
                    }                
                }

                if (!empty($errors))
                {
                    return array(
                        "field" => $file->key,
                        "message" => implode("<br>", $errors)
                    );
                }
            }

            return true;
        }

        /**
         * Upload file
         * Check wp-upload add to media and return upload data
         */
        public static function upload( $field, $pid, $config )
        {
            $uploads = [];

            if ('file' === $field->type && !empty($field->files))
            {
                $wp_sizes = ['medium', 'medium_large', 'large'];
                $register = ($pid != null) ? 'CustomPosts' : 'Settings';
                $restrictions = $config->ImagesSizes->$register;
                $posttype = $_REQUEST['post_type'];
                $GLOBALS['preserved_sizes'] = ['thumbnail'];
                $files = [];

                // Thumbnails Restrictions
                if ($register == "CustomPosts")
                {
                    $restrictions = $restrictions[$posttype];
                }
    
                // Rebuild $files array
                foreach ($field->files['name'] as $key => $file)
                {
                    if (!empty($field->files['name'][$key]))
                    {
                        $files[$key] = array(
                            "name" => $field->files['name'][$key],
                            "type" => $field->files['type'][$key],
                            "tmp_name" => $field->files['tmp_name'][$key],
                            "error" => $field->files['error'][$key],
                            "size" => $field->files['size'][$key]
                        );
                    }
                }
    
                foreach ($files as $file)
                {
                    $upload = wp_handle_upload($file, array('test_form' => false));

                    if (isset($upload['error']))
                    {
                        return array(
                            "errors" => array(
                                $file->key => array(
                                    "field" => $file->key,
                                    "message" => $upload['error']
                                )
                            )
                        );
                    }
                    else
                    {
                        // Add to medias list
                        $attach_id = wp_insert_attachment( [
                            'post_mime_type'    => $upload['type'],
                            'post_title'        => addslashes($file['name']),
                            'post_content'      => '',
                            'post_status'       => 'inherit',
                            'post_parent'       => $pid
                        ], $upload['file'], $pid);

                        // Apply Restrictions
                        if (true === $restrictions['strict'])
                        {
                            foreach ($restrictions['sizes'] as $size)
                            {
                                array_push($GLOBALS['preserved_sizes'], $size['name']);
                            }

                            if (true === $restrictions['preserve_wp_sizes'])
                            {
                                $GLOBALS['preserved_sizes'] = array_merge($GLOBALS['preserved_sizes'], $wp_sizes);
                            }

                            add_filter( 'intermediate_image_sizes_advanced', function( $sizes ) 
                            {
                                foreach ($sizes as $size => $null)
                                {
                                    if (!in_array($size, $GLOBALS['preserved_sizes']))
                                    {
                                        unset( $sizes[$size]);
                                    }
                                }

                                return $sizes;
                            } );
                        }

                        // Generate thumbnails
                        $attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
                        wp_update_attachment_metadata( $attach_id,  $attach_data );
                        
                        $existing_download = (int) get_post_meta($pid, $key, true);
                        if(is_numeric($existing_download)) {
                            wp_delete_attachment($existing_download);
                        }
                        
                        $upload['attachment'] = $attach_id;

                        array_push($uploads, $upload);
                    }
                }
            }
            
            return $uploads;
        }
    }
}
