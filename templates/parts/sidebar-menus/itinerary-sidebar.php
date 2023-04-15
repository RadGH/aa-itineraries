<?php
if ( !function_exists('_recurse_ah_itinerary_nav') ) {
	function _recurse_ah_itinerary_nav( $list, $depth = 0 ) {
		foreach( $list as $l ) {
			
			$classes = array( 'menu-item' );
			if ( !empty($l['children']) ) $classes[] = 'menu-item-has-children';
			
			echo '<li class="'. esc_attr(implode(' ', $classes)) .'">';
			echo '<a href="'. esc_attr($l['link']) .'">'. esc_html($l['title']) .'</a>';
			
			if ( !empty($l['children']) ) {
				echo '<ul class="sub-menu depth-'. esc_attr($depth) .'">';
				
				_recurse_ah_itinerary_nav( $l['children'], $depth + 1 );
				
				echo '</ul>';
			}
			
			echo '</li>';
			
		}
	}
}

?>
<ul id="menu-itinerary" class="ah-account-menu">
	
	<li><a href="/account/">My Account</a></li>
	
	<?php
	$toc = AH_Itinerary()->get_table_of_contents( get_the_ID() );
	
	if ( $toc ) {
		
		echo '<li class="separator"></li>';
		
		_recurse_ah_itinerary_nav( $toc );
	}
	?>
	
</ul>