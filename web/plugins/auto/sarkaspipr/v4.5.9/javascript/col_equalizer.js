var colEqualize = function(selector){
	var currentTallest = 0,
	     currentRowStart = 0,
	     rowDivs = new Array(),
	     $el,
	     topPosition = 0;

	jQuery(selector).each(function() {

	 $el = $(this);
	 topPostion = $el.position().top;

	 if (currentRowStart != topPostion) {
	   // we just came to a new row.  Set all the heights on the completed row
	   for (currentDiv = 0 ; currentDiv < rowDivs.length ; currentDiv++) {
	     rowDivs[currentDiv].height(currentTallest);
	   }

	   // set the variables for the new row
	   rowDivs.length = 0; // empty the array
	   currentRowStart = topPostion;
	   currentTallest = $el.height();
	   rowDivs.push($el);

	 } else {
	   // another div on the current row.  Add it to the list and check if it's taller
	   rowDivs.push($el);
	   currentTallest = (currentTallest < $el.height()) ? ($el.height()) : (currentTallest);
	}

	 // do the last row
	 for (currentDiv = 0 ; currentDiv < rowDivs.length ; currentDiv++) {
	   rowDivs[currentDiv].height(currentTallest);
	 }

	});
}
var activeColEqualization = function(selector){
	jQuery(function(){
		var t = null;
		jQuery(selector).height('auto');
		t = setTimeout(function(){colEqualize(selector)},250);
		jQuery(window).resize(function(){
			if (t) clearTimeout(t);
			t = setTimeout(function(){colEqualize(selector)},500);
		});
		onAjaxLoad(function(){t = setTimeout(function(){console.log(selector);console.log(jQuery(selector));colEqualize(selector)},50);});
	});
}