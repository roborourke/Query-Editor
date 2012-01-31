<?php

/*
Plugin Name: Query Editor
Description: Adds some simple options on the reading settings page to customise the main query. You can change the default post type(s), exclude terms from any taxonomy and change the ordering.
Version: 0.1
Author: Robert O'Rourke
License: GPLv2 or later
*/

add_action( 'admin_init', 'query_editor_init' );
function query_editor_init() {

    $section = 'query_editor';
    $page = 'reading';
    $save = 'save_query_editor';

    // query modifier settings section
    add_settings_section( 'query_editor', __( 'Query Editor' ), $section, $page );
	register_setting( $page, 'qe', $save );

    // post type selection
    add_settings_field( 'qe_post_types', __( 'Content types:' ), 'query_editor_post_types', $page, $section, array( 'post_types' => get_option( 'qe_post_types', array( 'post' ) ) ) );
    
    // taxonomy fields
	add_settings_field( 'qe_exclude_terms', __( 'Terms to exclude:' ), 'query_editor_exclude_terms', $page, $section, array( 'exclude_terms' => get_option( 'qe_exclude_terms', array() ) ) );
	
	// date order
	add_settings_field( 'qe_order', __( 'Order:' ), 'query_editor_order', $page, $section, get_option( 'qe_order', 'desc' ) );
	add_settings_field( 'qe_orderby', __( 'Order by:' ), 'query_editor_orderby', $page, $section, get_option( 'qe_orderby', 'date' ) );
	
	add_settings_field( 'qe_nopaging', __( 'No paging:' ), 'query_editor_nopaging', $page, $section, get_option( 'qe_nopaging', false ) );
	
	add_settings_field( 'qe_offset', __( 'Offset:' ), 'query_editor_offset', $page, $section, get_option( 'qe_offset', 0 ) );
	
	// meta
	add_settings_field( 'qe_meta_key', __( 'Meta key:' ), 'query_editor_meta_key', $page, $section, get_option( 'qe_meta_key', '' ) );
	add_settings_field( 'qe_meta_value', __( 'Meta value:' ), 'query_editor_meta_value', $page, $section, get_option( 'qe_meta_value', '' ) );
}

// main section explanation
function query_editor() { ?>
	<p class="intro">To find out more about how modifying the query works and for some examples of order by statements see the <a href="http://codex.wordpress.org/Class_Reference/WP_Query">WP_Query</a> codex page.</p>
	<?php
}

// post types selection field
function query_editor_post_types( $args ) { ?>
	<ul class="categorychecklist" style="max-height:220px;overflow:auto;margin:0 0 20px;"><?php
		$types = get_post_types( array( 'public' => true ), 'objects' );
		foreach( $types as $type ) {
			$field_id = 'post_type_' . $type->name; ?>
		<li><label for="<?php echo $field_id; ?>">
			<input id="<?php echo $field_id; ?>" type="checkbox" <?php if ( is_array( $args[ 'post_types' ] ) && in_array( $type->name, $args[ 'post_types' ] ) ) echo ' checked="checked"'; ?> name="qe_post_types[]" value="<?php echo $type->name; ?>" />
			<?php echo $type->labels->name; ?></label></li>
	<?php } ?>
	</ul>
	<?php
}

function query_editor_exclude_terms( $args ) {
	foreach( get_taxonomies( array( 'public' => true ), 'objects' ) as $taxonomy ) { ?>
		<h4 style="margin:0 0 5px;"><?php echo $taxonomy->labels->name; ?></h4>
		<ul class="categorychecklist" style="max-height:220px;overflow:auto;margin:0 0 20px;"><?php
			$terms = get_terms( $taxonomy->name, array( 'hide_empty' => false ) );
			if ( count( $terms ) )  {
			foreach( $terms as $term ) {
				$field_id = 'exclude_terms_' . $term->term_id; ?>
			<li><label for="<?php echo $field_id; ?>">
				<input id="<?php echo $field_id; ?>" type="checkbox" <?php if ( is_array( $args[ 'exclude_terms' ][ $taxonomy->name ] ) && in_array( $term->term_id, $args[ 'exclude_terms' ][ $taxonomy->name ] ) ) echo ' checked="checked"'; ?> name="qe_exclude_terms[<?php echo $taxonomy->name; ?>][]" value="<?php echo $term->term_id; ?>" />
				<?php echo $term->name; ?></label></li>
		<?php } } else { ?>
			<li><?php _e( 'None set' ); ?></li>
		<?php } ?>
		</ul>
		<?php
	}
}

function query_editor_order( $order ) { ?>
	<label for="qe_order_asc"><input <?php checked( 'asc', $order ); ?> id="qe_order_asc" type="radio" name="qe_order" value="asc" /> <?php _e( 'Ascending' ); ?></label>
	<label for="qe_order_desc"><input <?php checked( 'desc', $order ); ?> id="qe_order_desc" type="radio" name="qe_order" value="desc" /> <?php _e( 'Descending' ); ?></label>
	<?php
}

function query_editor_orderby( $orderby ) { ?>
	<select name="qe_orderby">
		<option <?php selected( 'date', $orderby ); ?> value="date"><?php _e( 'Date' ); ?></option>
		<option <?php selected( 'title', $orderby ); ?> value="title"><?php _e( 'Title' ); ?></option>
		<option <?php selected( 'rand', $orderby ); ?> value="rand"><?php _e( 'Random' ); ?></option>
		<option <?php selected( 'comment_count', $orderby ); ?> value="comment_count"><?php _e( 'Comment count' ); ?></option>
		<option <?php if ( ! preg_match( "/^(date|title|rand|comment_count)$/", $orderby ) ) selected( 'custom', 'custom' ); ?> value="custom"><?php _e( 'Custom' ); ?></option>
	</select>
	<label class="custom-orderby" for=""><?php _e( 'Custom' ); ?> <input id="custom-orderby" type="text" name="qe_orderby_custom" value="<?php esc_attr_e( $orderby ); ?>" /></label>
	<p class="description">If you set a custom value you can order by multiple fields by separating field names with a space eg: <code>meta_value ID parent</code></p>
	<?php
}

function query_editor_offset( $offset ) { ?>
	<input type="number" name="qe_offset" value="<?php esc_attr_e( $offset ); ?>" />
	<p class="description">This allows you to skip past any number of posts from the start of the query.</p>
	<?php
}

function query_editor_nopaging( $nopaging ) { ?>
	<label for="qe_nopaging"><input <?php checked( true, $nopaging ); ?> id="qe_nopaging" type="checkbox" name="qe_nopaging" value="1" /></label>
	<?php
}

function query_editor_meta_key( $meta_key ) { ?>
	<input type="text" name="qe_meta_key" value="<?php esc_attr_e( $meta_key ); ?>" />
	<p class="description">Queries all posts that have this meta key regardless of its value.</p>
	<?php
}

function query_editor_meta_value( $meta_value ) { ?>
	<input type="text" name="qe_meta_value" value="<?php esc_attr_e( $meta_value ); ?>" />
	<p class="description">Used in conjunction with meta key you can query for posts with a specific value for the meta key.</p>
	<?php
}

function save_query_editor() {
	if ( ! current_user_can( 'manage_options' ) )
		return false;

	// save post types
	get_option( 'qe_post_types'. array( 'post' ) );
	update_option( 'qe_post_types', empty( $_POST[ 'qe_post_types' ] ) ? array( 'post' ) : $_POST[ 'qe_post_types' ] );

	// save term excludes
	get_option( 'qe_exclude_terms', array() );
	update_option( 'qe_exclude_terms', $_POST[ 'qe_exclude_terms' ] );
	
	// ordering
	get_option( 'qe_order', 'desc' );
	update_option( 'qe_order', sanitize_key( $_POST[ 'qe_order' ] ) );
	
	get_option( 'qe_orderby', 'date' );
	update_option( 'qe_orderby', $_POST[ 'qe_orderby' ] == 'custom' ? sanitize_text_field( $_POST[ 'qe_orderby_custom' ] ) : sanitize_key( $_POST[ 'qe_orderby' ] ) );
	
	// offset
	get_option( 'qe_offset', 0 );
	update_option( 'qe_offset', intval( $_POST[ 'qe_offset' ] ) );
	
	// paging
	get_option( 'qe_nopaging', false );
	update_option( 'qe_nopaging', (bool)$_POST[ 'qe_nopaging' ] );
	
	// meta
	get_option( 'qe_meta_key', '' );
	update_option( 'qe_meta_key', sanitize_key( $_POST[ 'qe_meta_key' ] ) );
	
	get_option( 'qe_meta_value', '' );
	update_option( 'qe_meta_value', sanitize_text_field( $_POST[ 'qe_meta_value' ] ) );
}

// if it's the home page and the main query then run our filter
add_action( 'pre_get_posts', 'exclude_taxonomies_filter' );
function exclude_taxonomies_filter( $query ) {
	global $wp_the_query;
	if ( $query !== $wp_the_query || is_admin() )
		return $query;
	
	// post types
	if ( ! $query->is_post_type_archive() )
		$query->set( 'post_type' , get_option( 'qe_post_types', array( 'post' ) ) );
	
	// terms
	$exclude_terms = get_option( 'qe_exclude_terms', array() );
	$tax_query = array();
	foreach( get_taxonomies( array( 'public' => true ), 'objects' ) as $taxonomy )
		if ( ! empty( $exclude_terms[ $taxonomy->name ] ) ) $tax_query[] = array( 'taxonomy' => $taxonomy->name, 'field' => 'id', 'terms' => $exclude_terms[ $taxonomy->name ], 'operator' => 'NOT IN' );
	if ( !$query->is_tax() && !$query->is_tag() && !$query->is_category() )
		$query->set( 'tax_query', $tax_query );
	
	// sorting
	if ( empty( $query->query_vars[ 'order' ] ) )
		$query->set( 'order', strtoupper( get_option( 'qe_order', 'desc' ) ) );
	if ( empty( $query->query_vars[ 'orderby' ] ) )
		$query->set( 'orderby', get_option( 'qe_orderby', 'date' ) );
		
	// offset
	if ( ! $query->query_vars[ 'offset' ] )
		$query->set( 'offset', get_option( 'qe_offset', 0 ) );
		
	// paging
	if ( ! $query->query_vars[ 'nopaging' ] )
		$query->set( 'nopaging', get_option( 'qe_nopaging', false ) );
		
	// meta
	if ( empty( $query->query_vars[ 'meta_key' ] ) )
		$query->set( 'meta_key', strtoupper( get_option( 'qe_meta_key', '' ) ) );
	if ( empty( $query->query_vars[ 'meta_value' ] ) )
		$query->set( 'meta_value', strtoupper( get_option( 'qe_meta_value', '' ) ) );
	
	return $query;
}


?>
