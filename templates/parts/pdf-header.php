<?php
if ( !isset($title) ) $title = ucwords(get_the_title()) . ' PDF';

?>
<!doctype html>
<html lang="en">
<head>
	
	<meta charset="UTF-8">
	
	<title><?php echo $title; ?></title>
	
	<base href="<?php echo site_url(); ?>">
	
	<link rel="stylesheet" href="<?php echo esc_attr( ah_get_asset_url( 'fonts/fonts.css' ) ); ?>">
	
	<link rel="stylesheet" href="<?php echo esc_attr( ah_get_asset_url( 'pdf.css' ) ); ?>">
	
	<link rel="stylesheet" href="<?php echo esc_attr( ah_get_asset_url( 'pdf-shared.css' ) ); ?>">
	
<?php if ( ah_is_pdf_preview() ) { ?>
	<link rel="stylesheet" href="<?php echo esc_attr( ah_get_asset_url( 'pdf-preview.css' ) ); ?>">
<?php } ?>
	
	<script src="https://code.jquery.com/jquery-3.6.4.min.js" integrity="sha256-oP6HI9z1XaZNBrJURtCoUT5SUnxFr8s3BzRl+cbzUq8=" crossorigin="anonymous"></script>
	
	<script type="text/javascript" src="<?php echo esc_attr( ah_get_asset_url( 'pdf.js' ) ); ?>"></script>
</head>
<body>

<div class="pdf-container">