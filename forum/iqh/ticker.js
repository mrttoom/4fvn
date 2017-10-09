/**
 *  jQuery Rotator Plugin
 *  @requires jQuery v1.2.6 or greater
 *  http://hernan.amiune.com/labs
 *
 *  Copyright (c)  Hernan Amiune (hernan.amiune.com)
 *  Licensed under MIT license:
 *  http://www.opensource.org/licenses/mit-license.php
 * 
 *  Version: 1.0
 */
 
(function($){ $.fn.rotator = function(options){

    var defaults = {
		ms: 2000,
		n: 1,
		autoHeight: false
	};
  
    var options = $.extend(defaults, options);
	
	return this.each(function(index) {
		
		var $this = $(this);
		
		var initialHeight = 0;
		$this.children().filter(":lt("+options.n+")").each(function(index,item){
			initialHeight += $(item).height();
		});
		$this.height(initialHeight);

		setInterval(function(){
		    
			var childHeight = $this.children().filter(":first-child").height();
		    var animParams = {scrollTop: (childHeight) + "px"};
			var autoHeight = 0;
		    $this.children().filter(":lt("+(options.n+1)+")").each(function(index,item){
		        if(index>0)autoHeight += $(item).height();
		    });
			if(options.autoHeight)animParams = $.extend({height:(autoHeight) + "px"}, animParams);
		
			
		    $this.animate(animParams, 500, function(){
		        $this.scrollTop(0);
			    $this.append($this.children().filter(":first-child"));
				$this.css("overflow","hidden"); //Chrome hack
		    });

			
	    }, options.ms);
		

	});

  
}})(jQuery);
