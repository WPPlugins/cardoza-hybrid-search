<?php
/**
 * Chat model page.
 *
 * @package Cardoza_Hybrid_Search
 */

declare(strict_types=1);

namespace Cardoza_Hybrid_Search\Admin;

use Cardoza_Hybrid_Search\Chat;
use Cardoza_Hybrid_Search\Indexer;

/**
 * Hybrid Search → Chat: the chat model that writes an answer from the
 * final search results, and the prompt it is given.
 *
 * The preview card fills the prompt with a sample question and the site's
 * latest posts; assets/js/settings.js updates it as the prompt is edited.
 *
 * @package Cardoza_Hybrid_Search
 */
class Chat_Page {

	/**
	 * Settings group name.
	 *
	 * @var string
	 */
	public const GROUP = 'cardoza_hs_chat';

	/**
	 * Register the options and how they are sanitized. Runs on admin_init.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			self::GROUP,
			Chat::ENDPOINT_OPTION,
			array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => array( $this, 'sanitize_endpoint' ),
			)
		);

		register_setting(
			self::GROUP,
			Chat::API_KEY_OPTION,
			array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		register_setting(
			self::GROUP,
			Chat::MODEL_OPTION,
			array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		register_setting(
			self::GROUP,
			Chat::PROMPT_OPTION,
			array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => array( $this, 'sanitize_prompt' ),
			)
		);
	}

	/**
	 * Sanitize the chat endpoint URL. Only http and https URLs are accepted.
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
			if ( function_exists( 'add_settings_error' ) ) {
				add_settings_error(
					Chat::ENDPOINT_OPTION,
					'invalid_chat_endpoint',
					__( 'The chat endpoint must be a valid http or https URL.', 'cardoza-hybrid-search' )
				);
			}

			return (string) get_option( Chat::ENDPOINT_OPTION, '' );
		}

		return $url;
	}

	/**
	 * Sanitize the prompt.
	 *
	 * The text is kept as written (including things like <context> tags that
	 * prompts often use): it is only ever shown escaped and sent to the chat
	 * API, never output as HTML. Invalid UTF-8 and control characters other
	 * than newlines and tabs are removed.
	 *
	 * Saving the default text unchanged stores nothing, so future
	 * improvements to the default still apply.
	 *
	 * @param mixed $value Submitted value.
	 * @return string
	 */
	public function sanitize_prompt( $value ): string {
		$prompt = str_replace( array( "\r\n", "\r" ), "\n", wp_check_invalid_utf8( (string) $value ) );
		// Remove Unicode "Other" characters (\p{C}: control, format, private-use,
		// unassigned) except newlines and tabs. [^\P{C}\n\t] = "in C, and not \n
		// or \t". The /u flag makes it match by code point, not byte. preg_replace()
		// returns null on failure, hence the (string) cast.
		$prompt = trim( (string) preg_replace( '/[^\P{C}\n\t]/u', '', $prompt ) );

		if ( Chat::DEFAULT_PROMPT === $prompt ) {
			return '';
		}

		if ( $prompt !== '' && ! str_contains( $prompt, '{results}' ) ) {
			function_exists( 'add_settings_error' ) && add_settings_error(
				Chat::PROMPT_OPTION,
				'prompt_without_results',
				__( 'The prompt has no {results} placeholder, so the chat model will not see the search results.', 'cardoza-hybrid-search' ),
				'warning'
			);
		}

		return $prompt;
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

		$sample_query   = __( 'How do I get started?', 'cardoza-hybrid-search' );
		$sample_results = Chat::format_results( $this->sample_results() );

		Layout::open( Layout::CHAT_PAGE, __( 'Chat', 'cardoza-hybrid-search' ) );

		// Warnings (e.g. a prompt without {results}) still count as saved; only errors don't.
		$messages = get_settings_errors();
		$blocking = array_filter( $messages, fn( array $message ): bool => $message['type'] === 'error' || $message['code'] === 'settings_updated' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only flag set by options.php's redirect.
		if ( isset( $_GET['settings-updated'] ) && $blocking === array() ) {
			add_settings_error( 'general', 'settings_updated', __( 'Settings saved.', 'cardoza-hybrid-search' ), 'success' );
		}

		settings_errors();
		?>
		<div class="cardoza-hs-grid">
			<form class="cardoza-hs-col" action="options.php" method="post">
				<?php settings_fields( self::GROUP ); ?>

				<section class="cardoza-hs-card">
					<div class="cardoza-hs-card__head">
						<span class="cardoza-hs-icon"><?php Layout::icon( 'chat' ); ?></span>
						<div>
							<h2 class="cardoza-hs-card__title"><?php esc_html_e( 'Chat model', 'cardoza-hybrid-search' ); ?></h2>
							<p class="cardoza-hs-card__sub"><?php esc_html_e( 'Writes an answer from the final search results. Separate from the embedding service.', 'cardoza-hybrid-search' ); ?></p>
						</div>
					</div>
					<div class="cardoza-hs-card__body">
						<?php
						$this->input_row(
							Chat::ENDPOINT_OPTION,
							'url',
							__( 'Endpoint URL', 'cardoza-hybrid-search' ),
							__( 'Required. An OpenAI-compatible Chat Completions URL (http or https), from a hosted provider or a local server such as Ollama or LM Studio.', 'cardoza-hybrid-search' ),
							'https://example.com/v1/chat/completions'
						);

						$this->input_row(
							Chat::API_KEY_OPTION,
							'password',
							__( 'API key', 'cardoza-hybrid-search' ),
							__( 'Optional. Sent as a Bearer token only when set.', 'cardoza-hybrid-search' ),
							''
						);

						$this->input_row(
							Chat::MODEL_OPTION,
							'text',
							__( 'Model', 'cardoza-hybrid-search' ),
							__( 'The chat model name your endpoint expects.', 'cardoza-hybrid-search' ),
							'gpt-4o-mini'
						);
						?>
					</div>
				</section>

				<section class="cardoza-hs-card">
					<div class="cardoza-hs-card__head">
						<span class="cardoza-hs-icon"><?php Layout::icon( 'text' ); ?></span>
						<div>
							<h2 class="cardoza-hs-card__title"><?php esc_html_e( 'Answer prompt', 'cardoza-hybrid-search' ); ?></h2>
							<p class="cardoza-hs-card__sub"><?php esc_html_e( 'Instructions sent to the chat model together with the search results.', 'cardoza-hybrid-search' ); ?></p>
						</div>
					</div>
					<div class="cardoza-hs-card__body">
						<div class="cardoza-hs-row cardoza-hs-row--stacked">
							<div class="cardoza-hs-row__text">
								<label class="cardoza-hs-row__label" for="<?php echo esc_attr( Chat::PROMPT_OPTION ); ?>"><?php esc_html_e( 'Prompt', 'cardoza-hybrid-search' ); ?></label>
								<p class="cardoza-hs-row__desc" id="<?php echo esc_attr( Chat::PROMPT_OPTION ); ?>-desc">
									<?php esc_html_e( 'Placeholders:', 'cardoza-hybrid-search' ); ?>
									<code>{query}</code> <?php esc_html_e( 'the visitor\'s search,', 'cardoza-hybrid-search' ); ?>
									<code>{results}</code> <?php esc_html_e( 'the numbered search results. Clear the box to use the default.', 'cardoza-hybrid-search' ); ?>
								</p>
							</div>
							<textarea
								class="cardoza-hs-textarea"
								id="<?php echo esc_attr( Chat::PROMPT_OPTION ); ?>"
								name="<?php echo esc_attr( Chat::PROMPT_OPTION ); ?>"
								rows="10"
								spellcheck="false"
								aria-describedby="<?php echo esc_attr( Chat::PROMPT_OPTION ); ?>-desc"
								data-cardoza-hs-prompt
								data-default="<?php echo esc_attr( Chat::DEFAULT_PROMPT ); ?>"
							><?php echo esc_textarea( Chat::prompt() ); ?></textarea>
						</div>
					</div>
				</section>

				<div class="cardoza-hs-savebar">
					<span><?php esc_html_e( 'Used for AI answers on search results.', 'cardoza-hybrid-search' ); ?></span>
					<div class="cardoza-hs-actions">
						<button type="reset" class="cardoza-hs-btn cardoza-hs-btn--ghost"><?php esc_html_e( 'Discard', 'cardoza-hybrid-search' ); ?></button>
						<button type="submit" class="cardoza-hs-btn cardoza-hs-btn--primary"><?php esc_html_e( 'Save changes', 'cardoza-hybrid-search' ); ?></button>
					</div>
				</div>
			</form>

			<aside class="cardoza-hs-col">
				<section class="cardoza-hs-card cardoza-hs-card--aside">
					<h2 class="cardoza-hs-card__title"><?php esc_html_e( 'Status', 'cardoza-hybrid-search' ); ?></h2>
					<?php if ( Chat::is_configured() ) : ?>
						<p class="cardoza-hs-status cardoza-hs-status--ok"><?php esc_html_e( 'Chat model configured', 'cardoza-hybrid-search' ); ?></p>
					<?php else : ?>
						<p class="cardoza-hs-status"><?php esc_html_e( 'Set an endpoint and model to enable AI answers', 'cardoza-hybrid-search' ); ?></p>
					<?php endif; ?>
				</section>

				<section class="cardoza-hs-card cardoza-hs-card--aside">
					<h2 class="cardoza-hs-card__title"><?php esc_html_e( 'Prompt preview', 'cardoza-hybrid-search' ); ?></h2>
					<pre
						class="cardoza-hs-code cardoza-hs-code--wrap"
						data-cardoza-hs-prompt-preview
						data-query="<?php echo esc_attr( $sample_query ); ?>"
						data-results="<?php echo esc_attr( $sample_results ); ?>"
					><?php echo esc_html( Chat::render_prompt( $sample_query, $this->sample_results() ) ); ?></pre>
					<p class="cardoza-hs-note"><?php esc_html_e( 'Sample question with your latest posts as results.', 'cardoza-hybrid-search' ); ?></p>
				</section>
			</aside>
		</div>
		<?php
		Layout::close();
	}

	/**
	 * Latest published posts of enabled types, shaped like search results.
	 *
	 * @return array List of array{title: string, url: string, excerpt: string}.
	 */
	private function sample_results(): array {
		static $results = null;

		if ( $results !== null ) {
			return $results;
		}

		$post_types = Indexer::post_types();
		$results    = array();

		if ( $post_types === array() ) {
			return $results;
		}

		$posts = get_posts(
			array(
				'post_type'      => $post_types,
				'post_status'    => 'publish',
				'posts_per_page' => 2,
				'has_password'   => false,
			)
		);

		foreach ( $posts as $post ) {
			$results[] = array(
				'title'   => get_the_title( $post ),
				'url'     => (string) get_permalink( $post ),
				'excerpt' => wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), 20 ),
			);
		}

		return $results;
	}

	/**
	 * Render one input row: label and description on the left, input on the right.
	 *
	 * @param string $option      Option name, used as the input's id and name.
	 * @param string $type        Input type: text, url or password.
	 * @param string $label       Field label.
	 * @param string $description Help text.
	 * @param string $placeholder Placeholder text.
	 * @return void
	 */
	private function input_row( string $option, string $type, string $label, string $description, string $placeholder ): void {
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
