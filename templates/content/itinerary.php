<?php
// Itinerary content
// Used when viewing an itinerary, or when download a pdf

$title = get_field( 'title', get_the_ID() );
$subtitle = get_field( 'subtitle', get_the_ID() );
$date_range = get_field( 'date_range', get_the_ID() );
$introduction_message = get_field( 'introduction_message', get_the_ID() );
$contact_information = get_field( 'contact_information', get_the_ID() );

$schedule = get_field( 'schedule', get_the_ID() );

$departure_information = get_field( 'departure_information', get_the_ID() );

$phone_numbers = get_field( 'phone_numbers', get_the_ID() );

$country_codes = get_field( 'country_codes', get_the_ID() );

$tour_overview = get_field( 'tour_overview', get_the_ID() );

$villages = get_field( 'villages', get_the_ID() );

$hikes = get_field( 'hikes', get_the_ID() );


if ( $title ) echo '<h1>', $title, '</h1>';
if ( $subtitle ) echo '<h2>', $subtitle, '</h2>';
if ( $date_range ) echo '<div>', $date_range, '</div>';
if ( $introduction_message ) echo '<div>', $introduction_message, '</div>';
if ( $contact_information ) echo '<div>', $contact_information, '</div>';

if ( $schedule ) {
	echo '<div>';
	
	foreach( $schedule as $i ) {
		echo '<div class="column column-1">', $i['column_1'], '</div>';
		echo '<div class="column column-2">', $i['column_2'], '</div>';
		echo '<div class="column column-3">', $i['column_3'], '</div>';
	}
	
	echo '</div>';
}

if ( $departure_information ) echo '<div>', $departure_information, '</div>';

if ( $phone_numbers ) {
	echo '<div>';
	
	foreach( $phone_numbers as $i ) {
		echo '<div class="title">', $i['title'], '</div>';
		echo '<div class="phone_number">', $i['phone_number'], '</div>';
		echo '<div class="content">', $i['content'], '</div>';
	}
	
	echo '</div>';
}

if ( $country_codes ) echo '<div>', $country_codes, '</div>';

if ( $tour_overview ) echo '<div>', $tour_overview, '</div>';

if ( $villages ) {
	echo '<div>';
	
	foreach( $villages as $i ) {
		echo '<div class="village">', $i['village'], '</div>';
		echo '<div class="add_text">', print_r( $i['add_text'], true ), '</div>';
		echo '<div class="content">', $i['content'], '</div>';
	}
	
	echo '</div>';
}

if ( $hikes ) {
	echo '<div>';
	
	foreach( $hikes as $i ) {
		echo '<div class="hike">', $i['hike'], '</div>';
		echo '<div class="add_text">', print_r( $i['add_text'], true ), '</div>';
		echo '<div class="content">', $i['content'], '</div>';
	}
	
	echo '</div>';
}

