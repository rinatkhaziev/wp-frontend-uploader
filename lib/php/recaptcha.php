<?php

add_filter( 'fu_should_process_content_upload', 'fu_recaptcha_check_submission', 10, 2 );
add_action( 'wp_head', 'fu_add_recaptcha_js' );

function fu_add_recaptcha_js() {
	global $frontend_uploader;
	?>
	<script src='<?php echo esc_url( add_query_arg( array( 'hl' => $frontend_uploader->lang_short ), 'https://www.google.com/recaptcha/api.js' ) ) ?>' async defer></script>
	<?php
}
function fu_recaptcha_check_submission( $should_process, $layout ) {

	// Recaptcha is enabled but payload is missing g-recaptcha-response field
	// or it's empty
	if ( !isset( $_POST['g-recaptcha-response'] ) || ! $_POST['g-recaptcha-response'] )
		return false;

	$req = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', array(
		'body' => array(
			'secret' => fu_get_option( 'recaptcha_secret_key' ),
			'response' => sanitize_text_field( $_POST['g-recaptcha-response'] ),
			'remoteip' => $_SERVER['REMOTE_ADDR']
		),
		'timeout' => 3,
	) );

	// Request failed, fail the check
	// Because we have no means to verify if it's a valid submission
	if ( is_wp_error( $req ) )
		return false;

	$res = json_decode( wp_remote_retrieve_body( $req ) );

	return $res->success;
}

function fu_recaptcha_additional_html() {
	echo fu_get_recaptcha_markup();
}