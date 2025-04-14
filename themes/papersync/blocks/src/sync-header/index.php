<?php
/**
 * Award Slider Block.
 *
 * @package anfco
 */

use \Abercrombie\Features\Inc\Post_Types\Awards;

/**
 * Enqueue/Dequeue scripts and styles.
 *
 * @since Anfco 1.0
 *
 * @return void
 */
function anfco_award_slider_assets() {

	if ( ! has_block( 'anfco/award-slider' ) ) {
		wp_dequeue_style( 'anfco-award-slider-style' );
	}
}

add_action( 'wp_enqueue_scripts', 'anfco_award_slider_assets' );

/**
 * Register award slider block.
 *
 * @return void
 */
function register_award_slider_block() {

	\Anfco\Assets::get_instance()->register_script( 'anfco-award-slider', 'blocks/src/award-slider/slider.js', array( 'anfco-splide-script' ) );
	\Anfco\Assets::get_instance()->register_style( 'anfco-award-slider-style', 'blocks/build/award-slider/style-index.css', array( 'anfco-splide-style' ) );

	register_block_type(
		ANFCO_BLOCK_BUILD . '/award-slider',
		[

			'render_callback' => 'render_award_slider_callback',

		]
	);

}

add_action( 'init', 'register_award_slider_block' );

/**
 * Render callback for block.
 *
 * @param array $attributes Block attributes.
 *
 * @return string Rendered HTML.
 */
function render_award_slider_callback( $attributes = [] ) {

	$attributes = wp_parse_args( $attributes, [] );

	$post_ids = [];

	foreach ( $attributes['posts'] as $item ) {
		$post_ids[] = $item['value'];
	}

	// Fallback if no post added from Block options.
	if ( empty( $post_ids ) ) {

		$args = [
			'post_type'              => Awards::SLUG,
			'post_status'            => 'publish',
			'orderby'                => 'menu_order date',
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'no_found_rows'          => true,
			'ignore_sticky_posts'    => 1,
			'fields'                 => 'ids',
			'posts_per_page'         => ! empty( $attributes['items'] ) ? $attributes['items'] : 4,
			'order'                  => ! empty( $attributes['order'] ) ? $attributes['order'] : 'ASC',
		];

		$query = new WP_Query( $args );

		$post_ids = $query->posts;

	}

	ob_start();
	?>
	<div <?php echo wp_kses_data( get_block_wrapper_attributes() ); ?>>
		<?php
		if ( empty( $post_ids ) ) {

			printf( '<div class="no-post-found">%s</div>', esc_html__( 'No Award posts found, please add one to use this block.', 'anfco' ) );

		} else {
			?>

			<div class="anfco-awards-section">
				<ul class="anfco-awards-list" role="presentation">
					<?php
					$total_awards = count( $post_ids );
					$index = 0;
					foreach ( $post_ids as $award_id ) {
						if ( 'publish' !== get_post_status( $award_id ) ) {
							continue;
						}
						$award_year = get_field( 'anfco_award_year', $award_id );
						$index++;
						// translators: 1: Current Index, 2: Total number of award posts
						?>
						<li class="anfco-awards-list-item" role="group" aria-label="<?php printf( esc_attr__( '%1$s of %2$s', 'anfco' ), esc_attr( $index ), esc_attr( $total_awards ) ); ?>">
							<div class="award-content-box">
								<figure class="award-thumbnail-img<?php echo ( ! has_post_thumbnail( $award_id ) ) ? ' is-placeholder' : ''; ?>">
									<?php
									if ( has_post_thumbnail( $award_id ) ) {
										$image_id = get_post_thumbnail_id( $award_id ); // Get the ID of the featured image
											$alt_text = get_post_meta( $image_id, '_wp_attachment_image_alt', true ); // Get the alt text

											// Set the attributes array
											$attributes = array(
												'class' => 'award-thumbnail',
											);

											// Add title attribute only if alt text is present
											if ( $alt_text ) {
												$attributes['title'] = $alt_text;
											}

											// Output the post thumbnail with specified attributes
											echo get_the_post_thumbnail( $award_id, 'thumb-293x200', $attributes );
									}
									?>
								</figure>
								<div class="award-content">
									<h3 class="has-large-font-size award-title"><?php echo esc_html( get_the_title( $award_id ) ); ?></h3>
									<p class="has-body-text-color has-small-font-size award-year"><?php echo esc_html( $award_year ); ?></p>
								</div>
							</div>
						</li>
						<?php
					}
					?>
				</ul>
			</div>
			<?php
		}
		?>
	</div>
	<?php
	$content = ob_get_clean();
	wp_reset_postdata();

	return $content;
}
