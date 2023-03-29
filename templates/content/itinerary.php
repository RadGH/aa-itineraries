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

$phone_numbers = get_field( 'phone_numbers', get_the_ID() );

$country_codes = get_field( 'country_codes', get_the_ID() );

$tour_overview = get_field( 'tour_overview', get_the_ID() );

$villages = get_field( 'villages', get_the_ID() );

$hikes = get_field( 'hikes', get_the_ID() );

$attached_documents = get_field( 'attached_documents', get_the_ID() );
?>

<?php
if ( ah_is_pdf() ) {
	?>
	<!-- Intro -->
	<htmlpagefooter name="itinerary_intro_footer" style="display:none">
		<table class="footer-table footer-table-itinerary-intro" width="100%"><tr>
				<td width="50%"></td>
				<td width="50%" align="right"><?php ah_display_content_columns( $contact_information ); ?></td>
			</tr></table>
	</htmlpagefooter>
	
	<style>
		#page-itinerary-intro {
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
		#page-itinerary-schedule {
			page: itinerary_schedule;
		}

		@page itinerary_schedule {
			odd-footer-name: itinerary_schedule_footer;
			even-footer-name: itinerary_schedule_footer;
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
		#page-itinerary-tour-overview {
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
	
	<div class="pdf-page" id="page-itinerary-intro">
		
		<?php if ( $title && !ah_is_pdf() ) { ?>
			<div class="section-heading itinerary-heading">
				<?php echo '<h1 class="pdf-title">', $title, '</h1>'; ?>
			</div>
		<?php }else if ( $logo_id ) { ?>
			<div class="section-logo itinerary-logo">
				<?php ah_display_image( $logo_id, 250, 0 ); ?>
			</div>
		<?php } ?>
		
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
	
	<div class="pdf-page" id="page-itinerary-schedule">
		
		<div class="section-heading itinerary-heading">
			<?php if ( $title ) echo '<h1 class="pdf-title">', $title, '</h1>'; ?>
			
			<?php if ( $subtitle ) echo '<h2 class="pdf-subtitle">', $subtitle, '</h2>'; ?>
			
			<?php if ( $date_range ) echo '<h2 class="pdf-subtitle date-range">', $date_range, '</h2>'; ?>
		</div>
		
		<div class="section-sechedule">
			
			<?php
			if ( $schedule || $departure_information ) {
				
				echo '<table class="schedule-table columns-3"><tbody>';
				
				if ( $schedule ) foreach( $schedule as $i ) {
					echo '<tr>';
						echo '<td class="column column-1">', nl2br($i['column_1']), '</td>';
						echo '<td class="column column-2">', nl2br($i['column_2']), '</td>';
						echo '<td class="column column-3">', nl2br($i['column_3']), '</td>';
					echo '</tr>';
				}
				
				if ( $departure_information ) {
					echo '<tr>';
					echo '<td class="column column-1">Departure Information:</td>';
					echo '<td class="column column-2-3" colspan="2">', nl2br($departure_information), '</td>';
					echo '</tr>';
				}
				
				echo '</tbody></table>';
				
			}
			
			if ( $phone_numbers ) {
				echo '<table class="phone-number-table columns-2"><tbody>';
				foreach( $phone_numbers as $i ) {
					echo '<tr>';
					if ( $i['phone_number'] ) {
						echo '<td class="column column-1 title">', esc_html($i['title']), '</td>';
						echo '<td class="column column-2 phone_number">', esc_html($i['phone_number']), '</td>';
					}else{
						echo '<td class="column column-1-2 title" colspan="2">', esc_html($i['title']), '</td>';
					}
					echo '</tr>';
					
					echo '<tr>';
					echo '<td class="column column-1-2 content" colspan="2">', esc_html($i['content']), '</td>';
					echo '</tr>';
				}
				echo '</tbody></table>';
			}
			
			if ( $country_codes ) {
				echo '<div class="country-codes"><strong>Country Codes:</strong> ', $country_codes, '</div>';
			}
			?>
			
		</div>
		
	</div>
	
	<div class="pdf-page" id="page-itinerary-tour-overview">
		
		<div class="section-heading itinerary-heading">
			<?php echo '<h1 class="pdf-title">Tour Overview</h1>'; ?>
		</div>
		
		<div class="section-sechedule">
			
			<?php if ( $tour_overview ) { ?>
				<div class="section-content itinerary-tour-overview">
					<?php ah_display_content_columns( $tour_overview ); ?>
				</div>
			<?php } ?>
			
		</div>
		
	</div>

</section>

<?php
if ( $villages ) {
	foreach( $villages as $i => $s ) {
		$post_id = (int) $s['village'];
		$additional_content = $s['add_text'] ? $s['content'] : '';
		
		if ( $post_id ) include( __DIR__ . '/village.php' );
	}
}
?>

<?php
if ( $hikes ) {
	foreach( $hikes as $i => $s ) {
		$post_id = (int) $s['hike'];
		$additional_content = $s['add_text'] ? $s['content'] : '';
		
		if ( $post_id ) include( __DIR__ . '/hike.php' );
	}
}
?>

<?php
if ( $attached_documents ) {
	?>
	<section id="documents" class="pdf-section documents">
	
		<div class="pdf-page page-documents" id="page-documents">
		
			<?php
			foreach( $attached_documents as $post_id ) {
				$document_url = ah_get_document_redirect_url( $post_id );
				$date = date('m/d/Y', strtotime( $post_id ) );
				$image_id = ah_get_document_preview_image( $post_id );
				
				$title = get_the_title($post_id);
				$type = get_field( 'type', $post_id );
				$url = get_field( 'url', $post_id );
				
				?>
				<div class="section-document document-image document-id-<?php echo $post_id; ?>">
					
					<?php
					if ( $type == 'url' ) {
						if ( $title ) echo '<h4 class="document-title">', $title, '</h1>';
						
						?>
						<p><a href="<?php echo esc_attr($url); ?>"><?php echo $url; ?></a></p>
						<?php
					}
					
					if ( $type == 'file' ) {
						$attachment_id = (int) get_field( 'file', $post_id, false );
						$url = wp_get_attachment_link( $attachment_id, 'document-embed' );
						?>
						<p><a href="<?php echo esc_attr($url); ?>"><?php echo $url; ?></a></p>
						<?php
					}
					?>
					
				</div>
				<?php
			}
			?>
			
		</div>
		
	</section>
	<?php
}
?>