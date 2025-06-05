if (typeof (window.UM) !== 'object') {
	window.UM = {};
}

UM.recaptchaList = [];

UM.recaptcha = function( $recaptcha ) {
	this.wrapper = $recaptcha;
	this.sitekey = $recaptcha.data('sitekey');
	this.mode = $recaptcha.data('mode');
	this.size = $recaptcha.data('size');
	this.theme = $recaptcha.data('theme');
};

UM.recaptcha.prototype = {
	wrapper: null,
	sitekey: '',
	mode: '',
	size: '',
	theme: '',
	render: function() {
		if ( 'invisible' === this.size ) {
			let $form = this.wrapper.parents('form');
			let $submitButton = $form.find('input[type="submit"]');
			if ( ! $submitButton.length ) {
				$submitButton = $form.find('button[type="submit"]'); // new UI workaround
			}

			grecaptcha.render(
				$submitButton.attr('id'),
				{
					'sitekey': this.sitekey,
					'callback': function( token ) {
						$form.attr('disabled', 'disabled').submit();
					}
				}
			);
		} else {
			grecaptcha.render(
				this.wrapper.attr('id'),
				{
					'sitekey': this.sitekey,
					'theme': this.theme,
					'size' : this.size,
				}
			);
		}
	},
	refresh: function() {
		if ( 'invisible' !== this.size ) {
			this.wrapper.html('');
		}
		if ( typeof grecaptcha === 'object' && grecaptcha.reset() ) {
			this.render();
		}
	},
	reset: function() {
		if ( typeof grecaptcha === 'object' ) {
			grecaptcha.reset();
		}
	}
}

var UMreCAPTCHAonLoad = function () {
	jQuery('.um-recaptcha').each( function (i) {
		let $recaptcha = jQuery(this);

		if ( UM.recaptchaList[ $recaptcha.attr('id') ] ) {
			UM.recaptchaList[ $recaptcha.attr('id') ].refresh();
		} else {
			let recaptchaObj = new UM.recaptcha( $recaptcha );

			UM.recaptchaList[ $recaptcha.attr('id') ] = recaptchaObj;
			recaptchaObj.render();
		}
	});
};

wp.hooks.addAction( 'um_messaging_open_login_form', 'um_recaptcha', UMreCAPTCHAonLoad);
wp.hooks.addAction( 'um_messaging_close_login_form', 'um_recaptcha', UMreCAPTCHAonLoad); // Old UI only.

wp.hooks.addAction( 'um-modal-before-close', 'um_recaptcha', function( $modal ) { // New UI only.
	$modal.find('.um-recaptcha').each( function (i) {
		let $recaptcha = jQuery(this);

		if ( UM.recaptchaList[ $recaptcha.attr('id') ] ) {
			UM.recaptchaList[ $recaptcha.attr('id') ].reset();
			delete UM.recaptchaList[ $recaptcha.attr('id') ];
		}
	});
});
