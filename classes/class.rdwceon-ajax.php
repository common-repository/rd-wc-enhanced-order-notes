<?php

if ( ! defined( 'ABSPATH') ) {
	exit; // Exit if accessed directly
}

class RDWCEON_Ajax {
	public static function do_ajax() {
		if ( isset( $_REQUEST['method'] ) ) {
			$method = wp_kses( $_REQUEST['method'], array() );
			if ( method_exists( __CLASS__, $method ) ) {
				if ( check_ajax_referer( 'rdwceon-ajax-nonce' ) ) {
					call_user_func( array( __CLASS__, $method ) );
				}
			}
		}
		exit;
	}

	public static function hide_review_upgrade_notice() {
		if ( check_ajax_referer( 'rdwceon-ajax-nonce' ) ) {
			RDWCEON_Manager::update_option( 'show_review_upgrade_notice', 'no' );
			wp_send_json_success( array(), 200 );
		}

		wp_send_json_error( array(), 302 );
	}

	public static function get_order_note_template_data() {
		if ( check_ajax_referer( 'rdwceon-ajax-nonce' ) ) {
			if ( isset( $_POST['template_id'] ) ) {
				$template_id = sanitize_text_field( $_POST['template_id'] );
				$template_id = ( is_numeric( $template_id ) ) ? $template_id : 0;
				$template_header = RDWCEON_Manager::build_template_modal_header( $template_id );
				$template_content = RDWCEON_Manager::build_template_modal_content( $template_id );
				$template_exists = ( $template_id ) ? true : false;
				$response = array(
					'template_id' => $template_id,
					'template_header' => $template_header,
					'template_content' => $template_content,
					'template_exists' => $template_exists,
				);

				wp_send_json_success( $response );
			}
		}
		exit;
	}

	public static function add_template_category() {
		if ( check_ajax_referer( 'rdwceon-ajax-nonce' ) ) {
			if ( isset( $_POST['category_name'] ) ) {
				$category_name = sanitize_text_field( $_POST['category_name'] );
				if ( $category_name ) {
					$term = wp_insert_term(
						$category_name,
						'rdwceon_template_category'
					);
					if ( is_array( $term ) ) {
						wp_send_json_success( array(
							'term_id' => $term['term_id'],
							'name' => $category_name,
						) );
					}
				}
			}
			wp_send_json_error( array(
				'error' => __( 'Please enter a valid category name', 'rdwceon' ),
			) );
		}
		exit;
	}

	public static function remove_template_category() {
		if ( check_ajax_referer( 'rdwceon-ajax-nonce' ) ) {
			if ( isset( $_POST['term_id'] ) ) {
				$term_id = sanitize_text_field( $_POST['term_id'] );
				$term_id = ( is_numeric( $term_id ) ) ? $term_id : 0;
				if ( $term_id ) {
					wp_delete_term( $term_id, 'rdwceon_template_category' );
				}
				wp_send_json_success( array(
					'term_id' => $term_id,
				) );
			}
		}
		exit;
	}

	public static function save_template() {
		if ( check_ajax_referer( 'rdwceon-ajax-nonce' ) ) {
			$response = array(
				'errors' => array(),
			);
			$template_id = isset( $_POST['template_id'] ) ? sanitize_text_field( $_POST['template_id'] ) : 0;
			$template_id = ( is_numeric( $template_id ) ) ? $template_id : 0;
			$title = ( isset( $_POST['title'] ) ) ? sanitize_text_field( $_POST['title'] ) : '';
			$type = ( isset( $_POST['type'] ) ) ? sanitize_text_field( $_POST['type'] ) : '';
			$note = ( isset( $_POST['note'] ) ) ? wp_kses_post( $_POST['note'] ) : '';
			$term_id = ( isset( $_POST['term_id'] ) ) ? sanitize_text_field( $_POST['term_id'] ) : 0;
			$term_id = ( is_numeric( $term_id ) ) ? $term_id : 0;

			if ( empty( $title ) ) {
				$response['errors'][] = 'template_title';
			}
			if ( empty( $type ) ) {
				$response['errors'][] = 'template_type';
			}
			if ( empty( $note ) ) {
				$response['errors'][] = 'template_note';
			}
			if ( ! $term_id ) {
				$response['errors'][] = 'template_category';
			}

			if ( empty( $response['errors'] ) ) {
				$template_exists = get_posts( array(
					'fields' => 'ids',
					'post_type' => 'rdwceon_template',
					'post_status' => 'private',
					'posts_per_page' => 1,
					'name' => sanitize_title_with_dashes( $title ),
					'post__not_in' => array( $template_id ),
				) );
				$template_exists = ( $template_exists ) ? $template_exists : array();
				$response['template_exists'] = $template_exists;
				if ( empty( $template_exists ) ) {
					$args = array(
						'post_type' => 'rdwceon_template',
						'post_status' => 'private',
						'post_title' => $title,
						'post_name' => sanitize_title_with_dashes( $title ),
						'comment_status' => 'closed',
						'ping_status' => 'closed',
						'post_content' => $note,
					);
					if ( $template_id ) {
						$args['ID'] = $template_id;
					}
					$post_id = wp_insert_post( $args );
					if ( $post_id ) {
						wp_set_post_terms( $post_id, array( $term_id ), 'rdwceon_template_category', false );
						update_post_meta( $post_id, 'rdwceon_type', $type );
					}
				} else {
					$response['errors'][] = 'template_title';
				}
			}
			if ( empty( $response['errors'] ) ) {
				wp_send_json_success( $response );
			}
			wp_send_json_error( $response );
		}
		exit;
	}

	public static function remove_template() {
		if ( check_ajax_referer( 'rdwceon-ajax-nonce' ) ) {
			if ( isset( $_POST['template_id'] ) ) {
				$template_id = sanitize_text_field( $_POST['template_id'] );
				$template_id = ( is_numeric( $template_id ) ) ? $template_id : 0;
				if ( $template_id ) {
					$template = get_post( $template_id );
					if ( $template && 'rdwceon_template' == $template->post_type ) {
						wp_delete_post( $template_id, true );
					}
				}
				wp_send_json_success( array(
					'template_id' => $template_id,
				) );
			}
		}
		exit;
	}

	public static function build_template_list() {
		if ( check_ajax_referer( 'rdwceon-ajax-nonce' ) ) {
			$columns = apply_filters(
				'woocommerce_rdwceon_setting_columns',
				array(
					'title' => __( 'Title', 'rdwceon' ),
					'type' => __( 'Type', 'rdwceon' ),
					'category' => __( 'Category', 'rdwceon' ),
					'actions' => '',
				)
			);
			$templates = get_posts( array( 
				'post_type' => 'rdwceon_template',
				'post_status'=> 'private',
				'posts_per_page' => -1,
				'orderby' => 'title',
				'order' => 'asc',
			) );
			$templates = ( $templates ) ? $templates : array();
			foreach ( $templates as $template ) {
				$type = get_post_meta( $template->ID, 'rdwceon_type', true );
				$type_label = ( 'private' == $type ) ? __( 'Private', 'rdwceon' ) : __( 'Customer', 'rdwceon' );
				$category = get_the_terms( $template->ID, 'rdwceon_template_category' );
				$category = ( isset( $category[0] ) ) ? $category[0] : false;
				echo '<tr>';

				foreach ( $columns as $key => $column ) {
					switch ( $key ) {
						case 'title':
							echo '<td class="rdwceon-templates-table-' . esc_attr( $key ) . '">';
							echo '<a href="#" class="rdwceon-show-edit-template-modal" data-template-id="' . esc_attr( $template->ID ) . '">' . esc_html( $template->post_title ) . '</a>';
							echo '</td>';
							break;
						case 'type':
							echo '<td class="rdwceon-templates-table-' . esc_attr( $key ) . '">';
							echo esc_html( $type_label );
							echo '</td>';
							break;
						case 'category':
							echo '<td class="rdwceon-template-table-' . esc_attr( $key ) . '">';
							echo esc_html( $category->name );
							echo '</td>';
							break;
						case 'actions':
							echo '<td class="rdwceon-templates-table-' . esc_attr( $key ) . '">';
							echo '<button type="button" class="button alignright rdwceon-template-edit rdwceon-show-edit-template-modal" data-template-id="' . esc_attr( $template->ID ) . '"><span class="dashicons dashicons-edit"></span></button>';
							echo '<button type="button" class="button alignright rdwceon-template-remove" data-template-id="' . esc_attr( $template->ID ) . '"><span class="dashicons dashicons-remove"></span></button>';
							echo '</td>';
							break;
					}
				}

				echo '</tr>';
			}
		}
		exit;
	}

	public static function get_template_content() {
		if ( check_ajax_referer( 'rdwceon-ajax-nonce' ) ) {
			$content = '';
			if ( isset( $_POST['template_id'] ) ) {
				$template_id = sanitize_text_field( $_POST['template_id'] );
				$template_id = ( is_numeric( $template_id ) ) ? $template_id : 0;
				$template = get_post( $template_id );
				if ( $template ) {
					$content = $template->post_content;
				}
				echo wp_kses_post( $content );
			}
		}
		exit;
	}
}
