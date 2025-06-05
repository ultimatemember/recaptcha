if ( typeof (window.UM) !== 'object' ) {
	window.UM = {};
}

if ( typeof ( UM.recaptcha ) !== 'object' ) {
	UM.recaptcha = {
		validate: function (e) {
			e.preventDefault();

			let $form = jQuery(e.target);
			let action = $form.find('.um-recaptcha').data('mode');
			let sitekey = $form.find('.um-recaptcha').data('sitekey');
			if ( ! action || ! sitekey ) {
				return;
			}

			$form.find('.um-recaptcha').addClass('um-inited');

			grecaptcha.execute(sitekey, {
				action: action
			}).then(function (token) {

				if ($form.find('[name="g-recaptcha-response"]').length) {
					$form.find('[name="g-recaptcha-response"]').val(token);
				} else {
					$form.append('<input type="hidden" name="g-recaptcha-response" value="' + token + '">');
				}

				$form.off('submit', UM.recaptcha.validate).trigger('submit');
			});
		}
	};
}

var UMreCAPTCHAonLoad = function () {
	jQuery('.um-recaptcha:not(.um-inited)').closest('form').on('submit', UM.recaptcha.validate);
};

grecaptcha.ready(UMreCAPTCHAonLoad);

wp.hooks.addAction( 'um_messaging_open_login_form', 'um_recaptcha', UMreCAPTCHAonLoad);
