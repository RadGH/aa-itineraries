<?php
// Flex:
while( have_rows('flex_sections') ): the_row();
	get_template_part( '_templates/flex-page-rows' );
endwhile;

// Page:
// get_template_part( '_template-parts/loop', get_post_type() );