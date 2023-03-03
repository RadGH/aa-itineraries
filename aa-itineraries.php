<?php
/*
Plugin Name: A+A - Alpine Hikers
Description: Account pages, Deposit form, Document storage, Itineraries, and Smartsheet integrations
Author: Radley Sustaire, Alchemy and Aim
Version: 1.1.0
*/

define( 'AH_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'AH_PATH', __DIR__ );

/*
 * These functions get the specific instances of each module object.
 */

/** @return Class_AH_Plugin */
function AH_Plugin() {
	static $AH = null;
	if ( $AH === null ) $AH = new Class_AH_Plugin();
	return $AH;
}

/**
 * This class includes plugin files, performs upgrades, and registers cron schedules. It also stores each module as an object.
 *
 * @class Class_AH_Plugin
 */
class Class_AH_Plugin {
	
	public $plugin_name = 'A+A - Alpine Hikers Itineraries';
	
	public $missing_plugins = array();
	
	// Objects
	public Class_AH_Cron                $Cron;
	public Class_AH_Admin               $Admin;
	public Class_AH_Enqueue             $Enqueue;
	public Class_AH_Rewrites            $Rewrites;
	public Class_AH_Reminders           $Reminders;
	public Class_AH_Smartsheet          $Smartsheet;
	public Class_AH_Smartsheet_Invoices $Smartsheet_Invoices;
	public Class_Account_Page_Post_Type $Account_Page;
	public Class_Document_Post_Type     $Document;
	public Class_Invoice_Post_Type      $Invoice;
	
	/*
	 * Constructor
	 */
	public function __construct() {
		
		// Finish loading the plugin after all other plugins have loaded
		add_action( 'plugins_loaded', array( $this, 'initialize_plugin' ) );
		
		// When plugin is activated from the plugins page, call $this->plugin_activated()
		register_activation_hook( __FILE__, array( $this, 'plugin_activated' ) );
		
		// When plugin is deactivated from the plugins page, call $this->plugin_deactivated()
		register_deactivation_hook( __FILE__, array( $this, 'plugin_deactivated' ) );
		
	}
	
	/**
	 * Initialize the plugin - called after plugins have loaded.
	 */
	public function initialize_plugin() {
		
		// ----------------------------------------
		// 1. Check dependencies
		// Dependency 1: Advanced Custom Fields Pro
		if ( !function_exists('acf') ) {
			$this->missing_plugins[] = 'Advanced Custom Fields Pro';
			add_action( 'admin_notices', array( $this, 'admin_notice_missing_dependencies' ) );
			return;
		}
		
		// ----------------------------------------
		// 2. Include debug features
		include_once( AH_PATH . '/includes/functions/debug.php' );
		
		// @TODO: Restrict access during development to only show for Radley
		if ( ! aa_is_developer() ) return;
		// @TODO: End of restricted access
		
		// ----------------------------------------
		// 3. Include instance classes which can be instantiated multiple times
		include_once( AH_PATH . '/includes/instances/api.php' );
		
		
		// ----------------------------------------
		// 4. Include controller classes which must be instantiated once
		include_once( __DIR__ . '/includes/classes/cron.php' );
		$this->Cron = new Class_AH_Cron();
		
		include_once( __DIR__ . '/includes/classes/admin.php' );
		$this->Admin = new Class_AH_Admin();
		
		include_once( __DIR__ . '/includes/classes/enqueue.php' );
		$this->Enqueue = new Class_AH_Enqueue();
		
		include_once( __DIR__ . '/includes/classes/reminders.php' );
		$this->Reminders = new Class_AH_Reminders();
		
		include_once( __DIR__ . '/includes/classes/rewrites.php' );
		$this->Rewrites = new Class_AH_Rewrites();
		
		include_once( __DIR__ . '/includes/classes/smartsheet.php' );
		$this->Smartsheet = new Class_AH_Smartsheet();
		
		include_once( __DIR__ . '/includes/classes/smartsheet-invoices.php' );
		$this->Smartsheet_Invoices = new Class_AH_Smartsheet_Invoices();
		
		// ----------------------------------------
		// 5. Custom post types controllers
		include_once( AH_PATH . '/includes/classes/_abstract_post_type.php' );
		
		include_once( __DIR__ . '/includes/classes/account_page_post_type.php' );
		$this->Account_Page = new Class_Account_Page_Post_Type();
		
		include_once( __DIR__ . '/includes/classes/document_post_type.php' );
		$this->Document = new Class_Document_Post_Type();
		
		include_once( __DIR__ . '/includes/classes/invoice_post_type.php' );
		$this->Invoice = new Class_Invoice_Post_Type();
		
		// ----------------------------------------
		// 6. Include other functions
		include_once( AH_PATH . '/includes/functions/general.php' );
		include_once( AH_PATH . '/includes/functions/utility.php' );
		
		// ----------------------------------------
		// 7. Shortcodes
		include_once( AH_PATH . '/includes/shortcodes/ah_documents.php' );
		include_once( AH_PATH . '/includes/shortcodes/ah_invoice_merge_tags_preview.php' );
		include_once( AH_PATH . '/includes/shortcodes/ah_invoices.php' );
		include_once( AH_PATH . '/includes/shortcodes/ah_login_form.php' );
		
	}
	
	/**
	 * Displayed on admin dashboard if a required plugin is not active
	 *
	 * @return void
	 */
	public function admin_notice_missing_dependencies() {
		?>
		<div class="notice notice-error">
			<p>
				<strong><?php echo $this->plugin_name; ?>:</strong>
				The following plugins are required: <?php echo implode(', ', $this->missing_plugins); ?>.
				Install or activate the plugin(s) from the <a href="<?php echo esc_attr(admin_url('plugins.php')); ?>">plugins page</a>.
			</p>
		</div>
		<?php
	}
	
	/**
	 * Only triggered when the plugin is activated, typically through the plugin dashboard
	 *
	 * @return void
	 */
	public function plugin_activated() {
		
		// Include database so that it may add tables
		// include_once( __DIR__ . '/includes/classes/upgrade.php' );
		// $this->Upgrade = new Class_AH_Upgrade();
		
		// Perform upgrades
		// $this->Upgrade->perform_upgrade();
		
	}
	
	/**
	 * Only triggered when the plugin is deactivated
	 *
	 * @return void
	 */
	public function plugin_deactivated() {
		
		// Clear cron schedules
		include_once( __DIR__ . '/includes/classes/cron.php' );
		$this->Cron = new Class_AH_Cron();
		
		// Clear the schedule
		// $this->Cron->clear_cron();
		// wp_clear_scheduled_hook( $this->time_key );
		
	}
	
}

// Create the initial plugin instance
AH_Plugin();