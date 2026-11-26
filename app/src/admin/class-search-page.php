<?php
/**
 * Search page.
 *
 * @package Cardoza_Hybrid_Search
 */

declare(strict_types=1);

namespace Cardoza_Hybrid_Search\Admin;

use Cardoza_Hybrid_Search\Chat;
use Cardoza_Hybrid_Search\Search_Service;

/**
 * Hybrid Search → Search: how visitors see Cardoza Hybrid Search on the site.
 *
 * - Use Cardoza Hybrid Search for the theme's own search (default on).
 * - Show the AI answer above search results automatically (default off).
 *
 * @package Cardoza_Hybrid_Search
 */
class Search_Page {

	/**
	 * Settings group name.
	 *
	 * @var string
	 */
	public const GROUP = 'cardoza_hs_search';

	/**
	 * Register the options and how they are sanitized. Runs on admin_init.
	 *
	 * An unticked switch isn't submitted, so null sanitizes to false.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			self::GROUP,
			Search_Service::REPLACE_SEARCH_OPTION,
			array(
				'type'              => 'boolean',
				'default'           => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
			)
		);

		register_setting(
			self::GROUP,
			Search_Service::AUTO_ANSWER_OPTION,
			array(
				'type'              => 'boolean',
				'default'           => false,
				'sanitize_callback' => 'rest_sanitize_boolean',
			)
		);
	}

	/**
	 * Render the page.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		Layout::open( Layout::SEARCH_PAGE, __( 'Search', 'cardoza-hybrid-search' ) );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only flag set by options.php's redirect.
		if ( isset( $_GET['settings-updated'] ) && ! get_settings_errors() ) {
			add_settings_error( 'general', 'settings_updated', __( 'Settings saved.', 'cardoza-hybrid-search' ), 'success' );
		}

		settings_errors();
		?>
		<div class="cardoza-hs-grid">
			<form class="cardoza-hs-col" action="options.php" method="post">
				<?php settings_fields( self::GROUP ); ?>

				<section class="cardoza-hs-card">
					<div class="cardoza-hs-card__head">
						<span class="cardoza-hs-icon"><?php Layout::icon( 'search' ); ?></span>
						<div>
							<h2 class="cardoza-hs-card__title"><?php esc_html_e( 'Search on your site', 'cardoza-hybrid-search' ); ?></h2>
							<p class="cardoza-hs-card__sub"><?php esc_html_e( 'How visitors see Cardoza Hybrid Search.', 'cardoza-hybrid-search' ); ?></p>
						</div>
					</div>
					<div class="cardoza-hs-card__body">
						<?php
						$this->switch_row(
							Search_Service::REPLACE_SEARCH_OPTION,
							__( 'Use Cardoza Hybrid Search for site search', 'cardoza-hybrid-search' ),
							__( 'Your theme\'s search box and results page use keyword + semantic ranking. If nothing matches, WordPress\'s own search is used.', 'cardoza-hybrid-search' ),
							Search_Service::replaces_search()
						);

						$this->switch_row(
							Search_Service::AUTO_ANSWER_OPTION,
							__( 'Show an AI answer above search results', 'cardoza-hybrid-search' ),
							Chat::is_configured()
								? __( 'Adds the AI Answer box automatically. Turn this off if you place the [cardoza_hs_answer] shortcode yourself.', 'cardoza-hybrid-search' )
								: __( 'Needs a chat model: set it up on the Chat page first.', 'cardoza-hybrid-search' ),
							Search_Service::auto_answer()
						);
						?>
					</div>
				</section>

				<div class="cardoza-hs-savebar">
					<span><?php esc_html_e( 'Changes apply to the next search.', 'cardoza-hybrid-search' ); ?></span>
					<div class="cardoza-hs-actions">
						<button type="reset" class="cardoza-hs-btn cardoza-hs-btn--ghost"><?php esc_html_e( 'Discard', 'cardoza-hybrid-search' ); ?></button>
						<button type="submit" class="cardoza-hs-btn cardoza-hs-btn--primary"><?php esc_html_e( 'Save changes', 'cardoza-hybrid-search' ); ?></button>
					</div>
				</div>
			</form>

			<aside class="cardoza-hs-col">
				<section class="cardoza-hs-card cardoza-hs-card--aside">
					<h2 class="cardoza-hs-card__title"><?php esc_html_e( 'AI answers', 'cardoza-hybrid-search' ); ?></h2>
					<?php if ( Chat::is_configured() ) : ?>
						<p class="cardoza-hs-status cardoza-hs-status--ok"><?php esc_html_e( 'Chat model configured', 'cardoza-hybrid-search' ); ?></p>
					<?php else : ?>
						<p class="cardoza-hs-status"><?php esc_html_e( 'No chat model yet', 'cardoza-hybrid-search' ); ?></p>
						<p class="cardoza-hs-note">
							<a href="<?php echo esc_url( Layout::url( Layout::CHAT_PAGE ) ); ?>"><?php esc_html_e( 'Set up a chat model →', 'cardoza-hybrid-search' ); ?></a>
						</p>
					<?php endif; ?>
				</section>

				<section class="cardoza-hs-card cardoza-hs-card--aside">
					<h2 class="cardoza-hs-card__title"><?php esc_html_e( 'Place it yourself', 'cardoza-hybrid-search' ); ?></h2>
					<p class="cardoza-hs-note"><?php esc_html_e( 'Instead of the automatic answer box, add this shortcode where you want the answer to appear on search results:', 'cardoza-hybrid-search' ); ?></p>
					<pre class="cardoza-hs-code">[cardoza_hs_answer]</pre>
					<p class="cardoza-hs-note"><?php esc_html_e( 'Works in classic theme templates and widgets, and in a Shortcode block in a block theme\'s Search Results template.', 'cardoza-hybrid-search' ); ?></p>
				</section>
			</aside>
		</div>
		<?php
		Layout::close();
	}

	/**
	 * Render a switch row: label and description on the left, toggle on the right.
	 *
	 * @param string $option      Option name, used as the checkbox id and name.
	 * @param string $label       Field label.
	 * @param string $description Help text.
	 * @param bool   $checked     Current value.
	 * @return void
	 */
	private function switch_row( string $option, string $label, string $description, bool $checked ): void {
		?>
		<div class="cardoza-hs-row">
			<div class="cardoza-hs-row__text">
				<label class="cardoza-hs-row__label" for="<?php echo esc_attr( $option ); ?>"><?php echo esc_html( $label ); ?></label>
				<p class="cardoza-hs-row__desc" id="<?php echo esc_attr( $option ); ?>-desc"><?php echo esc_html( $description ); ?></p>
			</div>
			<input
				class="cardoza-hs-switch"
				type="checkbox"
				role="switch"
				id="<?php echo esc_attr( $option ); ?>"
				name="<?php echo esc_attr( $option ); ?>"
				value="1"
				aria-describedby="<?php echo esc_attr( $option ); ?>-desc"
				<?php checked( $checked ); ?>
			/>
		</div>
		<?php
	}
}
