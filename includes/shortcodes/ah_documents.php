<?php

function shortcode_ah_documents( $atts, $content = '', $shortcode_name = 'ah_documents' ) {
	$atts = shortcode_atts(array(
	), $atts, $shortcode_name);
	
	$args = array();
	
	$current_category_slug = isset($_GET['category']) ? stripslashes($_GET['category']) : false;
	$current_category = $current_category_slug ? get_term_by( 'slug', $current_category_slug, 'ah_document_category' ) : false;
	
	if ( $current_category ) {
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'ah_document_category',
				'field' => 'term_id',
				'terms' => $current_category->term_id
			),
		);
	}else{
		$current_category_slug = false;
	}
	
	$documents = AH_Document()->get_user_documents( null, $args );

	if ( ! $documents->have_posts() ) {
		return 'You currently have no documents.';
	}
	
	ob_start();
	?>
<div class="ah-documents">
	
	<?php
	if ( $current_category ) {
		echo '<h2 class="category-subtitle">', esc_html($current_category->name), '</h2>';
	}
	?>
	
	<table class="ah-table ah-table-responsive ah-document-table" cellspacing="0">
		<thead>
			<tr>
				<th class="col col-name">Name</th>
				<th class="col col-preview">&nbsp;</th>
				<th class="col col-date">Date</th>
			</tr>
		</thead>
		
		<tbody>
			<?php
			foreach( $documents->posts as $post ) {
				
				// Link directly to file:
				$document_url = ah_get_document_redirect_url( $post->ID );
				
				// Link to page:
				// $document_url = get_permalink( $post );
				
				$name = $post->post_title;
				$date = date('m/d/Y', strtotime( $post->post_date ) );
				
				$terms = get_the_terms( $post, 'ah_document_category' );
				
				$image_id = ah_get_document_preview_image( $post->ID );
				
				$classes = array('ah-document-item');
				?>
				<tr class="<?php echo esc_attr(implode(' ', $classes)); ?>">
					<td class="col col-name" data-mobile-label="Name"><?php
						
						echo '<div class="document-title">';
						echo '<a href="'. esc_attr($document_url) .'">';
						echo esc_html( $name );
						echo '</a>';
						echo '</div>';
						
						if ( $terms && ! $current_category ) {
							echo '<div class="category-name">';
							echo '<strong>'. _n('Category', 'Categories', count($terms)) .':</strong> ';
							echo esc_html( implode(', ', wp_list_pluck( $terms, 'name') ) );
							echo '</div>';
						}
						
					?></td>
					
					<td class="col col-preview"><?php
						if ( $image_id ) {
							echo '<a href="'. esc_attr($document_url) .'">';
							echo wp_get_attachment_image( $image_id, 'document-preview' );
							echo '</a>';
						}
					?></td>
					
					<td class="col col-date" data-mobile-label="Date"><?php echo $date; ?></td>
					
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>
	
</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'ah_documents', 'shortcode_ah_documents' );