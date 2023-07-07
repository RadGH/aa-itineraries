<?php
// Itinerary content
// Used when viewing an itinerary, or when download a pdf

if ( ! isset($itinerary_settings) ) {
	echo 'Error: $itinerary_settings does not exist in ' . __FILE__ . ':' . __LINE__;
	return;
}

/**
 * For structure @see Class_Itinerary_Post_Type::get_itinerary_settings()
 */
$data = $itinerary_settings['data'];
$pages = $itinerary_settings['pages'];

$slug = $data['slug'];
$logo_id = $data['logo_id'];

$title = $data['title'];
$subtitle = $data['subtitle'];
$introduction_message = $data['introduction_message'];
$contact_information = $data['contact_information'];
$date_range = $data['date_range'];

$schedule = $data['schedule'];

$departure_information = $data['departure_information'];

$phone_numbers = $data['phone_numbers'];

$country_codes = $data['country_codes'];

$tour_overview = $data['tour_overview'];

$hike_summary = $data['hike_summary'];

$villages = $data['villages'];

$hikes = $data['hikes'];

$documents = $data['documents'];

// Check which sections will be displayed
$show_intro_page = $pages['introduction']['enabled'];
$show_schedule_page = $pages['schedule']['enabled'];
$show_directory_page = $pages['directory']['enabled'];
$show_tour_overview = $pages['tour_overview']['enabled'];
$show_hike_summary = $pages['hike_summary']['enabled'];
$show_villages = $pages['villages']['enabled'];
$show_hikes = $pages['hikes']['enabled'];
$show_documents = $pages['documents']['enabled'];

if ( ! ah_is_pdf() ) {
	// Web page view: Show pdf download button
	?>
	<div class="ah-pdf-download-button">
		<a href="<?php echo get_permalink(); ?>/download/" target="download_<?php the_ID(); ?>" class="button">Download as PDF</a>
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
				<td width="50%" align="right"><?php echo wpautop( $contact_information ); ?></td>
			</tr></table>
	</htmlpagefooter>
	
	<style>
		.footer-table-itinerary-intro {
			font-weight: bold;
			color: #204f66;
		}
		
		#<?php echo $pages['introduction']['id']; ?> {
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
		#<?php echo $pages['schedule']['id']; ?> {
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
		#<?php echo $pages['directory']['id']; ?> {
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
		#<?php echo $pages['tour_overview']['id']; ?> {
			page: itinerary_tour_overview;
		}
		
		@page itinerary_tour_overview {
			odd-footer-name: itinerary_tour_overview_footer;
			even-footer-name: itinerary_tour_overview_footer;
		}
	</style>
	
	
	<!-- Hike Summary -->
	<htmlpagefooter name="itinerary_hike_summary_footer" style="display:none">
		<table class="footer-table" width="100%"><tr>
			<td width="50%">Hike Summary</td>
			<td width="50%" align="right">{PAGENO}</td>
		</tr></table>
	</htmlpagefooter>
	
	<style>
		#<?php echo $pages['hike_summary']['id']; ?> {
			page: itinerary_hike_summary;
		}
		
		@page itinerary_hike_summary {
			odd-footer-name: itinerary_hike_summary_footer;
			even-footer-name: itinerary_hike_summary_footer;
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
	
	if ( $show_intro_page ) {
	?>
	<div class="pdf-page" id="<?php echo $pages['introduction']['id']; ?>">
		
		<?php ah_display_bookmark( $pages['introduction']['title'], 0 ); ?>
		
		<?php if ( ah_is_pdf() && $logo_id ) { ?>
			<div class="section-logo itinerary-logo">
				<a href="<?php echo site_url('/'); ?>"><?php ah_display_image( $logo_id, 250, 0 ); ?></a>
			</div>
		<?php } ?>
		
		<?php if ( $introduction_message ) { ?>
			<div class="section-content itinerary-summary">
				<?php echo wpautop( $introduction_message ); ?>
			</div>
		<?php } ?>
		
		<?php // On PDF, this moved to footer. See above htmlpagefooter
		if ( (! ah_is_pdf() || ah_is_pdf_preview()) && $contact_information ) { ?>
			<div class="section-content itinerary-contact">
				<?php echo wpautop( $contact_information ); ?>
			</div>
		<?php } ?>
	
	</div>
	<?php } ?>
	
	<?php
	if ( $show_schedule_page ) {
	?>
	<div class="pdf-page" id="<?php echo $pages['schedule']['id']; ?>">
		
		<?php ah_display_bookmark( $pages['schedule']['title'], 0 ); ?>
		
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
				$col_3 = $i['column_3'];
				
				if ( $col_3 && ah_is_phone_number( $col_3 ) ) {
					$col_3 = ah_get_phone_number_link( $col_3 );
				}
				
				echo '<tr class="schedule">';
					echo '<td class="column column-1">', nl2br($i['column_1']), '</td>';
					echo '<td class="column column-2">', nl2br($i['column_2']), '</td>';
					echo '<td class="column column-3">', nl2br($col_3), '</td>';
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
	if ( $show_directory_page ) {
	?>
	<div class="pdf-page" id="<?php echo $pages['directory']['id']; ?>">
		
		<?php ah_display_bookmark( $pages['directory']['title'], 0 ); ?>
		
		<div class="section-heading itinerary-heading">
			<h1 class="pdf-title"><?php echo $pages['directory']['title']; ?></h1>
		</div>
		
		<div class="section-directory">
			
			<?php
			if ( $phone_numbers ) {
				echo '<table class="directory-table columns-2"><tbody>';
				foreach( $phone_numbers as $i ) {
					
					$phone_number_display = ah_get_phone_number_link( $i['phone_number'] );
					
					if ( $i['title'] || $phone_number_display ) {
						echo '<tr>';
						
						if ( $phone_number_display ) {
							echo '<td class="column column-1 title">', esc_html($i['title']), '</td>';
							echo '<td class="column column-2 phone-number">', $phone_number_display, '</td>';
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
	if ( $show_tour_overview ) {
	?>
	<div class="pdf-page" id="<?php echo $pages['tour_overview']['id']; ?>">
		
		<?php ah_display_bookmark( $pages['tour_overview']['title'], 0 ); ?>
		
		<div class="section-heading itinerary-heading">
			<?php echo '<h1 class="pdf-title">Tour Overview</h1>'; ?>
		</div>
		
		<?php if ( $tour_overview ) { ?>
			<div class="section-content itinerary-tour-overview">
				<?php echo wpautop( $tour_overview ); ?>
			</div>
		<?php } ?>
		
	</div>
	<?php
	}
	?>
	
	<?php
	if ( $show_hike_summary ) {
	?>
	<div class="pdf-page" id="<?php echo $pages['hike_summary']['id']; ?>">
		
		<?php ah_display_bookmark( $pages['hike_summary']['title'], 0 ); ?>
		
		<div class="section-heading itinerary-heading">
			<?php echo '<h1 class="pdf-title">'. $pages['hike_summary']['title'] .'</h1>'; ?>
		</div>
		
		<?php if ( $hike_summary ) { ?>
			<div class="section-content itinerary-hike-summary">
				<?php echo $hike_summary; ?>
			</div>
		<?php } ?>
		
	</div>
	<?php
	}
	?>

</section>

<?php
if ( $show_villages ) {
	echo '<div id="'. $pages['villages']['id']  .'">';
	
	foreach( $villages as $i => $s ) {
		$village_id = (int) $s['village'];
		if ( ! AH_Village()->is_valid($village_id) ) continue;
		
		$hotel_id = $s['hotel'] ?? false;
		$additional_content = $s['add_text'] ? $s['content'] : '';
		$first_bookmark = ($i == 0);
		
		$subpage = $pages['villages']['children'][ $village_id ] ?? false;
		$bookmark_title = $pages['villages']['title'];
		$html_id = $subpage ? $subpage['id'] : ('village-' . $village_id);
		
		if ( $village_id ) include( __DIR__ . '/village.php' );
	}
	
	echo '</div>';
}
?>

<?php
if ( $show_hikes ) {
	echo '<div id="'. $pages['hikes']['id']  .'">';
	
	foreach( $hikes as $i => $s ) {
		$post_id = (int) $s['hike'];
		if ( ! AH_Hike()->is_valid( $post_id ) ) continue;
		
		$additional_content = $s['add_text'] ? $s['content'] : '';
		
		$first_bookmark = ($i == 0);
		
		$subpage = $pages['hikes']['children'][ $post_id ] ?? false;
		$bookmark_title = $pages['hikes']['title'];
		$html_id = $subpage ? $subpage['id'] : ('hike-' . $post_id);
		
		if ( $post_id ) include( __DIR__ . '/hike.php' );
	}
	
	echo '</div>';
}
?>

<?php
if ( $show_documents ) {
	?>
	<section id="<?php echo $pages['documents']['id']; ?>" class="pdf-section documents">
	
		<div class="pdf-page page-documents" id="page-documents">
			
			<?php ah_display_bookmark( $pages['documents']['title'], 0 ); ?>
			
			<div class="section-heading documents-heading">
				<h1 class="pdf-title"><?php echo $pages['documents']['title']; ?></h1>
			</div>
			
			<?php
			foreach( $documents as $d ) {
				/**
				 * @see Class_Itinerary_Post_Type::get_itinerary_settings()
				 */
				$title = $d['title'];
				$html_id = 'document-' . $d['slug'];
				$image_id = $d['image_id'];
				$url = $d['url'];
				$text = $d['text'];
				
				?>
				<div id="<?php echo $html_id; ?>" class="section-document">
					
					<?php ah_display_bookmark( $title, 1 ); ?>
					
					<?php
					if ( $title ) {
						?>
						<h2 class="document-title"><?php echo esc_html($title); ?></h2>
						<?php
					}
					
					if ( $text ) {
						?>
						<div class="document-content"><?php echo wpautop($text); ?></div>
						<?php
					}
					
					if ( $url ) echo '<a href="', esc_attr($url), '">';
					
					$img = wp_get_attachment_image( $image_id, 'document-embed' );
					$img = ah_sanitize_mpdf_img( $img );
					echo $img;
					
					if ( $url ) echo '</a>';
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
