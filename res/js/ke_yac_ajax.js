jQuery(document).ready(function(){
	
	// inits
	var $ = jQuery;
	var $spinner = $('.yac-spinner');
	
	// hide spinner initially
	$('.yac-spinner').hide();
	
	// calendar "prev" button
	$('#arrow_prev img').live('click', function(){
		refreshCalendarAndList('prev', 1);
	});
	
	// calendar "next" button
	$('#arrow_next img').live('click', function(){
		refreshCalendarAndList('next', 1);
	});
	
	// calendar navigation links
	$('a.navlink').live('click', function(e){
		e.preventDefault();
		$classes = $(this).attr('class').split(' ');
		$mode = $classes[1].substr(0,4);
		$step = Number($classes[1].substr(4));
		if ($mode=='next') $step+=2;
		refreshCalendarAndList($mode, $step);
	});
	
	// link to days' singleview from calendar
	$('a.daylink').live('click', function(e){
		e.preventDefault();
		dateParts = $(this).parent().attr('id').split('.');
		day = Number(dateParts[0]);
		month = Number(dateParts[1]);
		year = Number(dateParts[2].substr(0,4));
		
		$('.tx-keyac-pi1').has('#yac_calendars').each(function(){
			$previousHeight = $(this).height();
			$(this).html($spinner).height($previousHeight);
			$spinner.toggle();
		});
		
		$.ajax({
			type: "GET",
			url: "index.php?id="+ajaxPid+"&type="+ajaxType,
			data: { action: "refreshSingleview", day: day, month: month, year: year },
			dataType: "html"
		}).done(function(result) {
			$('.tx-keyac-pi1').has('.yac-spinner').each(function(){
				$spinner.toggle();
				$(this).replaceWith(result);
			});
			
		});
	});
	
	// singleview "next" link
	$('a.next').live('click', function(e){
		e.preventDefault();
		refreshSingleview('next');
	});
	
	// singleview "prev" link
	$('a.prev').live('click', function(e){
		e.preventDefault();
		refreshSingleview('prev');
	});
	
	// backlink from singleview to list
	$('a.backlink').live("click", function(e){
		e.preventDefault();
		month = Number(lastCalMonth);
		year = Number(lastCalYear);
		refreshCalendarAndList('none',0);
	});
	
	// link from list to singleview
	$('a.single').live("click", function(e){
		e.preventDefault();
		classes = $(this).attr('class');
		classesArray = classes.split(' ');
		refreshSingleview('single', Number(classesArray[1].substr(3)));
		
	});
	
	// main ajax function for refreshing calendar view
	refreshCalendarAndList = function(mode, step) {
		
		switch (mode) {
			case 'next':
				month = month+step;
				if (month>12) { month-=12; year+=1; }
				break;
			case 'prev':
				month = month-step;
				if (month<=0) { month+=12; year-=1; }
				break;
		}
		lastCalMonth = Number(month);
		lastCalYear = Number(year);
		$('.tx-keyac-pi1').each(function(){
			$previousHeight = $(this).height();
			$(this).html($spinner).height($previousHeight);
			$spinner.toggle();
		});
		$.ajax({
			type: "GET",
			url: "index.php?id="+ajaxPid+"&type="+ajaxType,
			data: { action: "refreshCalendarAndList", month: Number(month), year: Number(year) },
			dataType: "html"
		}).done(function(result) {
			$('.tx-keyac-pi1').has('.yac-spinner').each(function(){
				$spinner.toggle();
				$(this).replaceWith(result);
			});
			$("table.calendar a").each(function() {
				$(this).qtip({
					hide: {fixed: true, delay:3000},
					show: {solo: true},
					content: jQuery(this).parent().next('.yac-tooltip').html()
				});
			});
		});
	}
	
	// main ajax function for refreshing singleview
	refreshSingleview = function(mode, id) {
		singleUid = 0;
		daysInMonth = getDaysInMonth(year, month);
		switch (mode) {
			case 'next':
				day+=1;
				if (day > daysInMonth) {
					month+=1;
					if (month > 12) {
						month+=1;
						year+=1;
					}
					day = 1;
				}
				break;
			case 'prev':
				day -= 1;
				if (day <= 0) {
					month -= 1;
					if (month <=0) {
						month-=1;
						year-=1;
					}
					day = getDaysInMonth(year, month);
				}
				break;
			case 'single':
				singleUid = id;
				break;
		}
		$('.tx-keyac-pi1').each(function(){
			$(this).html($spinner);
			$spinner.toggle();
		});
		
		$.ajax({
			type: "GET",
			url: "index.php?id="+ajaxPid+"&type="+ajaxType,
			data: { action: "refreshSingleview", day: Number(day), month: Number(month), year: Number(year), singleUid: Number(singleUid)},
			dataType: "html"
		}).done(function(result) {
			$('.tx-keyac-pi1').each(function(){
				$spinner.toggle();
				$(this).replaceWith(result);
			});
		});
		
		

		
	}
	
	
});

// Returns the amount of days of a given month
function getDaysInMonth(year, month) {
    var daysInMonth = 32 - new Date( year, month, 32 ).getDate();
    return daysInMonth;
}