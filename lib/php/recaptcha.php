<?php

add_filter( 'fu_should_process_content_upload', 'fu_recaptcha_check_submission', 10, 2 );
// add_action( 'fu_additional_html', 'fu_recaptcha_additional_html' );
add_action( 'wp_head', 'fu_add_recaptcha_js' );

function fu_add_recaptcha_js() {
	?>
	<script src='https://www.google.com/recaptcha/api.js' async defer></script>
	<?php
}

function fu_recaptcha_check_submission( $should_process, $layout ) {
	// Recaptcha is enabled but payload is missing g-recaptcha-response field
	if ( !isset( $_POST['g-recaptcha-response'] ) )
		return false;

	$req = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', [
		'body' => [
			'secret' => fu_get_option( 'recaptcha_secret_key' ),
			'response' => $_POST['g-recaptcha-response'],
			'remoteip' => $_SERVER['REMOTE_ADDR']
		],
		'timeout' => 1,
	] );

	// Request failed, let's bail
	if ( is_wp_error( $req ) )
		return $should_process;

	$res = json_decode( wp_remote_retrieve_body( $req ) );

	return $res->success;
}

function fu_recaptcha_additional_html() {
	echo fu_get_recaptcha_markup();
}