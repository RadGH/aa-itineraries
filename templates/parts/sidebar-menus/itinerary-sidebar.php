<ul id="menu-itinerary" class="ah-account-menu">
	
	<li><a href="/account/">My Account</a></li>
	
	<?php
	if ( isset($itinerary_settings) ) {
		?>
		
		<li class="separator"></li>
		
		<?php
		AH_Itinerary()->display_table_of_contents( $itinerary_settings['pages'] );
	}
	?>
	
</ul>