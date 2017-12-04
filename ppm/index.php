<?php

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) 
{
    echo "Hi there!<br>Do you want to plug me ?<br>";
	echo "If you looking for more about me, you can read at http://osw3.net/wordpress/plugins/please-plug-me/";
	exit;
}

// Files required
require_once(__DIR__.'/ppm.php');

// Plugin activation / desactivation
register_activation_hook( PPM::base(__DIR__)."/index.php", function(){
    $c = PPM::getClassname(); 
    $p = new $c(PPM::base(__DIR__));
    $p->activate();
});
register_deactivation_hook( PPM::base(__DIR__)."/index.php", function(){
    $c = PPM::getClassname(); 
    $p = new $c(PPM::base(__DIR__));
    $p->deactivate();
});
add_action( 'init', function(){
    $c = PPM::getClassname(); 
    $p = new $c(PPM::base(__DIR__));
    $p->start();
});

if (!class_exists(PPM::getClassname()))
{eval(sprintf('class %1$s extends PPM 
{
    public function start()
    {
        $this->init();
    }
    public function activate()
    {
        $this->installer()->activate();
    }
    public function deactivate()
    {
        $this->installer()->deactivate();
    }
}', PPM::getClassname()));}