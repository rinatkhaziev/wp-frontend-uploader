<?php
/**
 * Various helper functions
 */


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
