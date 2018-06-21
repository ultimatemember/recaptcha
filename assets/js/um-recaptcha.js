jQuery( document ).ready( function() {
	jQuery( document ).on( "um_messaging_open_login_form", function(e) {
		um_recaptcha_refresh();
	});

	jQuery( document ).on( "um_messaging_close_login_form", function(e) {
		um_recaptcha_refresh();
	});
});