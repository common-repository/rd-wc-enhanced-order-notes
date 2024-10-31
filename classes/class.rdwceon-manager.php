<?php

if ( ! defined( 'ABSPATH') ) {
	exit; // Exit if accessed directly
}

class RDWCEON_Manager {
	public static function get_premium_version_url() {
		return 'https://www.robotdwarf.com/woocommerce-plugins/enhanced-order-notes/';
	}

	public static function get_newsletter_signup_url() {
		return 'https://mailchi.mp/1585d7bfa373/rd-wc-enhanced-order-notes-signup';
	}

	public static function load( $plugin_file_path ) {
		self::add_actions();
		self::add_filters();
	}

	public static function activate() {
		register_uninstall_hook( RDWCEON_PLUGIN_FILE, array( __CLASS__, 'uninstall' ) );
	}

	public static function uninstall() {
		$options = self::get_options();
		if ( 'yes' == $options['delete_templates_on_uninstall'] ) {
			self::delete_template_categories();
			self::delete_templates();
		}
		delete_option( 'rdwceon_options' );
	}

	public static function delete_template_categories() {
		$categories = get_terms( array(
			'taxonomy' => 'rdwceon_template_category',
			'number' => 0,
			'hide_empty' => false,
		) );
		$categories = ( $categories ) ? $categories : array();
		foreach ( $categories as $category ) {
			wp_delete_term( $category->term_id, 'rdwceon_template_category' );
		}
	}

	public static function delete_templates() {
		$templates = get_posts( array( 
			'post_type' => 'rdwceon_template',
			'post_status'=> 'private',
			'posts_per_page' => -1,
		) );
		$templates = ( $templates ) ? $templates : array();
		foreach ( $templates as $template ) {
			if ( 'rdwceon_template' ==  $template->post_type ) {
				wp_delete_post( $template->ID, true );
			}
		}
	}

	public static function get_options() {
		$options = json_decode( get_option( 'rdwceon_options' ), true );
		$options = ( $options ) ? $options : array();

		$defaults = array(
			'delete_templates_on_uninstall' => 'no',
			'show_review_upgrade_notice' => 'yes',
		);

		return array_replace_recursive( $defaults, $options );
	}

	public static function update_options( $updated_options ) {
		$current_options = self::get_options();
		$options = array_replace_recursive( $current_options, $updated_options );
		update_option( 'rdwceon_options', wp_json_encode( $options ) );
	}

	public static function get_option( $key ) {
		$options = self::get_options();
		return ( isset( $options[$key] ) ) ? $options[$key] : false;
	}

	public static function update_option( $key, $value ) {
		$options = self::get_options();
		$options[$key] = $value;
		self::update_options($options);
	}

	public static function add_actions() {
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_notices', array( __CLASS__, 'admin_notices') );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
		add_action( 'wp_ajax_rdwceon_do_ajax', array( 'RDWCEON_Ajax', 'do_ajax' ) );
		add_action( 'add_meta_boxes_shop_order', array( __CLASS__, 'add_meta_boxes_shop_order' ), 11 );
		add_action( 'add_meta_boxes_woocommerce_page_wc-orders', array( __CLASS__, 'add_meta_boxes_shop_order' ), 11 );
		add_action( 'before_woocommerce_init', array( __CLASS__, 'before_woocommerce_init' ) );
	}

	public static function add_filters() {
		add_filter( 'plugin_action_links_' . plugin_basename( RDWCEON_PLUGIN_FILE ), array( __CLASS__, 'plugin_action_links' ) );
	}

	public static function admin_enqueue_scripts() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		wp_enqueue_style( 'rdwceon-admin-menu', RDWCEON_URL . 'css/admin-menu.css', array( 'woocommerce_admin_styles' ), RDWCEON_VERSION );
		if ( 'robot-dwarf_page_rdwceon-settings' == $screen_id 
			|| 'woocommerce_page_wc-orders' == $screen_id 
			|| 'shop_order' == $screen_id ) {
			wp_enqueue_style( 'rdwceon-admin', RDWCEON_URL . 'css/admin.css', array( 'woocommerce_admin_styles' ), RDWCEON_VERSION );
			wp_enqueue_script( 'wc-backbone-modal' );
			wp_enqueue_script( 'jquery-blockui' );
			wp_enqueue_script( 'rdwceon-admin', RDWCEON_URL . 'js/admin.js', array( 'jquery-blockui' ), RDWCEON_VERSION, false );
			wp_localize_script(
				'rdwceon-admin', 'RDWCEONSettings', array(
					'options' => self::get_options(),
					'nonces' => array(
						'ajax_nonce' => wp_create_nonce( 'rdwceon-ajax-nonce' ),
						'add_order_note_nonce' => wp_create_nonce( 'add-order-note' ),
						'delete_order_note_nonce' => wp_create_nonce( 'delete-order-note' ),
					),
					'i18n' => self::build_i18n(),
				)
			);
		}
	}

	public static function admin_init() {
		self::register_order_note_template_category_taxonomy();
		self::register_order_note_template_post_type();
		load_plugin_textdomain( 'rdwceon', false, plugin_basename( dirname( RDWCEON_PLUGIN_FILE ) ) . '/languages' );
	
		register_setting( 'rdwceon', 'rdwceon_options' );
		add_settings_section(
			'rdwceon_section_admin_orders',
			__( 'RD Order Note Templates for WooCommerce', 'rdwceon' ),
			array( __CLASS__, 'section_admin_orders_callback' ),
			'rdwceon'
		);
		add_settings_field(
			'delete_templates_on_uninstall',
			__( 'Delete templates on uninstall', 'rdwceon' ),
			array( __CLASS__, 'field_checkbox_callback' ),
			'rdwceon',
			'rdwceon_section_admin_orders',
			array(
				'label_for' => 'delete_templates_on_uninstall',
				'class' => '',
			)
		);
		add_settings_field(
			'show_review_upgrade_notice',
			__( 'Show review / upgrade notice', 'rdwceon' ),
			array( __CLASS__, 'field_checkbox_callback' ),
			'rdwceon',
			'rdwceon_section_admin_orders',
			array(
				'label_for' => 'show_review_upgrade_notice',
				'class' => '',
			)
		);
	}

	public static function before_woocommerce_init() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', RDWCEON_PLUGIN_FILE, true );
		}
	}

	public static function section_admin_orders_callback( $args ) {
		?>
		<a class="button button-secondary" href="<?php echo esc_html( self::get_premium_version_url() ); ?>" target="_blank"><?php esc_html_e( 'Get the premium version', 'rdwceon' ); ?></a>
		<a class="button button-secondary" href="https://wordpress.org/support/plugin/rd-wc-enhanced-order-notes/reviews/#new-post" target="_blank"><?php esc_html_e( 'Review this plugin', 'rdwceon' ); ?></a>
		<a class="button button-secondary" href="<?php echo esc_html( self::get_newsletter_signup_url() ); ?>" target="_blank"><?php esc_html_e( 'Join our mailing list', 'rdwceon' ); ?></a>
		<h4 id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Settings / Options', 'rdwceon' ); ?></h4>
	<?php
	}

	public static function field_checkbox_callback( $args ) {
		$options = self::get_options();
		?>
		<input 
			name="rdwceon_options[<?php echo esc_attr( $args['label_for'] ); ?>]" 
			type="checkbox" id="<?php echo esc_attr( $args['label_for'] ); ?>" 
			value="yes"
			<?php echo ( ( $options[$args['label_for']] ) == 'yes' ) ? 'checked="checked"' : ''; ?>>
		<?php
	}

	public static function menu_exists( $slug = '' ) {
		global $menu;
		foreach ( $menu as $menu_item ) {
			if ( isset( $menu_item[2] ) ) {
				if ( $menu_item[2] == $slug ) {
					return true;
				}
			}
		}
		return false;
	}

	public static function submenu_exists( $parent_slug = '', $slug = '' ) {
		global $submenu;
		if ( isset( $submenu[$parent_slug] ) ) {
			foreach ( $submenu[$parent_slug] as $submenu_item ) {
				if ( isset( $submenu_item[2] ) ) {
					if ( $submenu_item[2] == $slug ) {
						return true;
					}
				}
			}
		}
		return false;
	}

	public static function admin_menu() {
		if ( ! self::menu_exists( 'robot-dwarf-menu' ) ) {
			add_menu_page( 
				__( 'Robot Dwarf', 'rdwceon' ),
				__( 'Robot Dwarf', 'rdwceon' ),
				'manage_options',
				'robot-dwarf-menu',
				array( __CLASS__, 'our_products_page_html' ),
				RDWCEON_URL . 'images/robotdwarf-mascot.png',
				80
			);

			add_submenu_page(
				'robot-dwarf-menu',
				__( 'Our Products', 'rdwceon' ),
				__( 'Our Products', 'rdwceon' ),
				'manage_options',
				'robot-dwarf-menu'
			);
		}

		$hook_name = add_submenu_page(
			'robot-dwarf-menu',
			__( 'Order Note Templates', 'rdwceon' ),
			__( 'Order Note Templates', 'rdwceon' ),
			'manage_options',
			'rdwceon-settings',
			array( __CLASS__, 'settings_page_html' )
		);

		add_action( 'load-' . $hook_name, array( __CLASS__, 'settings_page_submit' ) );
	}

	public static function settings_page_submit() {
		if ( isset( $_POST['submit'] ) ) {
			if ( ! isset( $_POST['rdwceon_settings_nonce'] ) ) {
				return;
			}

			if ( ! wp_verify_nonce( sanitize_text_field( $_POST['rdwceon_settings_nonce'] ), 'rdwceon_settings' ) ) {
				return;
			}

			$options = self::get_options();
			$_GET['options_updated'] = 'true';

			$options['delete_templates_on_uninstall']  = 'no';
			if ( isset( $_POST['rdwceon_options']['delete_templates_on_uninstall'] ) ) {
				$options['delete_templates_on_uninstall'] = 'yes';
			}

			$options['show_review_upgrade_notice']  = 'no';
			if ( isset( $_POST['rdwceon_options']['show_review_upgrade_notice'] ) ) {
				$options['show_review_upgrade_notice'] = 'yes';
			}

			self::update_options( $options );
		}
	}

	public static function our_products_page_html() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$products = array();
		$remote = wp_remote_get(
			RDWCEON_API_URL . 'fetch-products',
			array(
				'timeout' => 10,
				'headers' => array(
					'Content-Type' => 'application/json',
				)
			)
		);

		$payload = json_decode( wp_remote_retrieve_body( $remote ), true );
		$products = ( isset( $payload['products'] ) ) ? $payload['products'] : array();
		?>
		<div class="wc-addons-wrap">
			<div class="wrap">
				<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
				<p><?php esc_html_e( 'We think WooCommerce is a fantastic solution for a wide variety of Ecommerce stores due to its stability, ease of use, and above all, its extensibility.', 'rdwceon' ); ?></p>
				<p><?php esc_html_e( 'With the use of WooCommerce plugins, there are virtually unlimited ways to add functionality and customisations to fit your store and operations.', 'rdwceon' ); ?></p>
				<p><?php esc_html_e( 'In our experience working with ecommerce clients, we have identified key areas, particularly in the order management process, that can be enhanced and improved and have developed several premium WooCommerce plugins specifically aimed at making this process easier for store managers.', 'rdwceon' ); ?></p>
				<div class="addon-product-group__items">
					<ul class="products addons-products-two-column">
						<?php 
						foreach ( $products as $product ) :
							self::render_product_card( $product );
						endforeach;
						?>
					</ul>
				</div>
			</div>
		</div>
		<?php
	}

	public static function render_product_card( $product ) {
		?>
		<li class="product">
			<div class="product-details">
				<div class="product-text-container">
					<a target="_blank" href="<?php echo esc_url( $product['url'] ); ?>">
						<h2><?php echo esc_html( $product['title'] ); ?></h2>
					</a>
					<p><?php echo wp_kses_post( $product['description'] ); ?></p>
				</div>
			</div>
			<div class="product-footer">
				<div class="product-price-and-reviews-container">
					<div class="product-price-block">
						<?php if ( $product['price'] > 0 ) : ?> 
							<span class="price">
								<?php
								echo wp_kses(
									'$' . sprintf( '%01.2f', $product['price'] ),
									array(
										'span' => array(
											'class' => array(),
										),
										'bdi'  => array(),
									)
								);
								?>
							</span>
							<span class="price-suffix">
								<?php
								$price_suffix = __( 'per year', 'woocommerce' );
								echo esc_html( $price_suffix );
								?>
							</span>
						<?php else : ?>
							<span class="price"><?php esc_html_e( 'FREE', 'rdwceon' ); ?></span>
						<?php endif; ?>
					</div>
				</div>
				<a class="button" target="_blank" href="<?php echo esc_url( $product['url'] ); ?>">
					<?php esc_html_e( 'View details', 'woocommerce' ); ?>
				</a>
			</div>
		</li>
		<?php
	}

	public static function settings_page_html() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_POST['submit'] ) ) {
			if ( ! isset( $_POST['rdwceon_settings_nonce'] ) ) {
				return;
			}
			
			if ( ! wp_verify_nonce( sanitize_text_field( $_POST['rdwceon_settings_nonce'] ), 'rdwceon_settings' ) ) {
				return;
			}
		}

		if ( isset( $_GET['options-updated'] ) ) {
			add_settings_error( 'rdwceon_messages', 'rdwceon_message', __( 'Options updated', 'rdwceon' ), 'updated' );
		}

		settings_errors( 'rdwceon_messages' );
		?>
		<div class="wrap">
			<form action="<?php menu_page_url( 'rdwceon' ); ?>" method="post">
				<?php
				wp_nonce_field( 'rdwceon_settings', 'rdwceon_settings_nonce' );
				settings_fields( 'rdwceon' );
				do_settings_sections( 'rdwceon' );
				submit_button( __( 'Update options', 'rdwceon' ) );
				?>
			</form>
			<h4><?php esc_html_e( 'Order Note Templates', 'rdwceon' ); ?></h4>
			<p><?php esc_html_e( 'You can manage your order note templates in the table below', 'rdwceon' ); ?></p>
			<table>
				<body>
				<tr valign="top">
					<td class="rdwceon_templates_wrapper" colspan="2">
						<?php echo wp_kses( self::build_template_modal(), self::get_allowed_modal_html() ); ?>
						<button type="button" data-template-id="0" class="page-title-action rdwceon-show-edit-template-modal"><?php esc_html_e( 'Add new template', 'rdwceon' ); ?></button>
						<table class="rdwceon_templates widefat" cellspacing="0">
							<thead>
								<tr>
									<?php
									$columns = apply_filters(
										'woocommerce_rdwceon_setting_columns',
										array(
											'title' => __( 'Title', 'rdwceon' ),
											'type' => __( 'Type', 'rdwceon' ),
											'category' => __( 'Category', 'rdwceon' ),
											'actions' => '',
										)
									);
									foreach ( $columns as $key => $column ) {
										echo '<th class="rdwceon-settings-table-' . esc_attr( $key ) . '">' . esc_html( $column ) . '</th>';
									}
									?>
								</tr>
							</thead>
							<tbody></tbody>
						</table>
					</td>
				</tr>
				</body>
			</table>
			<h4><?php esc_html_e( 'Get the premium version for advanced features including:', 'rdwceon' ); ?></h4>
			<ul class="rdwceon-upgrade-list">
				<li><?php esc_html_e( 'View and manage order notes directly from the order list screen', 'rdwceon' ); ?></li>
				<li><?php esc_html_e( 'Add HTML to your order notes with a rich text editor', 'rdwceon' ); ?></li>
				<li><?php esc_html_e( 'Tag or mention your colleagues in order notes with optional email notifications', 'rdwceon' ); ?></li>
				<li><?php esc_html_e( 'Change the sort order of order notes to show newest or oldest first', 'rdwceon' ); ?></li>
			</ul>
			<a class="button button-secondary" href="<?php echo esc_html( self::get_premium_version_url() ); ?>" target="_blank"><?php esc_html_e( 'Get the premium version', 'rdwceon' ); ?></a>
		</div>
		<?php
	}

	public static function admin_notices() {
		global $post;
		global $pagenow;

		//Check for WooCommerce is active
		if ( ! self::is_plugin_activated( 'woocommerce' ) ) {
			self::display_woocommerce_plugin_required_notice();
			deactivate_plugins( plugin_basename( RDWCEON_PLUGIN_FILE ) );
		}

		//Show notices
		if ( isset( $pagenow ) && 'post.php' == $pagenow ) {
			if ( isset( $post ) && 'shop_order' == $post->post_type ) {
				$options = self::get_options();
				if ( 'yes' == $options['show_review_upgrade_notice'] ) {
					self::display_review_upgrade_notice();
				}
			}
		}

		//HPOS
		if ( isset( $pagenow ) && 'admin.php' == $pagenow ) {
			$screen = get_current_screen();
			$screen_id = ( $screen ) ? $screen->id : '';
			if ( 'woocommerce_page_wc-orders' == $screen_id && isset( $_GET['action'] ) ) {
				$action = sanitize_text_field( $_GET['action'] );
				if ( 'edit' == $action ) {
					$options = self::get_options();
					if ( 'yes' == $options['show_review_upgrade_notice'] ) {
						self::display_review_upgrade_notice();
					}
				}
			}
		}
	}

	public static function is_plugin_activated( $plugin_name ) {
		$plugin_path = trailingslashit( WP_PLUGIN_DIR ) . "$plugin_name/$plugin_name.php";
		if ( in_array( $plugin_path, wp_get_active_and_valid_plugins() ) ) {
			return true;
		}
		if ( function_exists( 'wp_get_active_network_plugins' ) ) {
			if ( in_array( $plugin_path, wp_get_active_network_plugins() ) ) {
				return true;
			}
		}
		return false;
	}

	public static function display_woocommerce_plugin_required_notice() {
		?>
		<div class="notice notice-error is-dismissible">
			<p><?php echo wp_kses( __( 'The <strong>RD Order Note Templates for WooCommerce</strong> plugin depends on the <strong>WooCommerce</strong> plugin. Please activate <strong>WooCommerce</strong> in order to use the <strong>RD Order Note Templates for WooCommerce</strong> plugin.', 'rdwceon' ), array( 'strong' => array() ) ); ?></p>
		</div>
		<?php
	}

	public static function display_review_upgrade_notice() {
		?>
		<div id="rdwceon-review-upgrade-notice" class="updated notice is-dismissible">
			<p>
			<?php 
			echo wp_kses( sprintf(
				/* translators: %1$s: premium version URL %2$s: newsletter signup url */
				__( 'Thank you for using the <strong>RD Order Note Templates for WooCommerce</strong> plugin. If you find this plugin useful, please consider leaving a <a href="https://wordpress.org/support/plugin/rd-wc-enhanced-order-notes/reviews/#new-post" target="_blank">review</a>. If you need advanced features, have a look at the premium <a href="%1$s" target="_blank">Enhanced Order Notes for WooCommerce</a> plugin. You can also <a href="%2$s" target="_blank">join our mailing list</a> for feature updates, plugin news and discount offers</a><br><br><a href="#" class="rdwceon-hide-notice">Don\'t show again</a>.', 'rdwceon' ),
				esc_html( self::get_premium_version_url() ),
				esc_html( self::get_newsletter_signup_url() ) 
			), array( 
				'br' => array(), 
				'strong' => array(), 
				'a' => array( 'href' => array(), 'target' => array(), 'class' => array(), ), 
			) ); 
			?>
			</p>
		</div>
		<?php
	}

	public static function build_i18n() {
		return array(
			'delete' => __( 'Delete', 'rdwceon' ),
			'delete_note_sure' => __( 'Are you sure you wish to delete this note? This action cannot be undone.', 'rdwceon' ),
		);
	}

	public static function add_meta_boxes_shop_order( $order ) {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		if ( 'shop_order' == $screen_id || 'woocommerce_page_wc-orders' == $screen_id ) {
			remove_meta_box( 'woocommerce-order-notes', $screen_id, 'side' );
			/* Translators: %s order type name. */
			add_meta_box( 'woocommerce-order-notes', sprintf( __( '%s notes', 'woocommerce' ), __( 'Order', 'woocommerce' ) ), 'RDWCEON_Meta_Box_Order_Notes::output', $screen_id, 'side', 'default' );
		}
	}

	public static function plugin_action_links( $links ) {
		$settings_url = menu_page_url( 'rdwceon-settings', false );
		$rd_products_url = menu_page_url( 'robot-dwarf-menu', false );
		$plugin_action_links = array(
			'<a href="' . esc_url( $settings_url ) . '">' . __( 'Settings', 'rdwceon' ) . '</a>',
			'<a href="' . esc_url( $rd_products_url ) . '">' . __( 'RD Products', 'rdwceon' ) . '</a>',
		);

		return array_merge( $plugin_action_links, $links );
	}

	public static function build_template_modal() {
		ob_start(); 
		?>
		<script type="text/template" id="tmpl-wc-modal-rdwceon-template">
			<div class="wc-backbone-modal rdwceon-template">
				<div class="wc-backbone-modal-content">
					<section class="wc-backbone-modal-main" role="main">
						<header class="wc-backbone-modal-header">
							{{{ data.template_header }}}
							<button class="modal-close modal-close-link dashicons dashicons-no-alt">
								<span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', 'woocommerce' ); ?></span>
							</button>
						</header>
						<article>
						{{{ data.template_content }}}
						</article>
						<footer>
							<div class="inner">
								<button type="button" class="button modal-close"><?php esc_html_e( 'Cancel', 'rdwceon' ); ?></button>
								<button type="button" data-template-id="{{ data.template_id }}" class="button button-primary rdwceon-template-save"><?php esc_html_e( 'Save', 'rdwceon' ); ?></button>
							</div>
						</footer>
					</section>
				</div>
			</div>
			<div class="wc-backbone-modal-backdrop modal-close"></div>
		</script>
		<?php
		return ob_get_clean();
	}

	public static function build_template_modal_header( $template_id ) {
		ob_start(); 
		?>
		<?php if ( $template_id ) : ?>
			<h1><?php esc_html_e( 'Edit template', 'rdwceon' ); ?></h1>
		<?php else : ?>
			<h1><?php esc_html_e( 'Add new template', 'rdwceon' ); ?></h1>
		<?php endif; ?>
		<?php
		return ob_get_clean();
	}

	public static function build_template_modal_content( $template_id ) {
		$template = get_post( $template_id );
		$title = ( $template ) ? $template->post_title : '';
		$content = ( $template ) ? $template->post_content : '';
		$type = get_post_meta( $template_id, 'rdwceon_type', true );
		$current_category = get_the_terms( $template_id, 'rdwceon_template_category' );
		$current_category = ( isset( $current_category[0] ) ) ? $current_category[0]->term_id : false;
		$categories = get_terms( array(
			'taxonomy' => 'rdwceon_template_category',
			'number' => 0,
			'hide_empty' => false,
		) );
		$categories = ( $categories ) ? $categories : array();
		ob_start(); 
		?>
		<table class="form-table rdwceon-template-notes-table">
			<tbody>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label><?php esc_html_e( 'Title', 'rdwceon' ); ?><span class="required">*</span></label>
					</th>
					<td class="forminp">
						<fieldset class="form-required">
							<input type="text" class="widefat input-text regular-input rdwceon-pro-template-title" value="<?php echo esc_attr( $title ); ?>">
							<div class="error hidden">
								<label for="template_title"><?php esc_html_e( 'Please enter a valid title', 'rdwceon' ); ?></label>
							</div>
						</fieldset>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label><?php esc_html_e( 'Type', 'rdwceon' ); ?><span class="required">*</span></label>
					</th>
					<td class="forminp">
						<fieldset class="form-required">
							<select class="widefat select wc-enhanced-select rdwceon-template-type">
								<option value="private" <?php echo ( 'private' == $type ) ? 'selected="selected"' : ''; ?>><?php esc_html_e( 'Private note', 'rdwceon' ); ?></option>
								<option value="customer" <?php echo ( 'customer' == $type ) ? 'selected="selected"' : ''; ?>><?php esc_html_e( 'Customer note', 'rdwceon' ); ?></option>
							</select>
						</fieldset>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label><?php esc_html_e( 'Note', 'rdwceon' ); ?><span class="required">*</span></label>
					</th>
					<td class="forminput">
						<fieldset>
							<textarea id="rdwceon-template-note" cols="10" rows="10" class="form-required widefat input-text regular-input rdwceon-template-note"><?php echo esc_html( $content ); ?></textarea>
							<div class="error hidden">
								<label for="template_note"><?php esc_html_e( 'Please enter a valid note', 'rdwceon' ); ?></label>
							</div>
						</fieldset>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label><?php esc_html_e( 'Category', 'rdwceon' ); ?><span class="required">*</span></label>
					</th>
					<td class="forminp">
						<fieldset class="form-required">
							<input type="text" class="input-text regular-input rdwceon-template-new-category" placeholder="<?php esc_html_e( 'New category', 'rdwceon' ); ?>">
							<button type="button" class="button rdwceon-template-new-category-button"><?php echo esc_html_e( 'Add', 'rdwceon' ); ?></button>
							<div class="error hidden">
								<label for="new_category_name"><?php esc_html_e( 'Please enter a valid category name', 'rdwceon' ); ?></label>
							</div>
						</fieldset>
					</td>
				</tr>
				<tr valign="top">
					<td colspan="2" scope="row" class="forminp">
						<table class="widefat rdwceon-template-category-list-table">
							<thead>
								<tr valign="top">
									<th scope="row"><?php echo esc_html_e( 'Select a category:', 'rdwceon' ); ?></th>
									<th>&nbsp;</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $categories as $category ) : ?>
									<tr valign="top">
										<td>
											<div>
												<input id="rdwceon-template-category-radio-<?php echo esc_attr( $category->term_id ); ?>" type="radio" <?php echo ( $category->term_id == $current_category ) ? 'checked="checked"' : ''; ?> name="rdwceon_template_category" value="<?php echo esc_attr( $category->term_id ); ?>">
												<label for="rdwceon-template-category-radio-<?php echo esc_attr( $category->term_id ); ?>"><?php echo esc_html( $category->name ); ?></label>
											</div>
										</td>
										<td>
											<button type="button" data-term-id="<?php echo esc_attr( $category->term_id ); ?>" class="rdwceon-template-category-remove" title="<?php esc_html_e( 'Delete', 'rdwceon' ); ?>">
												<span class="dashicons dashicons-remove"></span>
											</button>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
						<div class="error hidden">
							<label for="template_category"><?php esc_html_e( 'Please select a category', 'rdwceon' ); ?></label>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
		return ob_get_clean();
	}

	public static function get_allowed_modal_html() {
		return array(
			'script' => array(
				'type' => array(),
				'id' => array(),
			),
			'div' => array(
				'class' => array(),
			),
			'section' => array(
				'class' => array(),
				'role' => array(),
			),
			'header' => array(
				'class' => array(),
			),
			'h1' => array(),
			'article' => array(),
			'footer' => array(),
			'h2' => array(),
			'button' => array(
				'class' => array(),
				'type' => array(),
				'data-template-id' => array(),
			),
			'span' =>array(
				'class' => array(),
			),
		);
	}

	public static function register_order_note_template_category_taxonomy() {
		$labels = array(
			'name' => _x( 'Categories', 'taxonomy general name', 'rdwceon' ),
			'singular_name' => _x( 'Category', 'taxonomy singular name', 'rdwceon' ),
			'search_items' => __( 'Search Categories', 'rdwceon' ),
			'all_items' => __( 'All Categories', 'rdwceon' ),
			'parent_item' => __( 'Parent Category', 'rdwceon' ),
			'parent_item_colon' => __( 'Parent Category:', 'rdwceon' ),
			'edit_item' => __( 'Edit Category', 'rdwceon' ),
			'update_item' => __( 'Update Category', 'rdwceon' ),
			'add_mew_item' => __( 'Add New Category', 'rdwceon' ),
			'new_item_name' => __( 'New Category', 'rdwceon' ),
			'menu_name' => __( 'Categories', 'rdwceon' ),
		);

		$args = array(
			'hierarchical' => true,
			'labels' => $labels,
			'show_ui' => true,
			'query_var' => true,
			'rewrite' => 'rdwceon-category',
		);

		register_taxonomy( 'rdwceon_template_category', array( 'rdwceon_template' ), $args );
	}

	public static function register_order_note_template_post_type() {
		$labels = array(
			'name' => _x( 'Order Note Templates', 'Post type general name', 'rdwceon' ),
			'singular_name' => _x( 'Order Note Template', 'Post type singular name', 'rdwceon' ),
			'menu_name' => _x( 'Order Note Templates', 'Admin Menu Text', 'rdwceon' ),
			'name_admin_bar' => _x( 'Order Note Template', 'Add New on Toolbar', 'rdwceon' ),
			'add_new' => __( 'Add New', 'rdwceon' ),
			'add_new_item' => __( 'Add New Template', 'rdwceon' ),
			'new_item' => __( 'New Template', 'rdwceon' ),
			'edit_item' => __( 'Edit Template', 'rdwceon' ),
			'view_item' => __( 'View Template', 'rdwceon' ),
			'all_items' => __( 'All Templates', 'rdwceon' ),
			'search_items' => __( 'Search Templates', 'rdwceon' ),
			'parent_item_colon' => __( 'Parent Template:', 'rdwceon' ),
			'not_found' => __( 'No Templates Found', 'rdwceon' ),
			'not_found_in_trash' => __( 'No Templates Found in Trash', 'rdwceon' ),
		);

		$args = array(
			'labels' => $labels,
			'public' => false,
			'publicly_queryable' => false,
			'show_ui' => true,
			'show_in_menu' => false,
			'query_var' => true,
			'rewrite' => array(
				'slug' => 'rdwceon-template',
			),
			'capability_type' => 'post',
			'has_archive' => false,
			'hierarchical' => false,
			'menu_position' => null,
			'supports' => array(
				'title',
				'editor',
				'author',
				'custom-fields',
			),
			'taxonomies' => array(
				'rdwceon_template_category',
			),
			'register_meta_box_cb' => function() {
				//meta box
			},
		);
		register_post_type( 'rdwceon_template', $args );
	}
}
