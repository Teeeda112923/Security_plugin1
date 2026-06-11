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

		wp_enqueue_style(
			'sp1-admin',
			SP1_URL . 'assets/css/admin.css',
			array(),
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
		$overall     = $this->get_overall_status( $diagnostics );
		?>
		<div class="wrap sp1-admin">
			<h1><?php echo esc_html__( 'Security Plugin 1', 'security-plugin1' ); ?></h1>
			<p class="sp1-admin__lead">
				<?php echo esc_html__( 'WordPressの設定を確認し、現在の状態と必要な対処を分かりやすく表示します。診断によって設定が自動で変更されることはありません。', 'security-plugin1' ); ?>
			</p>

			<section class="sp1-overview sp1-overview--<?php echo esc_attr( sanitize_html_class( $overall['status'] ) ); ?>" aria-label="<?php echo esc_attr__( '診断結果の概要', 'security-plugin1' ); ?>">
				<span class="dashicons dashicons-shield-alt" aria-hidden="true"></span>
				<div>
					<span class="sp1-overview__caption"><?php echo esc_html__( '現在の診断結果', 'security-plugin1' ); ?></span>
					<strong><?php echo esc_html( $overall['label'] ); ?></strong>
				</div>
			</section>

			<div class="sp1-diagnostics">
				<?php foreach ( $diagnostics as $diagnostic ) : ?>
					<?php $status_class = sanitize_html_class( $diagnostic['status'] ); ?>
					<article class="sp1-card sp1-card--<?php echo esc_attr( $status_class ); ?>">
						<header class="sp1-card__header">
							<span class="sp1-status sp1-status--<?php echo esc_attr( $status_class ); ?>">
								<?php echo esc_html( $diagnostic['label'] ); ?>
							</span>
							<h2><?php echo esc_html( $diagnostic['title'] ); ?></h2>
						</header>

						<p class="sp1-card__summary"><?php echo esc_html( $diagnostic['summary'] ); ?></p>

						<div class="sp1-card__sections">
							<section>
								<h3><?php echo esc_html__( '放置した場合に考えられること', 'security-plugin1' ); ?></h3>
								<p><?php echo esc_html( $diagnostic['risk'] ); ?></p>
							</section>

							<section>
								<h3><?php echo esc_html__( '推奨する対処', 'security-plugin1' ); ?></h3>
								<p><?php echo esc_html( $diagnostic['action'] ); ?></p>
							</section>

							<section>
								<h3><?php echo esc_html__( '設定変更による影響', 'security-plugin1' ); ?></h3>
								<p><?php echo esc_html( $diagnostic['impact'] ); ?></p>
							</section>
						</div>

						<details class="sp1-technical">
							<summary><?php echo esc_html__( '詳しく見る', 'security-plugin1' ); ?></summary>
							<dl>
								<?php foreach ( $diagnostic['technical'] as $item ) : ?>
									<div>
										<dt><?php echo esc_html( $item['label'] ); ?></dt>
										<dd><?php echo esc_html( $item['value'] ); ?></dd>
									</div>
								<?php endforeach; ?>
							</dl>
						</details>
					</article>
				<?php endforeach; ?>
			</div>

			<p class="sp1-admin__note">
				<?php echo esc_html__( 'この診断は現在の設定状態を確認するもので、すべての攻撃や被害を防ぐことを保証するものではありません。', 'security-plugin1' ); ?>
			</p>
		</div>
		<?php
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
					'label'  => $diagnostic['label'],
				);
			}
		}

		return $overall;
	}
}
