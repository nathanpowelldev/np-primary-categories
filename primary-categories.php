<?php
/*
Plugin Name:	NP Primary Categories
Plugin URI:		https://github.com/nathanpowelldev/np-primary-categories
Description:	Adds ability to assign a primary category to posts.
Version:		1.0
Author:			Nathan Powell
Author URI:		https://github.com/nathanpowelldev
Text Domain:	np-primary-category
License: 		GPL2

NP Primary Categories is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

NP Primary Categories is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with NP Primary Categories. If not, see http://www.gnu.org/licenses/gpl.html.
*/

new NP_Primary_Categories();

class NP_Primary_Categories {

	public $meta_key = "np_primary_category_id";
	public $capability = "assign_categories";

	function __construct() {

		add_action( 'init', array( $this, 'register_post_meta' ) );

		if ( is_admin() ) {
			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes') );
			add_action( 'save_post', array( $this, 'save_post_meta' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
			add_action( 'wp_ajax_primary_category_selector', array( $this, 'primary_category_selector' ) );
		}
	}

	/**
	 * Save Meta
	 * @param $post_id
	 */
	public function save_post_meta( $post_id ) {
		if ( isset( $_POST[ $this->meta_key ] ) ) {
			update_post_meta( $post_id, $this->meta_key, $_POST[ $this->meta_key ] );
		}
	}

	/**
	 * Register Meta
	 */
	public function register_post_meta() {
		$args = array(
			'sanitize_callback' => 'absint',
			'auth_callback'	=> array( $this, 'auth_callback' ),
			'type' => 'int',
			'description' => 'Primary Category',
			'single' => true,
			'show_in_rest' => true,
		);

		register_post_meta( 'post', $this->meta_key, $args );
	}

	/**
	 * Allowed to save meta
	 *
	 * @param $allowed
	 *
	 * @return bool
	 */
	public function auth_callback( $allowed ) {
		if ( ! current_user_can( $this->capability ) ) {
			$allowed = false;
		}

		return $allowed;
	}

	/**
	 * Add Meta Box
	 */
	public function add_meta_boxes(){
		if( current_user_can( $this->capability ) ) {
			add_meta_box(
				'np-primary-category',
				__( 'Primary Category', 'np-primary-category' ),
				array( $this, 'primary_category_metabox' ),
				'post',
				'side',
				'high' );
		}
	}

	/**
	 * The Meta Box
	 *
	 * @param $post
	 * @param $box
	 */
	public function primary_category_metabox( $post, $box ){

		wp_enqueue_script( 'np_primary_categories' );
		wp_nonce_field( 'np_cp_selector_nonce', 'np_cp_selector_nonce' );

		$value = get_post_meta( $post->ID, $this->meta_key, true );

		$out = '<label for="' . $this->meta_key .'">' . __( 'Primary Category', 'np-primary-category' );
		$out .= '<br>';
		$out .= "<select id='{$this->meta_key}' name='{$this->meta_key}'>";
		$out .= $this->primary_category_select_options( $value );
		$out .= '</label>';
		$out .= '</select>';

		np_get_primary_category_term( $post->ID );
		echo $out;
	}

	/**
	 * Build select options
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	public function primary_category_select_options( $value = '', $html = '' ) {

		$choices = "";
		$terms = get_terms( array(
				'taxonomy' => 'category',
				'hide_empty' => false
			)
		);

		foreach ( $terms as $category_term ) {
			$category_terms[ $category_term->term_id ] = $category_term->name;
		}

		foreach ( $terms as $term ) {
			$selected = selected( $value, $term->term_id, false );
			$choices .= "<option {$selected} value='{$term->term_id}'>{$term->name}</option>";
		}

		return $choices;
	}

	/**
	 * Ajax hack for selector
	 * Need to make sure we get newly inserted categories
	 */
	public function primary_category_selector() {
		check_ajax_referer( 'np_cp_selector_nonce', 'security' );
		if ( isset( $_POST[ 'value' ] ) ) {
			$html = $this->primary_category_select_options( $_POST[ 'value' ] );
			wp_send_json( $html );
		}
	}

	/**
	 * Javascript for selector hack
	 */
	public function admin_enqueue_scripts() {
		wp_register_script(
			'np_primary_categories',
			plugins_url( 'np-primary-categories/primary-categories.js' ),
			array( 'jquery' ),
			false,
			true
		);
	}

}

/**
 * Get primary category term if matches anything
 *
 * @param string $post_id
 *
 * @return bool|null|WP_Error|WP_Term
 */
function np_get_primary_category_term( $post_id = "" ) {
	if ( empty( $post_id ) ) {
		$post_id = get_queried_object_id();
	}

	$term_id = get_post_meta( $post_id, "np_primary_category_id", true );
	$post_categories = wp_get_post_categories( $post_id );

	if ( in_array( $term_id, $post_categories ) ) {
		return get_term( $term_id );
	}

	return false;
}

/**
 * Output term object on front end
 */
add_filter( 'the_content', function( $content ) {

	if ( $term =  np_get_primary_category_term() ) {

		$out = '<code><pre>';
		$out .= print_r( $term, true );
		$out .= '</pre></code>';

		$content = $out . $content;
	}

	return $content;
});