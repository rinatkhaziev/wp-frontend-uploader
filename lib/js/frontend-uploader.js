jQuery(function($) {

	$('#ug-photo-form').validate({
		submitHandler: function(form) {
		form.submit();
		}
	});

	$('#ug-photo-form .submit').click(function(e) {
		e.preventDefault();
		var el = $('#ug-photo-form');
		el.submit();
	});	
	
})