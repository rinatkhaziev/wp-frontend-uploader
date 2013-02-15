jQuery( function($) {
	$( '#ugc-media-form' ).validate({
		submitHandler: function(form) {
		form.submit();
		}
	});
});