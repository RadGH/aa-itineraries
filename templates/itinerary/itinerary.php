<?php
// Itinerary content
// Used when viewing an itinerary, or when download a pdf

$slug = get_post_field( 'post_name', get_the_ID() );

$logo_id = get_field( 'white_logo', 'ah_settings' );

$title = get_field( 'title', get_the_ID() );
$subtitle = get_field( 'subtitle', get_the_ID() );
$introduction_message = get_field( 'introduction_message', get_the_ID() );
$contact_information = get_field( 'contact_information', get_the_ID() );

$date_range = get_field( 'date_range', get_the_ID() );

$schedule = get_field( 'schedule', get_the_ID() );

$departure_information = get_field( 'departure_information', get_the_ID() );

$all_phone_numbers = (array) get_field( 'phone_numbers', get_the_ID() ); // title, phone_number, content
$has_phone_numbers = ! ah_is_array_recursively_empty( $all_phone_numbers );

$country_codes = get_field( 'country_codes', get_the_ID() );

$tour_overview = get_field( 'tour_overview', get_the_ID() );

$villages = get_field( 'villages', get_the_ID() );

$hikes = get_field( 'hikes', get_the_ID() );

$attached_documents = get_field( 'attached_documents', get_the_ID() );

if ( ! ah_is_pdf() ) {
	// Web page view
	?>
	<div class="ah-pdf-download-button">
		<a href="<?php echo get_permalink(); ?>/download/" target="download_<?php the_ID(); ?>" class="button">Download PDF</a>
	</div>
	<?php
}


if ( ah_is_pdf() ) {
	// PDF view
	?>
	<!-- Intro -->
	<htmlpagefooter name="itinerary_intro_footer" style="display:none">
		<table class="footer-table footer-table-itinerary-intro" width="100%"><tr>
				<td width="50%"></td>
				<td width="50%" align="right"><?php ah_display_content_columns( $contact_information ); ?></td>
			</tr></table>
	</htmlpagefooter>
	
	<style>
		.footer-table-itinerary-intro {
			font-weight: bold;
			color: #204f66;
		}
		
		#intro {
			page: itinerary_intro;
		}

		@page itinerary_intro {
			even-footer-name: itinerary_intro_footer;
			odd-footer-name: itinerary_intro_footer;
			margin-top: 0;
		}
	</style>
	
	
	<!-- Schedule -->
	<htmlpagefooter name="itinerary_schedule_footer" style="display:none">
		<table class="footer-table" width="100%"><tr>
			<td width="50%">Schedule</td>
			<td width="50%" align="right">{PAGENO}</td>
		</tr></table>
	</htmlpagefooter>
	
	<style>
		#schedule {
			page: itinerary_schedule;
		}

		@page itinerary_schedule {
			odd-footer-name: itinerary_schedule_footer;
			even-footer-name: itinerary_schedule_footer;
		}
	</style>
	
	
	<!-- Directory -->
	<htmlpagefooter name="itinerary_directory_footer" style="display:none">
		<table class="footer-table" width="100%"><tr>
			<td width="50%">Directory</td>
			<td width="50%" align="right">{PAGENO}</td>
		</tr></table>
	</htmlpagefooter>
	
	<style>
		#directory {
			page: itinerary_directory;
		}

		@page itinerary_directory {
			odd-footer-name: itinerary_directory_footer;
			even-footer-name: itinerary_directory_footer;
		}
	</style>
	
	
	<!-- Tour Overview -->
	<htmlpagefooter name="itinerary_tour_overview_footer" style="display:none">
		<table class="footer-table" width="100%"><tr>
			<td width="50%">Tour Overview</td>
			<td width="50%" align="right">{PAGENO}</td>
		</tr></table>
	</htmlpagefooter>
	
	<style>
		#tour-overview {
			page: itinerary_tour_overview;
		}
		
		@page itinerary_tour_overview {
			odd-footer-name: itinerary_tour_overview_footer;
			even-footer-name: itinerary_tour_overview_footer;
		}
	</style>
	
	
	<!-- Documents -->
	<htmlpagefooter name="documents_footer" style="display:none">
		<table class="footer-table" width="100%"><tr>
			<td width="50%">Documents</td>
			<td width="50%" align="right">{PAGENO}</td>
		</tr></table>
	</htmlpagefooter>
	
	<style>
		#page-documents {
			page: documents_page;
		}
		
		@page documents_page {
			odd-footer-name: documents_footer;
			even-footer-name: documents_footer;
		}
	</style>
	<?php
}
?>

<section id="itinerary" class="pdf-section itinerary itinerary-<?php echo esc_attr($slug); ?> itinerary-id-<?php the_ID(); ?>">
	
	<?php
	$show_intro_page = ( $introduction_message || $contact_information );
	
	if ( $show_intro_page ) {
	?>
	<div class="pdf-page" id="intro">
		
		<?php ah_display_bookmark( 'Introduction', 0 ); ?>
		
		<?php if ( $title && !ah_is_pdf() ) { ?>
			<div class="section-heading itinerary-heading">
				<?php echo '<h1 class="pdf-title">', $title, '</h1>'; ?>
			</div>
		<?php }else if ( $logo_id ) { ?>
			<div class="section-logo itinerary-logo">
				<a href="<?php echo site_url('/'); ?>"><?php ah_display_image( $logo_id, 250, 0 ); ?></a>
			</div>
		<?php } ?>
		
		<!-- Clear float for download PDF button -->
		<div style="overflow: hidden;clear: both;"></div>
		
		<?php if ( $introduction_message ) { ?>
			<div class="section-content itinerary-summary">
				<?php ah_display_content_columns( $introduction_message ); ?>
			</div>
		<?php } ?>
		
		<?php // On PDF, this moved to footer. See above htmlpagefooter
		if ( (! ah_is_pdf() || ah_is_pdf_preview()) && $contact_information ) { ?>
			<div class="section-content itinerary-contact">
				<?php ah_display_content_columns( $contact_information ); ?>
			</div>
		<?php } ?>
	
	</div>
	<?php } ?>
	
	<?php
	$show_schedule_page = ( $schedule || $departure_information );
	
	// If the intro page was not displayed, force the schedule to display
	if ( ! $show_intro_page ) $show_schedule_page = true;
	
	if ( $show_schedule_page ) {
	?>
	<div class="pdf-page" id="schedule">
		
		<?php ah_display_bookmark( 'Schedule', 0 ); ?>
		
		<div class="section-heading itinerary-heading">
			<?php if ( $title ) echo '<h1 class="pdf-title">', $title, '</h1>'; ?>
			
			<?php if ( $subtitle ) echo '<h2 class="pdf-subtitle">', $subtitle, '</h2>'; ?>
			
			<?php if ( $date_range ) echo '<h2 class="pdf-subtitle date-range">', $date_range, '</h2>'; ?>
		</div>
		
		<?php if ( $schedule || $departure_information ) { ?>
		<div class="section-schedule">
			
			<?php
			echo '<table class="schedule-table columns-3"><tbody>';
			
			if ( $schedule ) foreach( $schedule as $i ) {
				echo '<tr class="schedule">';
					echo '<td class="column column-1">', nl2br($i['column_1']), '</td>';
					echo '<td class="column column-2">', nl2br($i['column_2']), '</td>';
					echo '<td class="column column-3">', nl2br($i['column_3']), '</td>';
				echo '</tr>';
			}
			
			if ( $departure_information ) {
				echo '<tr class="department-information">';
				echo '<td class="column column-1">Departure Information:</td>';
				echo '<td class="column column-2-3" colspan="2">', nl2br($departure_information), '</td>';
				echo '</tr>';
			}
			
			echo '</tbody></table>';
			?>
		</div>
		<?php } ?>
		
	</div>
	<?php
	}
	?>
	
	<?php
	$show_directory_page = ( $has_phone_numbers || $country_codes );
	if ( $show_directory_page ) {
	?>
	<div class="pdf-page" id="directory">
		
		<?php ah_display_bookmark( 'Directory', 0 ); ?>
		
		<div class="section-heading itinerary-heading">
			<h1>Directory</h1>
		</div>
		
		<div class="section-directory">
			
			<?php
			if ( $all_phone_numbers ) {
				echo '<table class="directory-table columns-2"><tbody>';
				foreach( $all_phone_numbers as $i ) {
					
					if ( $i['title'] || $i['phone_number'] ) {
						echo '<tr>';
						
						if ( $i['phone_number'] ) {
							echo '<td class="column column-1 title">', esc_html($i['title']), '</td>';
							echo '<td class="column column-2 phone-number">', esc_html($i['phone_number']), '</td>';
						}else{
							echo '<td class="column column-1-2 title" colspan="2">', esc_html($i['title']), '</td>';
						}
						
						echo '</tr>';
					}
					
					if ( $i['content'] ) {
						echo '<tr>';
						echo '<td class="column column-1-2 content" colspan="2">', esc_html($i['content']), '</td>';
						echo '</tr>';
					}
					
				}
				echo '</tbody></table>';
			}
			
			if ( $country_codes ) {
				echo '<div class="country-codes"><strong>Country Codes:</strong> ', $country_codes, '</div>';
			}
			?>
			
		</div>
		
	</div>
	<?php
	}
	?>
	
	<?php
	$show_tour_overview = $tour_overview != '';
	if ( $show_tour_overview ) {
	?>
	<div class="pdf-page" id="tour-overview">
		
		<?php ah_display_bookmark( 'Tour Overview', 0 ); ?>
		
		<div class="section-heading itinerary-heading">
			<?php echo '<h1 class="pdf-title">Tour Overview</h1>'; ?>
		</div>
		
		<?php if ( $tour_overview ) { ?>
			<div class="section-content itinerary-tour-overview">
				<?php ah_display_content_columns( $tour_overview ); ?>
			</div>
		<?php } ?>
		
	</div>
	<?php
	}
	?>

</section>

<?php
if ( $villages ) {
	echo '<div id="villages"></div>';
	
	foreach( $villages as $i => $s ) {
		$village_id = (int) $s['village'];
		$hotel_id = $s['hotel'] ?? false;
		$additional_content = $s['add_text'] ? $s['content'] : '';
		$first_bookmark = ($i == 0);
		
		if ( $village_id ) include( __DIR__ . '/village.php' );
	}
}
?>

<?php
if ( $hikes ) {
	echo '<div id="hikes"></div>';
	
	foreach( $hikes as $i => $s ) {
		$post_id = (int) $s['hike'];
		$additional_content = $s['add_text'] ? $s['content'] : '';
		
		$first_bookmark = ($i == 0);
		
		if ( $post_id ) include( __DIR__ . '/hike.php' );
	}
}
?>

<?php
if ( $attached_documents ) {
	?>
	<section id="documents" class="pdf-section documents">
	
		<div class="pdf-page page-documents" id="page-documents">
			
			<?php ah_display_bookmark( 'Documents', 0 ); ?>
		
			<?php
			foreach( $attached_documents as $post_id ) {
				$document_url = ah_get_document_redirect_url( $post_id );
				$date = date('m/d/Y', strtotime( $post_id ) );
				$image_id = ah_get_document_preview_image( $post_id );
				
				$title = get_the_title($post_id);
				?>
				<div id="document-<?php echo $post_id; ?>" class="section-document document-image document-id-<?php echo $post_id; ?>">
					
					<?php ah_display_bookmark( $title, 1 ); ?>
					
					<?php ah_display_document_embed( $post_id ); ?>
					
				</div>
				<?php
			}
			?>
			
		</div>
		
	</section>
	<?php
}
?>
