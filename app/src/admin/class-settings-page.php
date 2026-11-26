<?php
/**
 * Settings page.
 *
 * @package Cardoza_Hybrid_Search
 */

declare(strict_types=1);

namespace Cardoza_Hybrid_Search\Admin;

use Cardoza_Hybrid_Search\Embeddings;

/**
 * Hybrid Search → Settings: the embedding endpoint, API key and model.
 *
 * Saving uses the WordPress Settings API (options.php), which handles the
 * nonce, capability check and sanitizing.
 *
 * @package Cardoza_Hybrid_Search
 */
class Settings_Page {

	/**
	 * Settings group name.
	 *
	 * @var string
	 */
	public const GROUP = 'cardoza_hs_settings';

	/**
	 * Register the options and how they are sanitized. Runs on admin_init.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			self::GROUP,
			Embeddings::ENDPOINT_OPTION,
			array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => array( $this, 'sanitize_endpoint' ),
			)
		);

		register_setting(
			self::GROUP,
			Embeddings::API_KEY_OPTION,
			array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		register_setting(
			self::GROUP,
			Embeddings::MODEL_OPTION,
			array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
	}

	/**
	 * Sanitize the endpoint URL. Only http and https URLs are accepted.
	 *
	 * An invalid URL shows an error and keeps the saved value.
	 *
	 * @param mixed $value Submitted value.
	 * @return string
	 */
	public function sanitize_endpoint( $value ): string {
		$value = trim( (string) $value );

		if ( $value === '' ) {
			return '';
		}

		$url = esc_url_raw( $value, array( 'http', 'https' ) );

		if ( $url === '' ) {
			function_exists( 'add_settings_error' ) && add_settings_error(
				Embeddings::ENDPOINT_OPTION,
				'invalid_endpoint',
				__( 'The endpoint must be a valid http or https URL.', 'cardoza-hybrid-search' )
			);

			return (string) get_option( Embeddings::ENDPOINT_OPTION, '' );
		}

		return $url;
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

		Layout::open( Layout::SETTINGS_PAGE, __( 'Settings', 'cardoza-hybrid-search' ) );

		$this->notices();
		?>
		<div class="cardoza-hs-grid">
			<form class="cardoza-hs-col" action="options.php" method="post">
				<?php settings_fields( self::GROUP ); ?>

				<section class="cardoza-hs-card">
					<div class="cardoza-hs-card__head">
						<span class="cardoza-hs-icon"><?php Layout::icon( 'plug' ); ?></span>
						<div>
							<h2 class="cardoza-hs-card__title"><?php esc_html_e( 'Embedding service', 'cardoza-hybrid-search' ); ?></h2>
							<p class="cardoza-hs-card__sub"><?php esc_html_e( 'Where post content is sent to be turned into vectors.', 'cardoza-hybrid-search' ); ?></p>
						</div>
					</div>
					<div class="cardoza-hs-card__body">
						<?php
						$this->row(
							Embeddings::ENDPOINT_OPTION,
							'url',
							__( 'Endpoint URL', 'cardoza-hybrid-search' ),
							__( 'Required. An http or https URL that accepts a batch of texts and returns one vector per text.', 'cardoza-hybrid-search' ),
							'https://example.com/embed'
						);

						$this->row(
							Embeddings::API_KEY_OPTION,
							'password',
							__( 'API key', 'cardoza-hybrid-search' ),
							__( 'Optional. Sent as a Bearer token only when set. Leave empty for services without authentication.', 'cardoza-hybrid-search' ),
							''
						);

						$this->row(
							Embeddings::MODEL_OPTION,
							'text',
							__( 'Model', 'cardoza-hybrid-search' ),
							sprintf(
								/* translators: %d: number of dimensions. */
								__( 'Must produce %d-dimension vectors. Changing it re-embeds each post on its next index run.', 'cardoza-hybrid-search' ),
								Embeddings::DIMENSIONS
							),
							'all-MiniLM-L6-v2'
						);
						?>
					</div>
				</section>

				<div class="cardoza-hs-savebar">
					<span><?php esc_html_e( 'Changes apply to the next indexing run.', 'cardoza-hybrid-search' ); ?></span>
					<div class="cardoza-hs-actions">
						<button type="reset" class="cardoza-hs-btn cardoza-hs-btn--ghost"><?php esc_html_e( 'Discard', 'cardoza-hybrid-search' ); ?></button>
						<button type="submit" class="cardoza-hs-btn cardoza-hs-btn--primary"><?php esc_html_e( 'Save changes', 'cardoza-hybrid-search' ); ?></button>
					</div>
				</div>
			</form>

			<aside class="cardoza-hs-col">
				<section class="cardoza-hs-card cardoza-hs-card--aside">
					<h2 class="cardoza-hs-card__title"><?php esc_html_e( 'Request format', 'cardoza-hybrid-search' ); ?></h2>
					<pre class="cardoza-hs-code">POST {"model": "…", "input": ["…", "…"]}
→ {"data": [{"index": 0, "embedding": […]}, …]}</pre>
					<p class="cardoza-hs-note"><?php esc_html_e( 'OpenAI-compatible embeddings format (OpenAI, OpenRouter, Ollama, LM Studio). Up to 16 texts per request, one vector per text.', 'cardoza-hybrid-search' ); ?></p>
				</section>
			</aside>
		</div>
		<?php
		Layout::close();
	}

	/**
	 * Show "Settings saved." and validation errors after saving.
	 *
	 * WordPress only does this automatically for pages under the Settings
	 * menu (options-head.php), so a top-level page has to do it itself.
	 *
	 * @return void
	 */
	private function notices(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only flag set by options.php's redirect.
		if ( isset( $_GET['settings-updated'] ) && ! get_settings_errors() ) {
			add_settings_error( 'general', 'settings_updated', __( 'Settings saved.', 'cardoza-hybrid-search' ), 'success' );
		}

		settings_errors();
	}

	/**
	 * Render one setting row: label and description on the left, input on the right.
	 *
	 * @param string $option      Option name, used as the input's id and name.
	 * @param string $type        Input type: text, url or password.
	 * @param string $label       Field label.
	 * @param string $description Help text.
	 * @param string $placeholder Placeholder text.
	 * @return void
	 */
	private function row( string $option, string $type, string $label, string $description, string $placeholder ): void {
		?>
		<div class="cardoza-hs-row">
			<div class="cardoza-hs-row__text">
				<label class="cardoza-hs-row__label" for="<?php echo esc_attr( $option ); ?>"><?php echo esc_html( $label ); ?></label>
				<p class="cardoza-hs-row__desc" id="<?php echo esc_attr( $option ); ?>-desc"><?php echo esc_html( $description ); ?></p>
			</div>
			<input
				class="cardoza-hs-input"
				type="<?php echo esc_attr( $type ); ?>"
				id="<?php echo esc_attr( $option ); ?>"
				name="<?php echo esc_attr( $option ); ?>"
				value="<?php echo esc_attr( (string) get_option( $option, '' ) ); ?>"
				placeholder="<?php echo esc_attr( $placeholder ); ?>"
				aria-describedby="<?php echo esc_attr( $option ); ?>-desc"
				autocomplete="off"
				spellcheck="false"
			/>
		</div>
		<?php
	}
}
