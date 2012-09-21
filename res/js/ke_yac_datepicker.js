jQuery(document).ready(function(){
	
	$.datepicker.regional['de'] = {
		closeText: 'schließen',
		prevText: '&#x3c;zurück',
		nextText: 'Vor&#x3e;',
		currentText: 'heute',
		monthNames: ['Januar','Februar','März','April','Mai','Juni',
		'Juli','August','September','Oktober','November','Dezember'],
		monthNamesShort: ['Jan','Feb','Mär','Apr','Mai','Jun',
		'Jul','Aug','Sep','Okt','Nov','Dez'],
		dayNames: ['Sonntag','Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag'],
		dayNamesShort: ['So','Mo','Di','Mi','Do','Fr','Sa'],
		dayNamesMin: ['So','Mo','Di','Mi','Do','Fr','Sa'],
		weekHeader: 'KW',
		dateFormat: 'dd.mm.yy',
		firstDay: 1,
		isRTL: false,
		showMonthAfterYear: false,
		yearSuffix: ''};
	$.datepicker.setDefaults($.datepicker.regional['de']);
	jQuery.datepicker.setDefaults(jQuery.datepicker.regional["de"]);
	
	jQuery('#startdat').datetimepicker({
		showSecond: false,
		timeFormat: 'hh:mm:ss',
		stepHour: 2,
		stepMinute: 10,
		stepSecond: 10
	});
	
	jQuery('#enddat').datetimepicker({
		showSecond: false,
		timeFormat: 'hh:mm:ss',
		stepHour: 2,
		stepMinute: 10,
		stepSecond: 10
	});
	
	jQuery("table.calendar a").each(function() {
		$(this).qtip({
			hide: {fixed: true, delay:3000},
			show: {solo: true},
			content: jQuery(this).parent().next('.yac-tooltip').html()
		});
	});
	
	
});

