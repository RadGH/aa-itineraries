<?php

if ( ! function_exists( 'ah_toc_item' ) ) {
	function ah_add_toc_item( &$list, $title, $link, &$new_item = array() ) {
		$new_item = array(
			'title' => $title,
			'link' => $link,
			'children' => array(),
		);
		
		$list[] = &$new_item;
	}
}

$toc_list = array();

// Itinerary
ah_add_toc_item( $toc_list, 'Introduction', '#intro' );

ah_add_toc_item( $toc_list, 'Schedule', '#schedule' );

ah_add_toc_item( $toc_list, 'Directory', '#directory' );

ah_add_toc_item( $toc_list, 'Tour Overview', '#tour-overview' );


// Villages
$villages = get_field( 'villages', get_the_ID() );

if ( $villages ) {
	ah_add_toc_item( $toc_list, 'Villages', '#villages', $village_list );
	
	foreach( $villages as $i => $s ) {
		$post_id = (int) $s['village'];
		$title = get_the_title( $post_id );
		$slug = get_post_field( 'post_name', $post_id );
		$link = '#village-' . esc_attr($slug);
		$child = ah_add_toc_item( $village_list['children'], $title, $link );
	}
}


// Hikes
$hikes = get_field( 'hikes', get_the_ID() );

if ( $hikes ) {
	ah_add_toc_item( $toc_list, 'Hikes', '#hikes', $hike_list );
	
	foreach( $hikes as $i => $s ) {
		$post_id = (int) $s['hike'];
		$title = get_the_title( $post_id );
		$slug = get_post_field( 'post_name', $post_id );
		$link = '#hike-' . esc_attr($slug);
		$child = ah_add_toc_item( $hike_list['children'], $title, $link );
	}
}


// Documents
$attached_documents = get_field( 'attached_documents', get_the_ID() );

if ( $attached_documents ) {
	ah_add_toc_item( $toc_list, 'Documents', '#documents', $document_list );
	
	foreach( $attached_documents as $post_id ) {
		$title = get_the_title( $post_id );
		$link = '#document-' . $post_id;
		ah_add_toc_item( $document_list['children'], $title, $link );
	}
}

return $toc_list;

pre_dump( $toc_list );


