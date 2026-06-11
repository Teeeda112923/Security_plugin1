<?php
/**
 * Security diagnostics.
 *
 * @package Security_Plugin1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Runs lightweight WordPress security diagnostics.
 */
final class SP1_Diagnostics {

	/**
	 * Returns all available diagnostics.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_diagnostics() {
		return array(
			$this->get_debug_settings_diagnostic(),
		);
	}

	/**
	 * Diagnoses WP_DEBUG and WP_DEBUG_DISPLAY.
	 *
	 * Status rules:
	 * - good: WP_DEBUG is disabled.
	 * - attention: WP_DEBUG is enabled and WP_DEBUG_DISPLAY is disabled.
	 * - recommended: WP_DEBUG and WP_DEBUG_DISPLAY are both enabled.
	 *
	 * @return array<string, mixed>
	 */
	public function get_debug_settings_diagnostic() {
		$wp_debug_defined         = defined( 'WP_DEBUG' );
		$wp_debug                 = $wp_debug_defined ? (bool) constant( 'WP_DEBUG' ) : false;
		$wp_debug_display_defined = defined( 'WP_DEBUG_DISPLAY' );
		$wp_debug_display         = $wp_debug_display_defined ? (bool) constant( 'WP_DEBUG_DISPLAY' ) : true;
		$is_display_effective     = $wp_debug && $wp_debug_display;

		$result = array(
			'id'      => 'debug-settings',
			'status'  => 'good',
			'label'   => __( '問題なし', 'security-plugin1' ),
			'title'   => __( 'デバッグ情報は公開されない設定です', 'security-plugin1' ),
			'summary' => __( 'WP_DEBUGが無効のため、WordPressのエラーや警告がサイト訪問者の画面に表示される状態ではありません。', 'security-plugin1' ),
			'risk'    => __( '現在の設定では、デバッグ情報が原因でサーバー内のパスやプラグイン名などが画面に表示される可能性は低くなっています。', 'security-plugin1' ),
			'action'  => __( '公開中のサイトでは、現在の設定を維持してください。調査のためにデバッグ機能を有効にする場合は、作業後に必ず無効へ戻してください。', 'security-plugin1' ),
			'impact'  => __( '設定変更は不要です。デバッグ機能を無効にしていても、通常のサイト表示や管理画面の操作には影響しません。', 'security-plugin1' ),
		);

		if ( $wp_debug && ! $wp_debug_display ) {
			$result['status']  = 'attention';
			$result['label']   = __( '要確認', 'security-plugin1' );
			$result['title']   = __( 'デバッグ機能が有効になっています', 'security-plugin1' );
			$result['summary'] = __( 'WP_DEBUGは有効ですが、WP_DEBUG_DISPLAYは無効です。エラーや警告は通常、サイト訪問者の画面には表示されません。', 'security-plugin1' );
			$result['risk']    = __( '公開中のサイトでデバッグ機能を常時有効にすると、ログの増加や予期しない情報の記録につながる場合があります。開発や調査のための一時的な設定か確認してください。', 'security-plugin1' );
			$result['action']  = __( '開発・調査中でなければ、wp-config.phpのWP_DEBUGをfalseに変更することを検討してください。調査中の場合は、作業終了後に無効へ戻してください。', 'security-plugin1' );
			$result['impact']  = __( 'WP_DEBUGを無効にすると、WordPressが出力する開発者向けの警告や通知を確認しにくくなります。問題調査中は、必要なログを確認してから変更してください。', 'security-plugin1' );
		} elseif ( $is_display_effective ) {
			$result['status']  = 'recommended';
			$result['label']   = __( '対処を推奨', 'security-plugin1' );
			$result['title']   = __( 'デバッグ情報が画面に表示される可能性があります', 'security-plugin1' );
			$result['summary'] = __( 'WP_DEBUGとWP_DEBUG_DISPLAYが有効です。WordPressでエラーや警告が発生した際、内容がサイト訪問者の画面に表示される可能性があります。', 'security-plugin1' );
			$result['risk']    = __( 'エラー内容には、サーバー内のファイルパス、テーマ名、プラグイン名、処理の詳細などが含まれる場合があります。これらは攻撃の手がかりになる可能性があります。', 'security-plugin1' );
			$result['action']  = __( '公開中のサイトでは、wp-config.phpのWP_DEBUGをfalseにしてください。調査のためにWP_DEBUGを有効にする場合でも、WP_DEBUG_DISPLAYはfalseにし、画面へ表示しない運用を推奨します。', 'security-plugin1' );
			$result['impact']  = __( '設定を無効にすると、画面上でPHPの警告やエラーを直接確認できなくなります。開発中のサイトでは、必要に応じて安全なログ出力を利用してください。', 'security-plugin1' );
		}

		$result['technical'] = array(
			array(
				'label' => 'WP_DEBUG',
				'value' => $this->format_constant_state( $wp_debug_defined, $wp_debug, false ),
			),
			array(
				'label' => 'WP_DEBUG_DISPLAY',
				'value' => $this->format_constant_state( $wp_debug_display_defined, $wp_debug_display, true ),
			),
			array(
				'label' => __( '画面表示の実効状態', 'security-plugin1' ),
				'value' => $is_display_effective ? __( '有効', 'security-plugin1' ) : __( '無効', 'security-plugin1' ),
			),
		);

		return $result;
	}

	/**
	 * Formats a Boolean constant for display.
	 *
	 * @param bool $is_defined    Whether the constant is defined.
	 * @param bool $value         Effective Boolean value.
	 * @param bool $default_value WordPress default value when undefined.
	 * @return string
	 */
	private function format_constant_state( $is_defined, $value, $default_value ) {
		if ( $is_defined ) {
			return $value
				? __( '有効（true）', 'security-plugin1' )
				: __( '無効（false）', 'security-plugin1' );
		}

		return $default_value
			? __( '未定義（既定値はtrue）', 'security-plugin1' )
			: __( '未定義（既定値はfalse）', 'security-plugin1' );
	}
}
