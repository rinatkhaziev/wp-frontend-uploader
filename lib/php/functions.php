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
	$mimes_exts = array (
		'doc' =>
		array (
			'label' => 'Microsoft Word Document',
			'mimes' =>
			array (
				0 => 'application/msword',
			),
		),
		'docx' =>
		array (
			'label' => 'Microsoft Word Open XML Document',
			'mimes' =>
			array (
				0 => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			),
		),
		'xls' =>
		array (
			'label' => 'Excel Spreadsheet',
			'mimes' =>
			array (
				0 => 'application/vnd.ms-excel',
				1 => ' application/msexcel',
				2 => ' application/x-msexcel',
				3 => ' application/x-ms-excel',
				4 => ' application/vnd.ms-excel',
				5 => ' application/x-excel',
				6 => ' application/x-dos_ms_excel',
				7 => ' application/xls',
			),
		),
		'xlsx' =>
		array (
			'label' => 'Microsoft Excel Open XML Spreadsheet',
			'mimes' =>
			array (
				0 => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			),
		),
		'pdf' =>
		array (
			'label' => 'Portable Document Format File',
			'mimes' =>
			array (
				0 => 'application/pdf',
				1 => ' application/x-pdf',
				2 => ' application/acrobat',
				3 => ' applications/vnd.pdf',
				4 => ' text/pdf',
				5 => ' text/x-pdf',
			),
		),
		'psd' =>
		array (
			'label' => 'Adobe Photoshop Document',
			'mimes' =>
			array (
				0 => 'image/photoshop',
				1 => ' image/x-photoshop',
				2 => ' image/psd',
				3 => ' application/photoshop',
				4 => ' application/psd',
				5 => ' zz-application/zz-winassoc-psd',
			),
		),
		'txt' =>
		array (
			'label' => 'Plain Text File',
			'mimes' =>
			array (
				0 => 'text/plain',
				1 => ' application/txt',
				2 => ' browser/internal',
				3 => ' text/anytext',
				4 => ' widetext/plain',
				5 => ' widetext/paragraph',
			),
		),
		'csv' =>
		array (
			'label' => 'Comma Separated Values File',
			'mimes' =>
			array (
				0 => 'text/comma-separated-values',
				1 => ' text/csv',
				2 => ' application/csv',
				3 => ' application/excel',
				4 => ' application/vnd.ms-excel',
				5 => ' application/vnd.msexcel',
				6 => ' text/anytext',
			),
		),
		'ppt' =>
		array (
			'label' => 'PowerPoint Presentation',
			'mimes' =>
			array (
				0 => 'application/vnd.ms-powerpoint',
				1 => ' application/mspowerpoint',
				2 => ' application/ms-powerpoint',
				3 => ' application/mspowerpnt',
				4 => ' application/vnd-mspowerpoint',
			),
		),
		'pptx' =>
		array (
			'label' => 'PowerPoint Open XML Presentation',
			'mimes' =>
			array (
				0 => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
			),
		),
		'mp3' =>
		array (
			'label' => 'MP3 Audio File',
			'mimes' =>
			array (
				0 => 'audio/mpeg',
				1 => ' audio/x-mpeg',
				2 => ' audio/mp3',
				3 => ' audio/x-mp3',
				4 => ' audio/mpeg3',
				5 => ' audio/x-mpeg3',
				6 => ' audio/mpg',
				7 => ' audio/x-mpg',
				8 => ' audio/x-mpegaudio',
			),
		),
		'avi' =>
		array (
			'label' => 'Audio Video Interleave File',
			'mimes' =>
			array (
				0 => 'video/avi',
				1 => ' video/msvideo',
				2 => ' video/x-msvideo',
				3 => ' image/avi',
				4 => ' video/xmpg2',
				5 => ' application/x-troff-msvideo',
				6 => ' audio/aiff',
				7 => ' audio/avi',
			),
		),
		'mp4' =>
		array (
			'label' => 'MPEG-4 Video File',
			'mimes' =>
			array (
				0 => 'video/mp4v-es',
				1 => ' audio/mp4',
			),
		),
		'mov' =>
		array (
			'label' => 'Apple QuickTime Movie',
			'mimes' =>
			array (
				0 => 'video/quicktime',
				1 => ' video/x-quicktime',
				2 => ' image/mov',
				3 => ' audio/aiff',
				4 => ' audio/x-midi',
				5 => ' audio/x-wav',
				6 => ' video/avi',
			),
		),
		'mpg' =>
		array (
			'label' => 'MPEG Video File',
			'mimes' =>
			array (
				0 => 'video/mpeg',
				1 => ' video/mpg',
				2 => ' video/x-mpg',
				3 => ' video/mpeg2',
				4 => ' application/x-pn-mpg',
				5 => ' video/x-mpeg',
				6 => ' video/x-mpeg2a',
				7 => ' audio/mpeg',
				8 => ' audio/x-mpeg',
				9 => ' image/mpg',
			),
		),
		'mid' =>
		array (
			'label' => 'MIDI File',
			'mimes' =>
			array (
				0 => 'audio/mid',
				1 => ' audio/m',
				2 => ' audio/midi',
				3 => ' audio/x-midi',
				4 => ' application/x-midi',
				5 => ' audio/soundtrack',
			),
		),
		'wav' =>
		array (
			'label' => 'WAVE Audio File',
			'mimes' =>
			array (
				0 => 'audio/wav',
				1 => ' audio/x-wav',
				2 => ' audio/wave',
				3 => ' audio/x-pn-wav',
			),
		),
		'wma' =>
		array (
			'label' => 'Windows Media Audio File',
			'mimes' =>
			array (
				0 => 'audio/x-ms-wma',
				1 => ' video/x-ms-asf',
			),
		),
		'wmv' =>
		array (
			'label' => 'Windows Media Video File',
			'mimes' =>
			array (
				0 => 'video/x-ms-wmv',
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