<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'www.startthema.test' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'KE^b%)>w|-{EcA{z`|kZLg;R)/+lzB1o#cut9p]CS[g;g*7H4s|Gw[&M`Ea|lV4p' );
define( 'SECURE_AUTH_KEY',  '`LVUBa&5r:|pTjl;L#qqc}ummP_YeBaYZH5Cc?_Dd@8tvZhV0^fMw8T4lM,,5!!g' );
define( 'LOGGED_IN_KEY',    'SH:wi>op,Z4APJa*:JkHtdol(%^&7=XKn]8Jr~B0LR1X,Y>}]:|19yUs_m5=B+bm' );
define( 'NONCE_KEY',        'm|.!2/bsN36:c^J#q@8K_o^k8@p}o:=oxFvI.v0>{?0y^~_XsG4@]|Ebi9d,~2Y)' );
define( 'AUTH_SALT',        '_Ba/_?czN*//0IYx$/q3^4!L9<tf&!{a3fKP,.s4rg&t$y> 07-o/g/?B_B29Sv1' );
define( 'SECURE_AUTH_SALT', 'R.7e>B2r)cyC0KIG8YBS>[`J8Jpm=MAjC6zH-P2sGWhimx]u;A1+p:9g/6ZF^R%o' );
define( 'LOGGED_IN_SALT',   'L621fr*6PYB{}>H19t~{;bvX1xX%E;|e?V<-S5.mA)DuL:0{~NB||o44divv^Z:8' );
define( 'NONCE_SALT',       '%5f`Omwm8$pmKA-N7F#>MlF?)cIRJOz`JK=Muqf%U4)q!AX3YV$_I,RT<;`S ;pB' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define('WP_DEBUG', true);            // Enable debug mode
define('WP_DEBUG_LOG', true);        // Log errors to debug.log
define('WP_DEBUG_DISPLAY', false);   // Hide errors from users
@ini_set('log_errors', 1);
@ini_set('display_errors', 0);

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';

/** license key for advancedcustomfields pro */
define( 'ACF_PRO_LICENSE', 'b3JkZXJfaWQ9MTE1ODA2fHR5cGU9ZGV2ZWxvcGVyfGRhdGU9MjAxNy0xMC0xNiAxMToyMDo0MQ==' );