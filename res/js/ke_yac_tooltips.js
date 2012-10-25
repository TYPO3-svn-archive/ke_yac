jQuery(document).ready(function(){
	
	jQuery("table.calendar a").each(function() {
		$(this).qtip({
			hide: {fixed: true, delay:3000},
			show: {solo: true},
			content: jQuery(this).parent().next('.yac-tooltip').html()
		});
	});
	
});

