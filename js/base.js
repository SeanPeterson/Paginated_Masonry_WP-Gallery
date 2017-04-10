/*-----------------Initialize----------------------*/
initMasonryGallery('0.4s');	

/*-----------------Functions----------------------*/

//Init or re-init the masonty gallery.
//For mobile transitions are disabled
function initMasonryGallery(durationTime)
{
	var opts = {
	        itemSelector: '.gallery-item',
	        gutter: 5,
	        transitionDuration: durationTime
	    }
	    $grid = jQuery('.gallery').masonry(opts);  
		// layout Masonry after each image loads
		$grid.imagesLoaded().progress( function() {
		  $grid.masonry('layout');
		});	
}