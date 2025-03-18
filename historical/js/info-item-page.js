
$( document ).ready(function() {

	
	if(window.location.hash) {
  $("#toTop").parent().css("display","block");

}

	$(function() {
    var $affixElement = $('ul[data-spy="affix"]');
    $affixElement.width($affixElement.parent().width()-8);
	 
});



// Add smooth scrolling to all links inside a navbar
$("#myScrollspy a.scrollable").on('click', function(event){
	event.preventDefault();
		var scrollAmount;
if ($(this).attr("Id")=="toTop") {
				$(this).parent().css("display","none");
				scrollAmount=$(window).scrollTop();
			
		} else {
			$("#toTop").parent().css("display","block");
			$("a").parent().removeClass("active");
			$(this).parent().addClass('active');
			scrollAmount = $(hash).offset().top-100;
//	$("#myScrollSpy, #sidelinks").css("top","30px");
  // Prevent default anchor click behavior
   

  // Store hash (#)
		}
//	$("#myScrollSpy, #sidelinks").css("top","30px");
  // Prevent default anchor click behavior
   

  // Store hash (#)
 //  var hash = this.hash;

  // Using jQuery's animate() method to add smooth page scroll
  // The optional number (800) specifies the number of milliseconds it takes to scroll to the specified area (the speed of the animation)

 // var offTop=parseInt($(hash).offset().top); // -parseInt($(this).parent().position().top)-30;
  $('html, body').animate({
    scrollTop: scrollAmount
  }, 300, function(){

    // Add hash (#) to URL when done scrolling (default click behavior)
   //  window.location.hash = hash;
  });
  
  	 lastClicked = $(this).parent();
     setTimeout(
         function(){ 
	$("li").not(lastClicked).removeClass("active");
	// $("#toTop")
	},300);
});
	
	});


