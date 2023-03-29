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
	
	<link rel="stylesheet" href="<?php echo esc_attr( ah_get_asset_url( 'pdf-shared.css' ) ); ?>">
	
	<link rel="stylesheet" href="<?php echo esc_attr( ah_get_asset_url( 'pdf.css' ) ); ?>">
	
<?php if ( ah_is_pdf_preview() ) { ?>
	<link rel="stylesheet" href="<?php echo esc_attr( ah_get_asset_url( 'pdf-preview.css' ) ); ?>">
<?php } ?>
	
	<script src="<?php echo esc_attr( ah_get_asset_url( 'jquery-3.6.4.min.js' ) ); ?>"></script>
	
	<script type="text/javascript" src="<?php echo esc_attr( ah_get_asset_url( 'pdf.js' ) ); ?>"></script>
	
</head>
<body>

<htmlpagefooter name="GeneralFooter" style="display: none;">
	<table width="100%">
		<tr>
			<td width="33%"></td>
			<td width="33%" align="center"></td>
			<td width="33%" align="right">{PAGENO}</td>
		</tr>
	</table>
</htmlpagefooter>

<div class="pdf-container">