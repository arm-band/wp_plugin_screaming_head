<?php
/*
Plugin Name: Screaming Head
Description: WordPress の head タグの中を整理するプラグインです(※設定画面付き)。
Version:     0.0.1
Author:      アルム＝バンド
*/


class ScreamingHead
{
    /**
     * const
     */
    const SCREAMINGHEAD                     = 'screaminghead';
    const SCREAMINGHEAD_SETTINGS            = 'Screaming Head 設定';
    const SCREAMINGHEAD_SETTINGS_EN         = 'screaminghead-settings';
    const SCREAMINGHEAD_SETTINGS_VALIDATION = 'screaminghead_validation';
    const SCREAMINGHEAD_SETTINGS_PARAMETERS = 'screaminghead_settings_parameters';
    /**
     * var
     */
    protected $keyArray;

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        $this->keyArray = [
            'wp_version' => [
                'label'       => 'WordPress バージョン',
                'description' => 'name="generator" で出力される WordPress のバージョン情報を非表示にします。',
                'value'       => 1,
            ],
            'shortlink' => [
                'label'       => 'ショートリンクURL',
                'description' => 'ショートリンクURL を非表示にします。',
                'value'       => 1,
            ],
            'wlwmanifest' => [
                'label'       => 'wlwmanifest',
                'description' => 'wlwmanifest を非表示にします。',
                'value'       => 1,
            ],
            'xmlrpc' => [
                'label'       => 'xmlrpc.php',
                'description' => 'xmlrpc.php を非表示にします。',
                'value'       => 1,
            ],
            'dns_prefetch'  => [
                'label'       => 'DNS プリフェッチ',
                'description' => 'DNS プリフェッチ を非表示にします。',
                'value'       => 1,
            ],
            'feed'  => [
                'label'       => 'フィード',
                'description' => 'フィード を非表示にします。(サイト全体フィード、サイト全体コメントフィードを除く)',
                'value'       => 0,
            ],
            'emoji'  => [
                'label'       => '絵文字',
                'description' => '絵文字 用の css や JavaScript を読み込まないようにします。',
                'value'       => 0,
            ],
            'rest_api'  => [
                'label'       => 'REST API',
                'description' => 'REST API を出力しないようにします(oEmbed, Jetpack, ブロックエディタ 除く)。',
                'value'       => 0,
            ],
            'prev_next_article'  => [
                'label'       => '前後の記事',
                'description' => '前後の記事 への rel 属性を出力しないようにします。',
                'value'       => 1,
            ],
        ];

    }

    /**
     * データ読み出し
     *
     * @return array $ANONYMOUS get_option で取得したデータを maybe_unserialize で配列に変換して戻り値にしている
     */
    public function dataRead()
    {
        return maybe_unserialize( get_option( self::SCREAMINGHEAD_SETTINGS_PARAMETERS ) );
    }
    /**
     * 管理者画面にメニューと設定画面を追加
     */
    public function admin_create_page()
    {
        // メニューを追加
        add_action( 'admin_menu', [ $this, 'screaminghead_create_menu' ] );
        // 独自関数をコールバック関数とする
        add_action( 'admin_init', [ $this, 'register_screaminghead_settings' ] );
    }
    /**
     * メニュー追加
     */
    public function screaminghead_create_menu()
    {
        // add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
        //  $page_title : 設定ページの `title` 部分
        //  $menu_title : メニュー名
        //  $capability : 権限 ( 'manage_options' や 'administrator' など)
        //  $menu_slug  : メニューのslug
        //  $function   : 設定ページの出力を行う関数
        //  $icon_url   : メニューに表示するアイコン
        //  $position   : メニューの位置 ( 1 や 99 など )
        add_menu_page(
            self::SCREAMINGHEAD_SETTINGS,
            self::SCREAMINGHEAD_SETTINGS,
            'administrator',
            self::SCREAMINGHEAD,
            [ $this, self::SCREAMINGHEAD . '_settings_page' ],
            'dashicons-album'
        );
    }
    /**
     * コールバック
     */
    public function register_screaminghead_settings()
    {
        // register_setting( $option_group, $option_name, $sanitize_callback )
        //  $option_group      : 設定のグループ名
        //  $option_name       : 設定項目名(DBに保存する名前)
        //  $sanitize_callback : 入力値調整をする際に呼ばれる関数
        register_setting(
            self::SCREAMINGHEAD_SETTINGS_EN,
            self::SCREAMINGHEAD_SETTINGS_PARAMETERS,
            [ $this, self::SCREAMINGHEAD_SETTINGS_VALIDATION ]
        );
    }
    /**
     * バリデーション。コールバックから呼ばれる
     *
     * @param array $newInput 設定画面で入力されたパラメータ
     *
     * @return string $newInput / $ANONYMOUS バリデーションに成功した場合は $newInput そのものを返す。失敗した場合はDBに保存してあった元のデータを get_option で呼び戻す。
     */
    public function screaminghead_validation( $newInput )
    {
        // nonce check
        check_admin_referer( self::SCREAMINGHEAD . '_options', 'name_of_nonce_field' );

        // validation
        $errCnt = 0;
        foreach($newInput as $key => $value) {
            if(preg_match('/^[\d]{1}$/i', $value)) {
                $newInput[$key] = (int) $value;
                if ($newInput[$key] !== 0 && $newInput[$key] !== 1) {
                    $errCnt++;
                }
            }
            else {
                $errCnt++;
            }
        }
        if($errCnt > 0) {
            // add_settings_error( $setting, $code, $message, $type )
            //  $setting : 設定のslug
            //  $code    : エラーコードのslug (HTMLで'setting-error-{$code}'のような形でidが設定されます)
            //  $message : エラーメッセージの内容
            //  $type    : メッセージのタイプ。'updated' (成功) か 'error' (エラー) のどちらか
            add_settings_error(
                self::SCREAMINGHEAD,
                self::SCREAMINGHEAD . '-validation_error',
                __(
                    '設定しようとしたパラメータに不正なデータが含まれています。',
                    self::SCREAMINGHEAD
                ),
                'error'
            );

            return get_option( self::SCREAMINGHEAD_SETTINGS_PARAMETERS );
        }
        else {
            return $newInput;
        }
    }
    /**
     * 設定画面ページの生成
     */
    public function screaminghead_settings_page()
    {
        if ( get_settings_errors( self::SCREAMINGHEAD ) ) {
            // エラーがあった場合はエラーを表示
            settings_errors( self::SCREAMINGHEAD );
        }
        else if( true == $_GET['settings-updated'] ) {
            // 設定変更時にメッセージ表示
?>
            <div id="settings_updated" class="updated notice is-dismissible"><p><strong>設定を保存しました。</strong></p></div>
<?php
        }
?>
        <div class="wrap">
            <h1><?= esc_html( self::SCREAMINGHEAD_SETTINGS ); ?></h1>
            <h2>非表示にする項目の設定</h2>
            <p>以下の一覧から、非表示にしたい項目を選択してください。</p>
            <form method="post" action="options.php">
<?php settings_fields( self::SCREAMINGHEAD_SETTINGS_EN ); ?>
<?php do_settings_sections( self::SCREAMINGHEAD_SETTINGS_EN ); ?>
                <table class="form-table" id="<?= esc_attr( self::SCREAMINGHEAD_SETTINGS_EN ); ?>">
<?php
        $data = self::dataRead();
        foreach ( $this->keyArray as $key => $value ) {
            if (get_option( self::SCREAMINGHEAD_SETTINGS_PARAMETERS ) !== false) {
                // プラグインのパラメータの読み込みができた場合
                // $data にキー $key の値が存在するならばチェックボックスにチェックを入れる
                $checked = array_key_exists($key, $data) && $data[$key] === 1 ? ' checked="checked"' : '';
            }
            else {
                // プラグインのパラメータの読み込みができなかった場合
                // $this->keyArray で保持しているデフォルト値を参照する
                $checked = $value['value'] === 1 ? ' checked="checked"' : '';
            }

?>
        <tr>
            <th>
                <label for="<?= esc_attr( $key ); ?>"><?= esc_html( $value['label'] ); ?></label>
            </th>
            <td>
                <input type="checkbox" id="<?= esc_attr( $key ); ?>" name="<?= esc_attr( self::SCREAMINGHEAD_SETTINGS_PARAMETERS ); ?>[<?= esc_attr( $key ); ?>]" <?= $checked ?> value="1"><br>
                <p><?= esc_html( $value['description'] ); ?></p>
            </td>
        </tr>
<?php
        }
?>
                </table>
<?php wp_nonce_field( self::SCREAMINGHEAD . '_options', 'name_of_nonce_field' ); ?>
<?php submit_button( '設定を保存', 'primary large', 'submit', true, [ 'tabindex' => '1' ] ); ?>
            </form>
        </div>

<?php
    }
    /**
     * メイン処理 (フロント側)
     */
    function time_crunch()
    {
        $data = self::dataRead();
        $flagArray = [];

        foreach( $this->keyArray as $key => $value ) {
            if( get_option( self::SCREAMINGHEAD_SETTINGS_PARAMETERS ) !== false ) {
                // プラグインのパラメータの読み込みができた場合
                // $data にキー $key の値が存在するならば
                $flagArray[$key] = array_key_exists($key, $data) ? $data[$key] : 0;
            }
            else {
                // プラグインのパラメータの読み込みができなかった場合
                // $this->keyArray で保持しているデフォルト値を参照する
                $flagArray[$key] = $value['value'];
            }
        }

        // WordPressバージョン情報の削除
        if( $flagArray['wp_version'] ) {
            remove_action( 'wp_head', 'wp_generator' );
        }
        // ショートリンクURLの削除
        if($flagArray['shortlink'] ) {
            remove_action( 'wp_head', 'wp_shortlink_wp_head' );
        }
        // wlwmanifestの削除
        if ($flagArray['wlwmanifest']) {
            remove_action( 'wp_head', 'wlwmanifest_link' );
        }
        // application/rsd+xmlの削除
        if( $flagArray['xmlrpc'] ) {
            remove_action( 'wp_head', 'rsd_link' );
        }
        // dns-prefetchの削除
        if( $flagArray['dns_prefetch'] ) {
            function remove_dns_prefetch( $hints, $relation_type ) {
                if( 'dns-prefetch' === $relation_type ) {
                    return array_diff( wp_dependencies_unique_hosts(), $hints );
                }
                return $hints;
            }
            add_filter( 'wp_resource_hints', 'remove_dns_prefetch', 10, 2 );
        }
        // フィードの削除
        if( $flagArray['feed'] ) {
            remove_action( 'wp_head', 'feed_links_extra', 3 );
        }
        // 絵文字用css, JSの削除
        if( $flagArray['emoji'] ) {
            remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
            remove_action('wp_print_styles', 'print_emoji_styles', 10);
            remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
            remove_action( 'wp_print_styles', 'print_emoji_styles' );
            remove_action( 'admin_print_styles', 'print_emoji_styles' );
            remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
            remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
            remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
            add_filter( 'emoji_svg_url', '__return_false' );
        }
        // REST API
        if( $flagArray['rest_api'] ) {
            function deny_restapi_except_plugins( $result, $wp_rest_server, $request ) {
                $namespaces = $request->get_route();
                // oembed
                if( strpos( $namespaces, 'oembed/' ) === 1 ) {
                    return $result;
                }
                // Jetpack
                if( strpos( $namespaces, 'jetpack/' ) === 1 ) {
                    return $result;
                }
                // BlockEditor
                if( current_user_can( 'edit_posts' ) ) {
                    return $result;
                }
                return new WP_Error( 'rest_disabled', __( 'The REST API on this site has been disabled.' ), array( 'status' => rest_authorization_required_code() ) );
            }
            add_filter( 'rest_pre_dispatch', 'deny_restapi_except_plugins', 10, 3 );
        }
        // 前後記事
        if( $flagArray['prev_next_article'] ) {
            remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );
        }
    }
}

// 処理
$wp_ab_screaminghead = new ScreamingHead();

if( is_admin() ) {
    // 管理者画面を表示している場合のみ実行
    $wp_ab_screaminghead->admin_create_page();
}
else {
    // 管理者画面以外
    $wp_ab_screaminghead->time_crunch();
}
