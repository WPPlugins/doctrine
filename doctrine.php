<?php
/**
 * Plugin Name: Doctrine ORM Integration
 * Plugin URI: http://www.flynsarmy.com/2010/02/integrating-doctrine-into-wordpress/
 * Description: This plugin enables Doctrine ORM 1.2.3 support in WordPress to easy development. You can get more information in <a href="http://www.doctrine-project.org/projects/orm/1.2/docs/en">Doctrine documentation</a>.
 * Author: Flynsarmy</a>, <a href="http://www.netinho.info/" title="Visitar a página de autores">Francisco Ernesto Teixeira
 * Author URI: http://www.flynsarmy.com/
 * Version: 0.3
 */

// constants of plugin
define( 'DOCTRINE_DSN', 'mysql://' . DB_USER . ':' . DB_PASSWORD . '@' . DB_HOST . '/' . DB_NAME );
define( 'DOCTRINE_MODELS_DIR', dirname( __FILE__ ) . '/models' );
define( 'DOCTRINE_SHORTCODES_DIR', dirname( __FILE__ ) . '/shortcodes' );
$GLOBALS['doctrine_models_folder_reset_processed'] = false;

/**
 * Default script init for Doctrine ORM Integration
 */
function wp_doctrine_init() {
    if ( isset( $_GET['action'] ) && ( $_GET['action'] == 'activate' ) && isset( $_GET['plugin'] ) && ( $_GET['plugin'] == 'doctrine/doctrine.php' ) ) {
        update_option( 'doctrine_only_in_admin', 'false' );
        if ( function_exists( 'apc_cache_info' ) ) {
            update_option( 'doctrine_use_apc', 'true' );
            update_option( 'doctrine_apc_result_cache_lifespan', 3600 );
        }
    }

    if ( get_option( 'doctrine_only_in_admin' ) != 'false' ) {
        if ( is_admin() ) {
            wp_doctrine_loadlibrary();
        }
    } else {
        wp_doctrine_loadlibrary();
    }
}
add_action( 'init', 'wp_doctrine_init' );

/**
 * Doctrine ORM Integration Options process
 */
function wp_doctrine_options_process() {
    if ( wp_verify_nonce( $_REQUEST['_wp_doctrine_nonce'], 'doctrine' ) ) {
        if ( isset( $_POST['submit'] ) ) {
            ( function_exists( 'current_user_can' ) && !current_user_can( 'manage_options' ) ) ? die( __( 'Cheatin&#8217; uh?', 'doctrine' ) ) : null;

            if ( isset( $_POST['models_folder_reset'] ) ) {
                if ( is_dir( DOCTRINE_MODELS_DIR ) ) {
                    require_once dirname( __FILE__ ) . '/deltree.func.php';
                    deltree( DOCTRINE_MODELS_DIR );
                }
                wp_doctrine_loadmodels();
                $GLOBALS['doctrine_models_folder_reset_processed'] = true;
            }

            isset( $_POST['only_in_admin'] ) ? update_option( 'doctrine_only_in_admin', 'true' ) : update_option( 'doctrine_only_in_admin', 'false' );

            if ( function_exists( 'apc_cache_info' ) ) {
                isset( $_POST['use_apc'] ) ? update_option( 'doctrine_use_apc', 'true' ) : update_option( 'doctrine_use_apc', 'false' );
            }

            isset( $_POST['apc_result_cache_lifespan'] ) ? update_option( 'doctrine_apc_result_cache_lifespan', (int) $_POST['apc_result_cache_lifespan'] ) : update_option( $_POST['apc_result_cache_lifespan'], 3600 );
        }
    }
}
isset( $_REQUEST['_wp_doctrine_nonce'] ) ? add_action( 'admin_init', 'wp_doctrine_options_process' ) : null;

/*
 * Doctrine Options Page
 */
function wp_doctrine_options_page() {
    $doctrine_models_folder_reset_processed_message = ( $GLOBALS['doctrine_models_folder_reset_processed'] ) ? '<p><strong>' . __( 'Reset Model\'s Folder processed.', 'doctrine' ) . '</strong></p>' : '';
?>
    <?php if ( $_POST ) { ?>
    <div id="message" class="updated fade"><?php echo $doctrine_models_folder_reset_processed_message; ?><p><strong><?php _e( 'Options saved.', 'doctrine' ) ?></strong></p></div>
    <?php } ?>
    <div class="wrap">
        <?php screen_icon(); ?>
        <h2><?php _e( 'Doctrine ORM Integration', 'doctrine' ); ?></h2>
        <form action="" method="post" id="doctrine-options">
            <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row"><?php _e( 'Reset Model\'s Folder', 'doctrine' ); ?></th>
                    <td><label><input name="models_folder_reset" id="models_folder_reset" value="false" type="checkbox" /> <?php _e( 'I\'m sure on reset all modifications of models and reload database structure.', 'doctrine' ); ?></label></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Only In Admin Area', 'doctrine' ); ?></th>
                    <td><label><input name="only_in_admin" id="only_in_admin" value="false" type="checkbox" <?php if ( get_option( 'doctrine_only_in_admin' ) != 'false' ) echo ' checked="checked" '; ?> /> <?php _e( 'Enable Doctrine ORM only in admin area.', 'doctrine' ); ?></label></td>
                </tr>
                <?php if ( function_exists( 'apc_cache_info' ) ) { ?>
                <tr>
                    <th scope="row"><?php _e( 'Use APC', 'doctrine' ); ?></th>
                    <td><label><input name="use_apc" id="use_apc" value="false" type="checkbox" <?php if ( get_option( 'doctrine_use_apc' ) != 'false' ) echo ' checked="checked" '; ?> /> <?php _e( 'Use Alternative PHP Cache.', 'doctrine' ); ?></label></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'APC: Result Cache Lifespan', 'doctrine' ); ?></th>
                    <td>
						<input type="text" class="regular-text" value="<?php echo (int) get_option( 'doctrine_apc_result_cache_lifespan' ); ?>" id="apc_result_cache_lifespan" name="apc_result_cache_lifespan" />
						<div class="description"><?php _e( 'Specify the result cache timelife.', 'doctrine' ); ?></div>
					</td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
        <p class="submit">
            <?php wp_nonce_field( 'doctrine', '_wp_doctrine_nonce' ); ?>
            <input class="button-primary" type="submit" name="submit" value="<?php _e( 'Process', 'doctrine' ); ?>" />
        </p>
    </form>
</div>
<?php
}

/*
 * Add Doctrine Options Page to Settings menu
 */
function wp_doctrine_options_menu() {
    function_exists( 'add_submenu_page' ) ? add_options_page( __( 'Doctrine ORM Integration', 'doctrine' ), __( 'Doctrine ORM Integration', 'doctrine' ), 'manage_options', 'doctrine-options', 'wp_doctrine_options_page' ) : null;
}
add_action( 'admin_menu', 'wp_doctrine_options_menu' );

/*
 * Load Doctrine library
 */
function wp_doctrine_loadlibrary() {
    // load Doctrine library
    require_once dirname( __FILE__ ) . '/lib/Doctrine.php';
    require_once dirname( __FILE__ ) . '/count_files_in_dir.func.php';

    // this will allow Doctrine to load Model classes automatically
    spl_autoload_register( array( 'Doctrine', 'autoload' ) );

    Doctrine_Manager::connection( DOCTRINE_DSN, 'default' );

    wp_doctrine_loadmodels();

    // (OPTIONAL) CONFIGURATION BELOW

    // load our shortcodes
    if ( is_dir( DOCTRINE_SHORTCODES_DIR ) ) {
        foreach ( glob( DOCTRINE_SHORTCODES_DIR . '/*.php' ) as $shortcode_file ) {
            require_once( $shortcode_file );
        }
    } else {
        mkdir( DOCTRINE_SHORTCODES_DIR, 0775 );
    }

    // this will allow us to use "mutators"
    Doctrine_Manager::getInstance()->setAttribute( Doctrine::ATTR_AUTO_ACCESSOR_OVERRIDE, true );

    // this sets all table columns to notnull and unsigned (for ints) by default
    Doctrine_Manager::getInstance()->setAttribute( Doctrine::ATTR_DEFAULT_COLUMN_OPTIONS,
        array( 'notnull' => true, 'unsigned' => true ) );

    // set the default primary key to be named 'id', integer, 20 bytes as default MySQL bigint
    Doctrine_Manager::getInstance()->setAttribute( Doctrine::ATTR_DEFAULT_IDENTIFIER_OPTIONS,
        array( 'name' => 'id', 'type' => 'integer', 'length' => 20 ) );

    // use of Alternative PHP Cache
    if ( function_exists( 'apc_cache_info' ) && ( get_option('doctrine_use_apc') != 'false' ) ) {
        $cacheDriver = new Doctrine_Cache_Apc();
        Doctrine_Manager::getInstance()->setAttribute( Doctrine::ATTR_QUERY_CACHE,
            $cacheDriver );
        Doctrine_Manager::getInstance()->setAttribute( Doctrine_Core::ATTR_RESULT_CACHE,
            $cacheDriver );
        Doctrine_Manager::getInstance()->setAttribute( Doctrine_Core::ATTR_RESULT_CACHE_LIFESPAN,
            (int) get_option( 'doctrine_apc_result_cache_lifespan' ) );
    }
}

/*
 * Generate and load all database models
 */
function wp_doctrine_loadmodels() {
    // detect if model's folder exists and make if not
    if ( !is_dir( DOCTRINE_MODELS_DIR ) ) {
        mkdir( DOCTRINE_MODELS_DIR, 0775 );
    }

    // detect if models exists and generate if not
    if ( count_files_in_dir( DOCTRINE_MODELS_DIR ) . '/*.php' ) {
        Doctrine_Core::generateModelsFromDb( DOCTRINE_MODELS_DIR, array( 'default' ),
            array( 'generateTableClasses' => true ) );
    }

    // telling Doctrine where our models are located
    Doctrine::loadModels( DOCTRINE_MODELS_DIR . '/generated' );
    Doctrine::loadModels( DOCTRINE_MODELS_DIR );
}
?>
