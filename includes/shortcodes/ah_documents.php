<?php

function shortcode_ah_documents( $atts, $content = '', $shortcode_name = 'ah_documents' ) {
	$atts = shortcode_atts(array(
	), $atts, $shortcode_name);
	
	$documents = AH_Document()->get_user_documents();

	if ( ! $documents->have_posts() ) {
		return 'You currently have no documents.';
	}
	
	ob_start();
	?>
<div class="ah-documents">
	
	<table class="ah-table ah-table-responsive ah-document-table" cellspacing="0">
		<thead>
			<tr>
				<th class="col col-id">ID</th>
				<th class="col col-name">Name</th>
				<th class="col col-preview">&nbsp;</th>
				<th class="col col-date">Date</th>
				<th class="col col-actions">Actions</th>
			</tr>
		</thead>
		
		<tbody>
			<?php
			foreach( $documents->posts as $post ) {
				
				// Link directly to file:
				// $document_url = ah_get_document_redirect_url( $post->ID );
				
				// Link to page first:
				$document_url = get_permalink( $post );
				
				$name = $post->post_title;
				$date = date('m/d/Y', strtotime( $post->post_date ) );
				
				$terms = get_the_terms( $post, 'ah_document_category' );
				
				$image_id = ah_get_document_preview_image( $post->ID );
				
				$classes = array('ah-document-item');
				?>
				<tr class="<?php echo esc_attr(implode(' ', $classes)); ?>">
					<td class="col col-id" data-mobile-label="ID"><?php
						printf(
							'<a href="%s">%s</a>',
							esc_attr( $document_url ),
							esc_html( $post->ID )
						);
					?></td>
					<td class="col col-name" data-mobile-label="Name"><?php
						echo '<div class="document-title">', $name, '</div>';
						if ( $terms ) {
							echo '<div class="category-name">';
							echo '<strong>'. _n('Category', 'Categories', count($terms)) .':</strong> ';
							echo esc_html( implode(', ', wp_list_pluck( $terms, 'name') ) );
							echo '</div>';
						}
					?></td>
					<td class="col col-preview"><?php
						if ( $image_id ) {
							echo wp_get_attachment_image( $image_id, 'document-preview' );
						}
					?></td>
					<td class="col col-date" data-mobile-label="Date"><?php echo $date; ?></td>
					<td class="col col-actions"><?php
						
						// Action: View
						printf(
							'<a href="%s" class="button button-primary ah-button">%s</a>',
							esc_attr( $document_url ),
							esc_html( 'View' )
						);
						
					?></td>
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