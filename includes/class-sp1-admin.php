<?php
/**
 * WordPress admin screen.
 *
 * @package Security_Plugin1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and renders the Security Plugin 1 admin page.
 */
final class SP1_Admin {

	/**
	 * Diagnostics service.
	 *
	 * @var SP1_Diagnostics
	 */
	private $diagnostics;

	/**
	 * Admin page hook suffix.
	 *
	 * @var string
	 */
	private $page_hook = '';

	/**
	 * Constructor.
	 *
	 * @param SP1_Diagnostics $diagnostics Diagnostics service.
	 */
	public function __construct( SP1_Diagnostics $diagnostics ) {
		$this->diagnostics = $diagnostics;

		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Registers the top-level admin menu.
	 *
	 * @return void
	 */
	public function register_menu() {
		$this->page_hook = add_menu_page(
			__( 'Security Plugin 1', 'security-plugin1' ),
			__( 'セキュリティ診断', 'security-plugin1' ),
			'manage_options',
			'security-plugin1',
			array( $this, 'render_page' ),
			'dashicons-shield-alt',
			80
		);
	}

	/**
	 * Loads CSS only on this plugin's admin page.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( $hook_suffix !== $this->page_hook ) {
			return;
		}

		wp_enqueue_style( 'dashicons' );

		wp_enqueue_style(
			'sp1-admin',
			SP1_URL . 'assets/css/admin.css',
			array( 'dashicons' ),
			SP1_VERSION
		);
	}

	/**
	 * Renders the diagnostics page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'このページを表示する権限がありません。', 'security-plugin1' ) );
		}

		$diagnostics = $this->diagnostics->get_diagnostics();
		$counts      = $this->get_status_counts( $diagnostics );
		$total       = count( $diagnostics );
		$issues      = $counts['recommended'] + $counts['attention'];
		$overall     = $this->get_overall_status( $diagnostics );
		$priority    = $this->get_priority_items( $diagnostics, 3 );
		$ring_degrees = $total > 0 ? (int) round( min( 1, $issues / $total ) * 360 ) : 0;
		?>
		<div class="wrap sp1-admin">
			<div class="sp1-page-toolbar">
				<div>
					<h1 class="sp1-admin__title">
						<span class="dashicons dashicons-shield-alt" aria-hidden="true"></span>
						<?php echo esc_html__( 'セキュリティ診断', 'security-plugin1' ); ?>
					</h1>
					<p class="sp1-admin__lead">
						<?php echo esc_html__( 'WordPressの設定を確認し、現在の状態と必要な対処を分かりやすく表示します。診断によって設定が自動で変更されることはありません。', 'security-plugin1' ); ?>
					</p>
				</div>
				<div class="sp1-toolbar-actions">
					<span class="sp1-last-scan">
						<span class="dashicons dashicons-clock" aria-hidden="true"></span>
						<?php
						echo esc_html(
							sprintf(
								/* translators: %s: current date and time. */
								__( '最終診断: %s', 'security-plugin1' ),
								wp_date( 'Y/m/d H:i' )
							)
						);
						?>
					</span>
					<a class="button sp1-refresh-button" href="<?php echo esc_url( admin_url( 'admin.php?page=security-plugin1' ) ); ?>">
						<span class="dashicons dashicons-update" aria-hidden="true"></span>
						<?php echo esc_html__( '再診断する', 'security-plugin1' ); ?>
					</a>
				</div>
			</div>

			<section class="sp1-hero sp1-hero--<?php echo esc_attr( sanitize_html_class( $overall['status'] ) ); ?>" aria-label="<?php echo esc_attr__( '診断結果の概要', 'security-plugin1' ); ?>">
				<div class="sp1-hero__ring" style="--sp1-ring-degrees: <?php echo esc_attr( $ring_degrees ); ?>deg;">
					<div class="sp1-hero__ring-inner">
						<span><?php echo esc_html__( '確認が必要な項目', 'security-plugin1' ); ?></span>
						<strong><?php echo esc_html( $issues ); ?> / <?php echo esc_html( $total ); ?></strong>
						<small><?php echo esc_html__( '項目', 'security-plugin1' ); ?></small>
					</div>
				</div>
				<div class="sp1-hero__copy">
					<h2><?php echo esc_html__( 'サイトのセキュリティ状態', 'security-plugin1' ); ?></h2>
					<p>
						<?php
						if ( $issues > 0 ) {
							echo esc_html__( '確認が必要な項目があります。まずは優先対応から確認しましょう。', 'security-plugin1' );
						} else {
							echo esc_html__( '現在、すぐに確認が必要な項目はありません。', 'security-plugin1' );
						}
						?>
					</p>
				</div>
				<div class="sp1-hero__counts">
					<?php $this->render_count_card( 'recommended', __( '要対応', 'security-plugin1' ), $counts['recommended'], 'dashicons-warning' ); ?>
					<?php $this->render_count_card( 'attention', __( '改善推奨', 'security-plugin1' ), $counts['attention'], 'dashicons-warning' ); ?>
					<?php $this->render_count_card( 'good', __( '問題なし', 'security-plugin1' ), $counts['good'], 'dashicons-yes-alt' ); ?>
				</div>
			</section>

			<section class="sp1-priority sp1-card-shell" aria-label="<?php echo esc_attr__( '優先対応が必要な項目', 'security-plugin1' ); ?>">
				<div class="sp1-section-heading">
					<span class="sp1-section-icon sp1-section-icon--recommended dashicons dashicons-warning" aria-hidden="true"></span>
					<div>
						<h2><?php echo esc_html__( '優先対応が必要な項目', 'security-plugin1' ); ?></h2>
						<p><?php echo esc_html__( '放置するとリスクが高まる可能性があります。できるだけ早く確認してください。', 'security-plugin1' ); ?></p>
					</div>
				</div>

				<?php if ( empty( $priority ) ) : ?>
					<div class="sp1-empty-state">
						<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
						<p><?php echo esc_html__( 'すぐに対応が必要な項目はありません。', 'security-plugin1' ); ?></p>
					</div>
				<?php else : ?>
					<div class="sp1-priority-list">
						<?php foreach ( $priority as $diagnostic ) : ?>
							<?php $this->render_diagnostic_row( $diagnostic, true ); ?>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</section>

			<div class="sp1-content-grid">
				<section class="sp1-diagnostics-panel sp1-card-shell">
					<div class="sp1-section-heading sp1-section-heading--compact">
						<span class="sp1-section-icon sp1-section-icon--blue dashicons dashicons-list-view" aria-hidden="true"></span>
						<div>
							<h2><?php echo esc_html__( '診断結果', 'security-plugin1' ); ?></h2>
							<p><?php echo esc_html__( '各項目の状態と推奨される確認内容です。', 'security-plugin1' ); ?></p>
						</div>
					</div>

					<div class="sp1-diagnostics-list">
						<?php foreach ( $diagnostics as $diagnostic ) : ?>
							<?php $this->render_diagnostic_row( $diagnostic, false ); ?>
						<?php endforeach; ?>
					</div>
				</section>

				<section class="sp1-pro-panel sp1-card-shell" aria-label="<?php echo esc_attr__( '脆弱性アラート Pro の案内', 'security-plugin1' ); ?>">
					<div class="sp1-pro-label">
						<span class="dashicons dashicons-lock" aria-hidden="true"></span>
						<?php echo esc_html__( '脆弱性アラート Pro', 'security-plugin1' ); ?>
						<span class="sp1-pro-badge">Pro</span>
					</div>
					<h2><?php echo esc_html__( '更新の「緊急度」まで知りたい方へ', 'security-plugin1' ); ?></h2>
					<p><?php echo esc_html__( '無料版では、サイト内の設定状態を診断できます。Proではさらに、使用中のプラグイン・テーマを既知の脆弱性情報と照合し、どれを優先して対応すべきかを日本語で確認できます。', 'security-plugin1' ); ?></p>

					<div class="sp1-cve-preview" aria-label="<?php echo esc_attr__( '表示例', 'security-plugin1' ); ?>">
						<div class="sp1-cve-preview__row">
							<strong><?php echo esc_html__( 'フォームプラグインA', 'security-plugin1' ); ?></strong>
							<span class="sp1-severity sp1-severity--high"><?php echo esc_html__( '深刻度: 高', 'security-plugin1' ); ?></span>
							<small><?php echo esc_html__( '早めの更新を推奨', 'security-plugin1' ); ?></small>
						</div>
						<div class="sp1-cve-preview__row">
							<strong><?php echo esc_html__( 'SEO補助プラグインB', 'security-plugin1' ); ?></strong>
							<span class="sp1-severity sp1-severity--middle"><?php echo esc_html__( '深刻度: 中', 'security-plugin1' ); ?></span>
							<small><?php echo esc_html__( '更新内容を確認', 'security-plugin1' ); ?></small>
						</div>
						<p class="sp1-cve-preview__note"><?php echo esc_html__( '※これは表示例です。実際の診断結果ではありません。', 'security-plugin1' ); ?></p>
					</div>

					<p class="sp1-pro-note"><?php echo esc_html__( 'この機能は外部サービスとして提供されます。無料版のプラグイン内には、CVE照合機能やライセンス解除機能は含まれていません。', 'security-plugin1' ); ?></p>
					<a class="button button-primary sp1-pro-button" href="<?php echo esc_url( 'https://www.cybernote.click/' ); ?>" target="_blank" rel="noopener noreferrer">
						<?php echo esc_html__( 'CVEアラートを見る', 'security-plugin1' ); ?>
						<span class="dashicons dashicons-external" aria-hidden="true"></span>
					</a>
				</section>
			</div>

			<p class="sp1-admin__note">
				<span class="dashicons dashicons-info" aria-hidden="true"></span>
				<?php echo esc_html__( 'このプラグインは診断と情報提供に特化しています。設定の変更や更新の実行は行いません。', 'security-plugin1' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Renders one count card.
	 *
	 * @param string $status Status key.
	 * @param string $label  Display label.
	 * @param int    $count  Count.
	 * @param string $icon   Dashicons slug.
	 * @return void
	 */
	private function render_count_card( $status, $label, $count, $icon ) {
		?>
		<div class="sp1-count-card sp1-count-card--<?php echo esc_attr( sanitize_html_class( $status ) ); ?>">
			<span class="dashicons <?php echo esc_attr( sanitize_html_class( $icon ) ); ?>" aria-hidden="true"></span>
			<span><?php echo esc_html( $label ); ?></span>
			<strong><?php echo esc_html( (string) $count ); ?><?php echo esc_html__( '件', 'security-plugin1' ); ?></strong>
		</div>
		<?php
	}

	/**
	 * Renders one diagnostic row.
	 *
	 * @param array<string, mixed> $diagnostic Diagnostic item.
	 * @param bool                $compact    Whether to render a compact row.
	 * @return void
	 */
	private function render_diagnostic_row( $diagnostic, $compact ) {
		$status = isset( $diagnostic['status'] ) ? sanitize_html_class( $diagnostic['status'] ) : 'good';
		$anchor = $this->get_diagnostic_anchor( $diagnostic );
		?>
		<article id="<?php echo esc_attr( $anchor ); ?>" class="sp1-result-row sp1-result-row--<?php echo esc_attr( $status ); ?>">
			<div class="sp1-result-row__icon">
				<span class="dashicons <?php echo esc_attr( sanitize_html_class( $this->get_topic_icon( $diagnostic ) ) ); ?>" aria-hidden="true"></span>
			</div>
			<div class="sp1-result-row__body">
				<div class="sp1-result-row__titleline">
					<h3><?php echo esc_html( isset( $diagnostic['title'] ) ? $diagnostic['title'] : '' ); ?></h3>
					<span class="sp1-status sp1-status--<?php echo esc_attr( $status ); ?>"><?php echo esc_html( $this->get_status_label( $status ) ); ?></span>
				</div>
				<p><?php echo esc_html( isset( $diagnostic['summary'] ) ? $diagnostic['summary'] : '' ); ?></p>

				<?php if ( ! $compact ) : ?>
					<div class="sp1-result-row__details">
						<section>
							<h4><?php echo esc_html__( '放置した場合', 'security-plugin1' ); ?></h4>
							<p><?php echo esc_html( isset( $diagnostic['risk'] ) ? $diagnostic['risk'] : '' ); ?></p>
						</section>
						<section>
							<h4><?php echo esc_html__( '推奨する対処', 'security-plugin1' ); ?></h4>
							<p><?php echo esc_html( isset( $diagnostic['action'] ) ? $diagnostic['action'] : '' ); ?></p>
						</section>
					</div>
					<?php if ( ! empty( $diagnostic['technical'] ) && is_array( $diagnostic['technical'] ) ) : ?>
						<details class="sp1-technical">
							<summary><?php echo esc_html__( '技術情報を見る', 'security-plugin1' ); ?></summary>
							<dl>
								<?php foreach ( $diagnostic['technical'] as $item ) : ?>
									<div>
										<dt><?php echo esc_html( isset( $item['label'] ) ? $item['label'] : '' ); ?></dt>
										<dd><?php echo esc_html( isset( $item['value'] ) ? $item['value'] : '' ); ?></dd>
									</div>
								<?php endforeach; ?>
							</dl>
						</details>
					<?php endif; ?>
				<?php endif; ?>
			</div>
			<?php if ( $compact ) : ?>
				<a class="sp1-row-action" href="#<?php echo esc_attr( $anchor ); ?>">
					<?php echo esc_html__( '詳細を確認する', 'security-plugin1' ); ?>
					<span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
				</a>
			<?php endif; ?>
		</article>
		<?php
	}

	/**
	 * Returns status counts.
	 *
	 * @param array<int, array<string, mixed>> $diagnostics Diagnostic results.
	 * @return array{good:int, attention:int, recommended:int}
	 */
	private function get_status_counts( $diagnostics ) {
		$counts = array(
			'good'        => 0,
			'attention'   => 0,
			'recommended' => 0,
		);

		foreach ( $diagnostics as $diagnostic ) {
			$status = isset( $diagnostic['status'] ) ? $diagnostic['status'] : 'good';
			if ( isset( $counts[ $status ] ) ) {
				$counts[ $status ]++;
			}
		}

		return $counts;
	}

	/**
	 * Returns priority diagnostic items.
	 *
	 * @param array<int, array<string, mixed>> $diagnostics Diagnostic results.
	 * @param int                             $limit       Maximum item count.
	 * @return array<int, array<string, mixed>>
	 */
	private function get_priority_items( $diagnostics, $limit ) {
		$items = array();

		foreach ( array( 'recommended', 'attention' ) as $target_status ) {
			foreach ( $diagnostics as $diagnostic ) {
				if ( isset( $diagnostic['status'] ) && $target_status === $diagnostic['status'] ) {
					$items[] = $diagnostic;
					if ( count( $items ) >= $limit ) {
						return $items;
					}
				}
			}
		}

		return $items;
	}

	/**
	 * Returns the most important status among all diagnostics.
	 *
	 * @param array<int, array<string, mixed>> $diagnostics Diagnostic results.
	 * @return array{status: string, label: string}
	 */
	private function get_overall_status( $diagnostics ) {
		$priority = array(
			'good'        => 1,
			'attention'   => 2,
			'recommended' => 3,
		);

		$overall = array(
			'status' => 'good',
			'label'  => __( '問題なし', 'security-plugin1' ),
		);

		foreach ( $diagnostics as $diagnostic ) {
			$current_priority = isset( $priority[ $diagnostic['status'] ] ) ? $priority[ $diagnostic['status'] ] : 0;
			$overall_priority = isset( $priority[ $overall['status'] ] ) ? $priority[ $overall['status'] ] : 0;

			if ( $current_priority > $overall_priority ) {
				$overall = array(
					'status' => $diagnostic['status'],
					'label'  => $this->get_status_label( $diagnostic['status'] ),
				);
			}
		}

		return $overall;
	}

	/**
	 * Returns the UI label for a status.
	 *
	 * @param string $status Status key.
	 * @return string
	 */
	private function get_status_label( $status ) {
		$labels = array(
			'good'        => __( '問題なし', 'security-plugin1' ),
			'attention'   => __( '改善推奨', 'security-plugin1' ),
			'recommended' => __( '要対応', 'security-plugin1' ),
		);

		return isset( $labels[ $status ] ) ? $labels[ $status ] : $labels['good'];
	}

	/**
	 * Returns a stable anchor for one diagnostic.
	 *
	 * @param array<string, mixed> $diagnostic Diagnostic item.
	 * @return string
	 */
	private function get_diagnostic_anchor( $diagnostic ) {
		$id = isset( $diagnostic['id'] ) ? $diagnostic['id'] : 'item';

		return 'sp1-diagnostic-' . sanitize_html_class( $id );
	}

	/**
	 * Returns a Dashicons slug for one diagnostic.
	 *
	 * @param array<string, mixed> $diagnostic Diagnostic item.
	 * @return string
	 */
	private function get_topic_icon( $diagnostic ) {
		$id = isset( $diagnostic['id'] ) ? $diagnostic['id'] : '';

		if ( 'debug-settings' === $id ) {
			return 'dashicons-editor-code';
		}

		return 'dashicons-shield-alt';
	}
}
