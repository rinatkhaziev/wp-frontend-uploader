<?php
/**
 * Various helper functions
 */

/**
 * Get the common MIME-types for extensions
 * @return array
 */
function fu_get_mime_types() {
	// Generated with dyn_php class: http://www.phpclasses.org/package/2923-PHP-Generate-PHP-code-programmatically.html
	$mimes_exts = array(
		'csv'=>
		array(
			'label'=> 'Comma Separated Values File',
			'mimes'=>
			array(
				'text/comma-separated-values',
				'text/csv',
				'application/csv',
				'application/excel',
				'application/vnd.ms-excel',
				'application/vnd.msexcel',
				'text/anytext',
			),
		),
		'mp3'=>
		array(
			'label'=> 'MP3 Audio File',
			'mimes'=>
			array(
				'audio/mpeg',
				'audio/x-mpeg',
				'audio/mp3',
				'audio/x-mp3',
				'audio/mpeg3',
				'audio/x-mpeg3',
				'audio/mpg',
				'audio/x-mpg',
				'audio/x-mpegaudio',
			),
		),
		'avi'=>
		array(
			'label'=> 'Audio Video Interleave File',
			'mimes'=>
			array(
				'video/avi',
				'video/msvideo',
				'video/x-msvideo',
				'image/avi',
				'video/xmpg2',
				'application/x-troff-msvideo',
				'audio/aiff',
				'audio/avi',
			),
		),

		'mid'=>
		array(
			'label'=> 'MIDI File',
			'mimes'=>
			array(
				'audio/mid',
				'audio/m',
				'audio/midi',
				'audio/x-midi',
				'application/x-midi',
				'audio/soundtrack',
			),
		),
		'wav'=>
		array(
			'label'=> 'WAVE Audio File',
			'mimes'=>
			array(
				'audio/wav',
				'audio/x-wav',
				'audio/wave',
				'audio/x-pn-wav',
			),
		),
		'wma'=>
		array(
			'label'=> 'Windows Media Audio File',
			'mimes'=>
			array(
				'audio/x-ms-wma',
				'video/x-ms-asf',
			),
		),
	);

	return $mimes_exts;
}

/**
 * Generate slug => description array for Frontend Uploader settings
 * @return array
 */
function fu_get_exts_descs() {
	$mimes = fu_get_mime_types();
	$a = array();

	foreach( $mimes as $ext => $mime )
		$a[$ext] = sprintf( '%1$s (.%2$s)', $mime['label'], $ext );

	return $a;
}

function fu_get_option( $slug = '' ) {
	static $options;
	$slug = sanitize_key( $slug );
	if ( ! $options )
		$options  = $GLOBALS['frontend_uploader']->settings;

	return isset( $options[ $slug ] ) ? $options[ $slug ] : '';
}

function fu_get_recaptcha_markup() {
	return '<div class="g-recaptcha" data-sitekey="' . esc_attr( fu_get_option( 'recaptcha_site_key' ) ) . '"></div>';
}

function fu_get_file_array() {
    $walker = function ($arr, $fileInfokey, callable $walker) {
        $ret = array();
        foreach ($arr as $k => $v) {
            if (is_array($v)) {
                $ret[$k] = $walker($v, $fileInfokey, $walker);
            } else {
                $ret[$k][$fileInfokey] = $v;
            }
        }
        return $ret;
    };

    $files = array();
    foreach ($_FILES as $name => $values) {
        // init for array_merge
        if (!isset($files[$name])) {
            $files[$name] = array();
        }
        if (!is_array($values['error'])) {
            // normal syntax
            $files[$name] = $values;
        } else {
            // html array feature
            foreach ($values as $fileInfoKey => $subArray) {
                $files[$name] = array_replace_recursive($files[$name], $walker($subArray, $fileInfoKey, $walker));
            }
        }
    }

    return $files;
}

function fu_email_content_type( $content_type ) {
	return 'text/html';
}
