<?php
/**
 * As configurações básicas do WordPress
 *
 * O script de criação wp-config.php usa esse arquivo durante a instalação.
 * Você não precisa usar o site, você pode copiar este arquivo
 * para "wp-config.php" e preencher os valores.
 *
 * Este arquivo contém as seguintes configurações:
 *
 * * Configurações do MySQL
 * * Chaves secretas
 * * Prefixo do banco de dados
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Configurações do MySQL - Você pode pegar estas informações com o serviço de hospedagem ** //
/** O nome do banco de dados do WordPress */
define( 'DB_NAME', 'wordpress' );

/** Usuário do banco de dados MySQL */
define( 'DB_USER', 'root' );

/** Senha do banco de dados MySQL */
define( 'DB_PASSWORD', '' );

/** Nome do host do MySQL */
define( 'DB_HOST', 'localhost' );

/** Charset do banco de dados a ser usado na criação das tabelas. */
define( 'DB_CHARSET', 'utf8mb4' );

/** O tipo de Collate do banco de dados. Não altere isso se tiver dúvidas. */
define( 'DB_COLLATE', '' );

/**#@+
 * Chaves únicas de autenticação e salts.
 *
 * Altere cada chave para um frase única!
 * Você pode gerá-las
 * usando o {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org
 * secret-key service}
 * Você pode alterá-las a qualquer momento para invalidar quaisquer
 * cookies existentes. Isto irá forçar todos os
 * usuários a fazerem login novamente.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         ']8Zz%*=Ir4Ac.GTb:p8jWrpOzRz+LeZvui@DyWPo|Iw-xiGOTY((^EKDd`Z,FmB;' );
define( 'SECURE_AUTH_KEY',  '1=mr$4^,GD`p3uS|C!v1lmP#.U8m]x1$<_hFr7s2OgXk?!uc)<x )@[yyXMbZCBQ' );
define( 'LOGGED_IN_KEY',    '2(v-J(F/{D?7/xUrjYK5aXa]Xn[02!KNtq:|j5Yo7u_o*&q4u5AKAYK.vILUb X|' );
define( 'NONCE_KEY',        '^vKlAgfAtncOQt0#yum;O,?1F@sa[L&v 52w7&wDX/pYHk;)6i<u1{BKJ6r;}nRw' );
define( 'AUTH_SALT',        'Zh~18qg]Fnl=9h)`.a<d6g`P(|bCfg>$q!-]~bZ_&1^Id@X_nYGOK%|jrzi3^a.j' );
define( 'SECURE_AUTH_SALT', '@?9Rxwn[8-IoiWJ^3ONF<xuxTWK<s ;{oZERUywkKL5^<=Yn8KO5(2ZLe1wIQm(#' );
define( 'LOGGED_IN_SALT',   '3@6&>7,rBz)A0Mg?zRuFl+$]YeVI8=YSQ=0H6U}N%uuXd]0l;Zj&]~E@n]Ac`<`Z' );
define( 'NONCE_SALT',       ' -yh9NXapuS%rU?rN7_QT!V |}2h/C+k*4Hmk[c@(2[<bkyD.~7xjtl3[Ox%~*s<' );

/**#@-*/

/**
 * Prefixo da tabela do banco de dados do WordPress.
 *
 * Você pode ter várias instalações em um único banco de dados se você der
 * um prefixo único para cada um. Somente números, letras e sublinhados!
 */
$table_prefix = 'wp_';

/**
 * Para desenvolvedores: Modo de debug do WordPress.
 *
 * Altere isto para true para ativar a exibição de avisos
 * durante o desenvolvimento. É altamente recomendável que os
 * desenvolvedores de plugins e temas usem o WP_DEBUG
 * em seus ambientes de desenvolvimento.
 *
 * Para informações sobre outras constantes que podem ser utilizadas
 * para depuração, visite o Codex.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* Isto é tudo, pode parar de editar! :) */

/** Caminho absoluto para o diretório WordPress. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Configura as variáveis e arquivos do WordPress. */
require_once ABSPATH . 'wp-settings.php';

define( 'FS_METHOD', 'direct' );
