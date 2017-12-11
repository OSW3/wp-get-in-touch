// Multi File / Media preview
$(document).ready(function(){
    $('.wpppm-images-preview').bxSlider({
        pager: false,
        controls: true,
        touchEnabled: false,
        captions: true
    });
});

function render(props) {
    return function(tok, i) {
      return (i % 2) ? props[tok] : tok;
    };
  }

  
if (typeof 'widget_action_callback' !== "function") 
{ 
    function widget_action_callback( response )
    {
        response = JSON.parse(response);
        $template_ID = response.template_ID;
        $list_container = $('#'+response.widget_ID+'-list');
        $list_template = $('#'+response.widget_ID).find('script[data-template="'+$template_ID+'"]').text().split(/\$\{(.+?)\}/g);
        $list_container.append(response.posts.map(function(item) {
            return $list_template.map(render(item)).join('');
        }));
    }
} 