<?php
// Itinerary content
// Used when viewing an itinerary, or when download a pdf

$slug = get_post_field( 'post_name', get_the_ID() );

$logo_id = get_field( 'logo', 'ah_settings' );

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
?>

<section id="itinerary-<?php echo esc_attr($slug); ?>" class="pdf-section itinerary itinerary-<?php echo esc_attr($slug); ?> itinerary-id-<?php the_ID(); ?>">
	
	<div class="pdf-page" id="page-itinerary-<?php echo esc_attr($slug); ?>-intro">
		
		<?php if ( $title && !ah_is_pdf() ) { ?>
			<div class="section-heading itinerary-heading">
				<?php echo '<h1 class="pdf-title">', $title, '</h1>'; ?>
			</div>
		<?php } ?>
		
		<?php if ( ah_is_pdf() && $logo_id ) { ?>
			<div class="section-logo itinerary-logo">
				<?php ah_display_image( $logo_id, 250, 0 ); ?>
			</div>
		<?php } ?>
		
		<?php if ( $introduction_message ) { ?>
			<div class="section-content itinerary-summary">
				<?php ah_display_content_columns( $introduction_message ); ?>
			</div>
		<?php } ?>
		
		<?php if ( $contact_information ) { ?>
			<div class="section-content itinerary-contact">
				<?php ah_display_content_columns( $contact_information ); ?>
			</div>
		<?php } ?>
	
	</div>
	
	<div class="page-break"></div>
	
	<div class="pdf-page" id="page-itinerary-<?php echo esc_attr($slug); ?>-schedule">
		
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
	
	<div class="page-break"></div>

	
	<div class="pdf-page" id="page-itinerary-<?php echo esc_attr($slug); ?>-tour-overview">
		
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
		if ( $i > 0 ) echo '<div class="page-break"></div>';
		
		$post_id = (int) $s['village'];
		$additional_content = $s['add_text'] ? $s['content'] : '';
		
		if ( $post_id ) include( __DIR__ . '/village.php' );
	}
}

if ( $hikes ) {
	foreach( $hikes as $i => $s ) {
		if ( $i > 0 ) echo '<div class="page-break"></div>';
		
		$post_id = (int) $s['hike'];
		$additional_content = $s['add_text'] ? $s['content'] : '';
		
		if ( $post_id ) include( __DIR__ . '/hike.php' );
	}
}