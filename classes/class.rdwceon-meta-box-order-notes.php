<?php
/**
 * Order Notes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RDWCEON_Meta_Box_Order_Notes {

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post|WC_Order $post Post or order object.
	 */
	public static function output( $post ) {
		if ( $post instanceof WC_Order ) {
			$order_id = $post->get_id();
		} else {
			$order_id = $post->ID;
		}

		$args = array( 'order_id' => $order_id );

		if ( 0 !== $order_id ) {
			$notes = wc_get_order_notes( $args );
		} else {
			$notes = array();
		}

		include RDWCEON_PATH . 'views/html-order-notes.php';
		?>
		<div class="add_note rdwceon-add-note">
			<p>
				<?php 
				$categories = get_terms( array(
					'taxonomy' => 'rdwceon_template_category',
					'number' => 0,
					'hide_empty' => false,
				) );
				$categories = ( $categories ) ? $categories : array();
				?>
				<label for="rdwceon_order_note_template" class="screen-reader-text"><?php esc_html_e( 'Use template', 'rdwceon' ); ?></label>
				<select name="rdwceon_order_note_template" id="rdwceon_order_note_template">
					<option value=""><?php esc_html_e( 'Add note from template', 'rdwceon' ); ?></option>
					<?php foreach ( $categories as $category ) : ?>
						<?php 
						$templates = get_posts( array( 
							'post_type' => 'rdwceon_template',
							'post_status'=> 'private',
							'posts_per_page' => -1,
							'orderby' => 'title',
							'order' => 'asc',
							'tax_query' => array(
								array(
									'taxonomy' => 'rdwceon_template_category',
									'field' => 'term_id',
									'terms' => array( $category->term_id ),
								),
							),
						) );
						$templates = ( $templates ) ? $templates : array();
						if ( ! empty( $templates ) ) : 
							?>
							<optgroup label="<?php echo esc_attr( $category->name ); ?>">
							<?php 
							foreach ( $templates as $template ) :
								$type = get_post_meta( $template->ID, 'rdwceon_type', true ); 
								?>
								<option data-type="<?php echo esc_attr( $type ); ?>" value="<?php echo esc_attr( $template->ID ); ?>"><?php echo esc_html( $template->post_title ); ?></option>
							<?php endforeach; ?>
							</optgroup>
						<?php endif; ?>
					<?php endforeach; ?>
				</select>
			</p>
			<p>
				<label for="add_order_note"><?php esc_html_e( 'Add note', 'woocommerce' ); ?> <?php echo wc_help_tip( __( 'Add a note for your reference, or add a customer note (the user will be notified).', 'woocommerce' ) ); ?></label>
				<textarea type="text" name="order_note" id="add_order_note" class="input-text" cols="20" rows="5"></textarea>
			</p>
			<p>
				<label for="order_note_type" class="screen-reader-text"><?php esc_html_e( 'Note type', 'woocommerce' ); ?></label>
				<select name="order_note_type" id="order_note_type">
					<option value=""><?php esc_html_e( 'Private note', 'woocommerce' ); ?></option>
					<option value="customer"><?php esc_html_e( 'Note to customer', 'woocommerce' ); ?></option>
				</select>
				<button type="button" class="add_note button"><?php esc_html_e( 'Add', 'woocommerce' ); ?></button>
			</p>
		</div>
		<?php
	}
}
