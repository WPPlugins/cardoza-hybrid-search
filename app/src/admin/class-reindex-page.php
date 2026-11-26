<?php
/**
 * Re-Index page.
 *
 * @package Cardoza_Hybrid_Search
 */

declare(strict_types=1);

namespace Cardoza_Hybrid_Search\Admin;

use Cardoza_Hybrid_Search\Embeddings;
use Cardoza_Hybrid_Search\Hooks\Reindexing;
use Cardoza_Hybrid_Search\Index_Repository;
use Cardoza_Hybrid_Search\Reindexer;

/**
 * Hybrid Search → Re-Index: start a re-index of all posts, follow its
 * progress, and see index statistics.
 *
 * While a job runs, assets/js/settings.js polls the AJAX step in
 * Hooks\Reindexing and swaps in the markup from status_html().
 *
 * @package Cardoza_Hybrid_Search
 */
class Reindex_Page {

	/**
	 * Render the page.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$model   = (string) get_option( Embeddings::MODEL_OPTION, '' );
		$stats   = ( new Index_Repository() )->stats();
		$job     = ( new Reindexer() )->status();
		$running = $job['status'] === 'running';

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only flag set by our own redirect.
		$notice = isset( $_GET['cardoza_hs'] ) ? sanitize_key( wp_unslash( $_GET['cardoza_hs'] ) ) : '';

		Layout::open( Layout::REINDEX_PAGE, __( 'Re-Index', 'cardoza-hybrid-search' ) );
		?>
		<?php if ( $notice === 'started' ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Re-index started. You can leave this page; it continues in the background.', 'cardoza-hybrid-search' ); ?></p></div>
		<?php elseif ( $notice === 'no-endpoint' ) : ?>
			<div class="notice notice-error is-dismissible">
				<p>
					<?php esc_html_e( 'Set the embedding endpoint URL before re-indexing.', 'cardoza-hybrid-search' ); ?>
					<a href="<?php echo esc_url( Layout::url( Layout::SETTINGS_PAGE ) ); ?>"><?php esc_html_e( 'Go to Settings', 'cardoza-hybrid-search' ); ?></a>
				</p>
			</div>
		<?php endif; ?>

		<div class="cardoza-hs-grid">
			<div class="cardoza-hs-col">
				<section
					class="cardoza-hs-card"
					data-cardoza-hs-reindex
					data-status="<?php echo esc_attr( $job['status'] ); ?>"
					data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
					data-nonce="<?php echo esc_attr( wp_create_nonce( Reindexing::NONCE ) ); ?>"
				>
					<div class="cardoza-hs-card__head">
						<span class="cardoza-hs-icon"><?php Layout::icon( 'refresh' ); ?></span>
						<div>
							<h2 class="cardoza-hs-card__title"><?php esc_html_e( 'Indexing', 'cardoza-hybrid-search' ); ?></h2>
							<p class="cardoza-hs-card__sub"><?php esc_html_e( 'Re-embed every published post and page. Runs in the background in batches.', 'cardoza-hybrid-search' ); ?></p>
						</div>
					</div>
					<div class="cardoza-hs-card__body">
						<div class="cardoza-hs-row">
							<div class="cardoza-hs-row__text cardoza-hs-reindex" data-cardoza-hs-reindex-status aria-live="polite">
								<?php echo self::status_html( $job ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside status_html(). ?>
							</div>
							<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
								<input type="hidden" name="action" value="cardoza_hs_reindex" />
								<?php wp_nonce_field( Reindexing::NONCE ); ?>
								<button
									type="submit"
									class="cardoza-hs-btn cardoza-hs-btn--primary"
									data-cardoza-hs-reindex-button
									data-idle-label="<?php esc_attr_e( 'Re-index all posts', 'cardoza-hybrid-search' ); ?>"
									<?php disabled( $running ); ?>
								><?php echo esc_html( $running ? __( 'Re-indexing…', 'cardoza-hybrid-search' ) : __( 'Re-index all posts', 'cardoza-hybrid-search' ) ); ?></button>
							</form>
						</div>
					</div>
				</section>
			</div>

			<aside class="cardoza-hs-col">
				<section class="cardoza-hs-card cardoza-hs-card--aside">
					<h2 class="cardoza-hs-card__title"><?php esc_html_e( 'Index', 'cardoza-hybrid-search' ); ?></h2>
					<dl class="cardoza-hs-stats">
						<div>
							<dt><?php esc_html_e( 'Posts indexed', 'cardoza-hybrid-search' ); ?></dt>
							<dd data-cardoza-hs-stat="posts"><?php echo esc_html( number_format_i18n( $stats['posts'] ) ); ?></dd>
						</div>
						<div>
							<dt><?php esc_html_e( 'Chunks', 'cardoza-hybrid-search' ); ?></dt>
							<dd data-cardoza-hs-stat="chunks"><?php echo esc_html( number_format_i18n( $stats['chunks'] ) ); ?></dd>
						</div>
						<div>
							<dt><?php esc_html_e( 'Last indexed', 'cardoza-hybrid-search' ); ?></dt>
							<dd data-cardoza-hs-stat="last_indexed"><?php echo esc_html( Layout::time_ago( $stats['last_indexed'] ) ); ?></dd>
						</div>
						<div>
							<dt><?php esc_html_e( 'Model', 'cardoza-hybrid-search' ); ?></dt>
							<dd><?php echo esc_html( $model !== '' ? $model : '—' ); ?></dd>
						</div>
						<div>
							<dt><?php esc_html_e( 'Dimensions', 'cardoza-hybrid-search' ); ?></dt>
							<dd><?php echo esc_html( (string) Embeddings::DIMENSIONS ); ?></dd>
						</div>
					</dl>
				</section>
			</aside>
		</div>
		<?php
		Layout::close();
	}

	/**
	 * Build the re-index status block: badge, headline, progress bar and counts.
	 *
	 * Used for the first render and returned by the AJAX step, so the text
	 * and translations live in one place.
	 *
	 * @param array $state Job state from Reindexer::status().
	 * @return string Escaped HTML.
	 */
	public static function status_html( array $state ): string {
		$total     = max( 0, (int) $state['total'] );
		$processed = max( 0, (int) $state['processed'] );
		$percent   = $total > 0 ? (int) min( 100, floor( $processed * 100 / $total ) ) : 0;

		switch ( $state['status'] ) {
			case 'running':
				$badge    = __( 'Running', 'cardoza-hybrid-search' );
				$headline = sprintf(
					/* translators: 1: posts processed, 2: total posts. */
					__( '%1$s of %2$s posts processed', 'cardoza-hybrid-search' ),
					number_format_i18n( $processed ),
					number_format_i18n( $total )
				);
				break;

			case 'done':
				$badge    = $state['failed'] > 0 ? __( 'Finished with errors', 'cardoza-hybrid-search' ) : __( 'Finished', 'cardoza-hybrid-search' );
				$percent  = 100;
				$headline = sprintf(
					/* translators: 1: posts processed, 2: time difference, e.g. "5 mins". */
					__( '%1$s posts processed %2$s ago', 'cardoza-hybrid-search' ),
					number_format_i18n( $processed ),
					human_time_diff( (int) $state['finished_at'] )
				);
				break;

			default:
				$badge    = __( 'Not run yet', 'cardoza-hybrid-search' );
				$headline = __( 'Posts are indexed automatically when saved. Re-index to process all existing content.', 'cardoza-hybrid-search' );
		}

		$counts = array(
			__( 'Indexed', 'cardoza-hybrid-search' ) => (int) $state['indexed'],
			__( 'Removed', 'cardoza-hybrid-search' ) => (int) $state['removed'],
			__( 'Failed', 'cardoza-hybrid-search' )  => (int) $state['failed'],
		);

		$html  = '<span class="cardoza-hs-badge cardoza-hs-badge--' . esc_attr( (string) $state['status'] ) . ( $state['failed'] > 0 ? ' cardoza-hs-badge--errors' : '' ) . '">' . esc_html( $badge ) . '</span>';
		$html .= '<p class="cardoza-hs-reindex__headline">' . esc_html( $headline ) . '</p>';

		if ( $state['status'] !== 'idle' ) {
			$html .= '<div class="cardoza-hs-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . esc_attr( (string) $percent ) . '">'
				. '<span style="width:' . esc_attr( (string) $percent ) . '%"></span></div>';

			$items = array();
			foreach ( $counts as $label => $count ) {
				$items[] = '<li><span>' . esc_html( $label ) . '</span> ' . esc_html( number_format_i18n( $count ) ) . '</li>';
			}
			$html .= '<ul class="cardoza-hs-counts">' . implode( '', $items ) . '</ul>';
		}

		if ( $state['last_error'] !== '' ) {
			$html .= '<p class="cardoza-hs-reindex__error">' . esc_html( $state['last_error'] ) . '</p>';
		}

		return $html;
	}
}
