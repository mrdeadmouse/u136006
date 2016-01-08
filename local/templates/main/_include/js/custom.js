// superfish menu nav
$(document).ready(function() {
    		// Main navigation dropdowns
            $('ul.sf-menu').superfish({
                delay:       300,                            // one second delay on mouseout
                animation:   { height:'show' },              // fade-in and slide-down animation
                speed:       'fast',                         // faster animation speed
                autoArrows:  true,                           // disable generation of arrow mark-up
                disableHI:   true,
                dropShadows: false                           // disable drop shadows
            });
         });
        jQuery(document).ready(function() {
          /* portfolio image effect */
          $("img.a").hover(
          function() {  $(this).stop().animate({"opacity": "0.7"}, "slow");  },
          function() {  $(this).stop().animate({"opacity": "1"}, "slow");  });
          $("img.pthumb").hover(
          function() {  $(this).stop().animate({"opacity": "0.7"}, "slow");  },
          function() {  $(this).stop().animate({"opacity": "1"}, "slow");  });
          $("ul.tabs").tabs("div.panes > div");
          $("ul.css-tabs").tabs("div.css-panes > div");
          $(".scrollable").scrollable();
          $(".accordion").tabs(".pane", {tabs: 'h2', effect: 'slide'});
          $(".accordion-faq").tabs(".pane", {tabs: 'span', effect: 'slide'});
          /* $(function() {
          $("ul.pricing-col-small").mouseover(function(){
          $(this).removeClass().addClass("highlight-small");
          }).mouseout(function(){
          $(this).removeClass().addClass("pricing-col-small");
          })}); */
 });


$(function() {
    /* External links open in new windows */
    $("a[rel='external']").bind("click.external", function(){
        window.open(this.href);
        return false;
    });
});

// simple hide no animation
function hide(id){
    if (document.getElementById){
    obj = document.getElementById(id);
    obj.style.display = "none";
    }     }

// simple show no animation
function show(id){
    if (document.getElementById){
    obj = document.getElementById(id);
    obj.style.display = "";
    }    }

// jquery hide, show and toggle
function ajaxshow(id){	$(id).fadeIn("slow");	}
function ajaxhide(id){	$(id).fadeOut("slow");	}
function toggle(id){	$(id).slideToggle("fast"); }



//quicskand gallery categories filtering
jQuery(document).ready(function($){
 	// clone applications to get a second collection
	var $data = $(".gallery-content .container").clone();
 	//note: only filter on the main portfolio page, not on the subcategory pages
	$('.gallery-main li').click(function(e) {
		$(".filter li").removeClass("current-cat");
		//use the last category class as the category to filter by. This means that multiple categories are not supported (yet)
		var filterClass=$(this).attr('class').split(' ').slice(-1)[0];

		if (filterClass == 'all-items') {
			var $filteredData = $data.find('.gallery-item');
		} else {
			var $filteredData = $data.find('.gallery-item[data-type=' + filterClass + ']');
		}
		$(".gallery-content .container").quicksand($filteredData, {
			duration: 700,
			easing: 'easeInOutQuad'
		}, function(){
				//callback function to re-apply hover effects on cloned elements
                $("img.pthumb").hover(
                function() {  $(this).stop().animate({"opacity": "0.7"}, "slow");  },
                function() {  $(this).stop().animate({"opacity": "1"}, "slow");  });
                $("a[rel^='prettyPhoto']").prettyPhoto();
		});

		$(this).addClass("current-cat");
		return false;
	});
});

// Cufon Replacements
Cufon.replace(' h1, h2, h3, h4, h5, .featured-top, .pack .button, .pack-last .button, .textbutton, .applyfont, ul.css-tabs a', { hover: true, fontFamily: 'Museo' });
Cufon.replace('.header h1, .header-alt h1, .header-alt-sec h1, .header-alt h4, .header-alt-sec h4, .header-inner h1, .subtitle h3', { hover: true, textShadow: '1px 1px 0 #444444', fontFamily: 'Museo' });
Cufon.replace('.btn-small, .btn-medium, .btn-big, .btn-big-light, .form-button, .accordion h2, .text-replace, .big-home-button', { hover: true, textShadow: '1px 1px 0 #ffffff', fontFamily: 'Museo' });
Cufon.replace('.btn-orange-small, .btn-orange-medium, .btn-orange-big', { hover: true, textShadow: '1px 1px 0 #cb6a20', fontFamily: 'Museo' });
Cufon.replace('.btn-green-small, .btn-green-medium, .btn-green-big', { hover: true, textShadow: '1px 1px 0 #588b11', fontFamily: 'Museo' });
Cufon.replace('.big-footer h4', { textShadow: '1px 1px 0 #555555', fontFamily: 'Museo' });
