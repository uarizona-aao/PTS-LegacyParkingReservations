$( document ).ready(function() {
	
	
	if(window.location.hash) {
  $("#toTop").parent().css("display","block");

}

	$(function() {
    var $affixElement = $('ul[data-spy="affix"]');
    $affixElement.width($affixElement.parent().width()-8);
	 
});

// Add smooth scrolling to all links inside a navbar
$("#toTop").on('click', function(event){
	event.preventDefault();
	var scrollAmount;

				$(this).parent().css("display","none");
				scrollAmount=$(window).scrollTop();
				
  $('html, body').animate({
    scrollTop: -scrollAmount
  }, 300, function(){
  });
  
  	 lastClicked = $(this).parent();
     setTimeout(
         function(){ 
			
	$("li").not(lastClicked).removeClass("active");
	},300);
});
	
	});



