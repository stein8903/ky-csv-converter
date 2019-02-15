<?php
/**
 * WordPress の基本設定
 *
 * このファイルは、インストール時に wp-config.php 作成ウィザードが利用します。
 * ウィザードを介さずにこのファイルを "wp-config.php" という名前でコピーして
 * 直接編集して値を入力してもかまいません。
 *
 * このファイルは、以下の設定を含みます。
 *
 * * MySQL 設定
 * * 秘密鍵
 * * データベーステーブル接頭辞
 * * ABSPATH
 *
 * @link http://wpdocs.osdn.jp/wp-config.php_%E3%81%AE%E7%B7%A8%E9%9B%86
 *
 * @package WordPress
 */





define("ALLOW_UNFILTERED_UPLOADS", true ); 




// 注意:
// Windows の "メモ帳" でこのファイルを編集しないでください !
// 問題なく使えるテキストエディタ
// (http://wpdocs.osdn.jp/%E7%94%A8%E8%AA%9E%E9%9B%86#.E3.83.86.E3.82.AD.E3.82.B9.E3.83.88.E3.82.A8.E3.83.87.E3.82.A3.E3.82.BF 参照)
// を使用し、必ず UTF-8 の BOM なし (UTF-8N) で保存してください。

// ** MySQL 設定 - この情報はホスティング先から入手してください。 ** //
/** WordPress のためのデータベース名 */
define('DB_NAME', 'LAA0230611-kcc');

/** MySQL データベースのユーザー名 */
define('DB_USER', 'LAA0230611');

/** MySQL データベースのパスワード */
define('DB_PASSWORD', 'zmfl8903');

/** MySQL のホスト名 */
define('DB_HOST', 'mysql134.phy.lolipop.lan');

/** データベースのテーブルを作成する際のデータベースの文字セット */
define('DB_CHARSET', 'utf8');

/** データベースの照合順序 (ほとんどの場合変更する必要はありません) */
define('DB_COLLATE', '');

/**#@+
 * 認証用ユニークキー
 *
 * それぞれを異なるユニーク (一意) な文字列に変更してください。
 * {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org の秘密鍵サービス} で自動生成することもできます。
 * 後でいつでも変更して、既存のすべての cookie を無効にできます。これにより、すべてのユーザーを強制的に再ログインさせることになります。
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'oa_PGbfu~IP^H.!8^u8nxEE_T;GxiBz*{{,7!FY5.*#9gr+@{*|70,YL3Fu=2hU=');
define('SECURE_AUTH_KEY',  '*ORAiL3qp{tbU|L@y1_G,5-O?z*Q5tjf;4asCj5*BUKa8B3sl0D^T}hjc y2:Ork');
define('LOGGED_IN_KEY',    '7UwF-C7OxC,LS,d55:fSo,M> r}d2FYcKnWEMB`nd>2{,+i[@_& 5q:88<DVE)nB');
define('NONCE_KEY',        'TDFsZ@&WpXRL{Kp6}GMsdX<U_ScujP@1BIQs^,/NBdX9Bjl#gn(>}<(4fD|QMvl5');
define('AUTH_SALT',        '<9^h<ow2ye{ ,_/W#JBHYrV}w}D0l@:eagrp.@8l`sfOj7gSNxT8/M2q3wKv{&vm');
define('SECURE_AUTH_SALT', ':~Jb,hvTby*{8K2>}@D2O4*uY32;(#|A>5;k]?{:iJ92in@v~kp)S(y9TQfN8+7K');
define('LOGGED_IN_SALT',   'd9en[6s?9lyNw}]IJM{nwl*U3lmsCQ8QU;UFbN$3.Cer&;(/4>=]vx@p5CPHF;OJ');
define('NONCE_SALT',       '(Ep3fc[(v%Dljtb*|ftBB[8vo+iI!}h4ME`Mz1{D}~X`!PJ#j@]> )W.UN_AT`)/');

/**#@-*/

/**
 * WordPress データベーステーブルの接頭辞
 *
 * それぞれにユニーク (一意) な接頭辞を与えることで一つのデータベースに複数の WordPress を
 * インストールすることができます。半角英数字と下線のみを使用してください。
 */
$table_prefix  = 'wp_';

/**
 * 開発者へ: WordPress デバッグモード
 *
 * この値を true にすると、開発中に注意 (notice) を表示します。
 * テーマおよびプラグインの開発者には、その開発環境においてこの WP_DEBUG を使用することを強く推奨します。
 *
 * その他のデバッグに利用できる定数については Codex をご覧ください。
 *
 * @link http://wpdocs.osdn.jp/WordPress%E3%81%A7%E3%81%AE%E3%83%87%E3%83%90%E3%83%83%E3%82%B0
 */
define('WP_DEBUG', false);

/* 編集が必要なのはここまでです ! WordPress でブログをお楽しみください。 */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
