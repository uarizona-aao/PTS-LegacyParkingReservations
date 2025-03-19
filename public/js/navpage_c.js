/*
 * 
 *  code for scrolling pages with navbar and sub nav sections
 * 
 * 
 * 
 */	
	$( document ).ready(function() {
	$(function() {
    var $affixElement = $('ul[data-spy="affix"]');
    $affixElement.width($affixElement.parent().width());
	$('.open-first').trigger('click');
	 
});

	if(window.location.hash) {
  $("#toTop").parent().css("display","block");
}
 

// Add smooth scrolling to all links inside a navbar
$("#myScrollspy a").not(".accordion-toggle").on('click', function(event){
	event.preventDefault();
	if ($(this).attr("Id")=="toTop") {
				$(this).parent().css("display","none");
		} else {
			$("#toTop").parent().css("display","block");
			$("a").parent().removeClass("active");
			$(this).parent().addClass('active');
//	$("#myScrollSpy, #sidelinks").css("top","30px");
  // Prevent default anchor click behavior
   

  // Store hash (#)
		}
  var hash = this.hash;

  // Using jQuery's animate() method to add smooth page scroll
  // The optional number (800) specifies the number of milliseconds it takes to scroll to the specified area (the speed of the animation)

  var offTop=parseInt($(hash).offset().top); // -parseInt($(this).parent().position().top)-30;
  $('html, body').animate({
    scrollTop: offTop-100
  }, 300, function(){

    // Add hash (#) to URL when done scrolling (default click behavior)
   //  window.location.hash = hash;

	 lastClicked = $(this).parent();
     setTimeout(
         function(){ 
	$("li li").not(lastClicked).removeClass("active");
	$("#toTop").parent().css("background-color","#FF0000;");
	// $("#toTop")
	},100);
  });
  
		
});
	});
	var lastClicked
	