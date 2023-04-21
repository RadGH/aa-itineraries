<?php
/*
Plugin Name: A+A - Alpine Hikers
Description: Account pages, invoices, payment form, client documents, itineraries, and Smartsheet integration.
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

/* Shorthands of our objects */
function AH_Cron() { return AH_Plugin()->Cron; }
function AH_Admin() { return AH_Plugin()->Admin; }
function AH_Enqueue() { return AH_Plugin()->Enqueue; }
function AH_Rewrites() { return AH_Plugin()->Rewrites; }
function AH_Reminders() { return AH_Plugin()->Reminders; }
function AH_Theme() { return AH_Plugin()->Theme; }
function AH_Smartsheet() { return AH_Plugin()->Smartsheet; }
function AH_Smartsheet_Hotels() { return AH_Plugin()->Smartsheet_Hotels; }
function AH_Smartsheet_Rooms() { return AH_Plugin()->Smartsheet_Rooms; }
function AH_Smartsheet_Invoices() { return AH_Plugin()->Smartsheet_Invoices; }
function AH_Smartsheet_Webhooks() { return AH_Plugin()->Smartsheet_Webhooks; }
function AH_Account_Page() { return AH_Plugin()->Account_Page; }
function AH_Document() { return AH_Plugin()->Document; }
function AH_Invoice() { return AH_Plugin()->Invoice; }
function AH_Itinerary() { return AH_Plugin()->Itinerary; }
function AH_Itinerary_Template() { return AH_Plugin()->Itinerary_Template; }
function AH_Village() { return AH_Plugin()->Village; }
function AH_Hotel() { return AH_Plugin()->Hotel; }
function AH_Hike() { return AH_Plugin()->Hike; }
function AH_PDF() { return AH_Plugin()->PDF; }

/**
 * This class includes plugin files, performs upgrades, and registers cron schedules. It also stores each module as an object.
 *
 * @class Class_AH_Plugin
 */
class Class_AH_Plugin {
	
	public $plugin_name = 'A+A - Alpine Hikers';
	
	public $missing_plugins = array();
	
	// Objects
	public Class_AH_Cron                       $Cron;
	public Class_AH_Admin                      $Admin;
	public Class_AH_Enqueue                    $Enqueue;
	public Class_AH_Rewrites                   $Rewrites;
	public Class_AH_Reminders                  $Reminders;
	public Class_AH_Theme                      $Theme;
	
	public Class_AH_Smartsheet                 $Smartsheet;
	public Class_AH_Smartsheet_Hotels          $Smartsheet_Hotels;
	public Class_AH_Smartsheet_Rooms           $Smartsheet_Rooms;
	public Class_AH_Smartsheet_Invoices        $Smartsheet_Invoices;
	public Class_AH_Smartsheet_Webhooks        $Smartsheet_Webhooks;
	
	public Class_Account_Page_Post_Type        $Account_Page;
	public Class_Document_Post_Type            $Document;
	public Class_Invoice_Post_Type             $Invoice;
	public Class_Itinerary_Post_Type           $Itinerary;
	public Class_Itinerary_Template_Post_Type  $Itinerary_Template;
	public Class_Village_Post_Type             $Village;
	public Class_Hotel_Post_Type               $Hotel;
	public Class_Hike_Post_Type                $Hike;
	public Class_AH_PDF                        $PDF;
	
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
		require_once( AH_PATH . '/includes/functions/debug.php' );
		
		// ----------------------------------------
		// 3. Include instance classes which can be instantiated multiple times
		require_once( AH_PATH . '/includes/instances/api.php' );
		
		
		// ----------------------------------------
		// 4. Include controller classes which must be instantiated once
		require_once( __DIR__ . '/includes/classes/cron.php' );
		$this->Cron = new Class_AH_Cron();
		
		require_once( __DIR__ . '/includes/classes/admin.php' );
		$this->Admin = new Class_AH_Admin();
		
		require_once( __DIR__ . '/includes/classes/enqueue.php' );
		$this->Enqueue = new Class_AH_Enqueue();
		
		require_once( __DIR__ . '/includes/classes/reminders.php' );
		$this->Reminders = new Class_AH_Reminders();
		
		require_once( __DIR__ . '/includes/classes/theme.php' );
		$this->Theme = new Class_AH_Theme();
		
		require_once( __DIR__ . '/includes/classes/rewrites.php' );
		$this->Rewrites = new Class_AH_Rewrites();
		
		require_once( __DIR__ . '/includes/classes/smartsheet.php' );
		$this->Smartsheet = new Class_AH_Smartsheet();
		
		require_once( __DIR__ . '/includes/classes/smartsheet-sync-hotels.php' );
		$this->Smartsheet_Hotels = new Class_AH_Smartsheet_Hotels();
		
		require_once( __DIR__ . '/includes/classes/smartsheet-sync-rooms.php' );
		$this->Smartsheet_Rooms = new Class_AH_Smartsheet_Rooms();
		
		require_once( __DIR__ . '/includes/classes/smartsheet-invoices.php' );
		$this->Smartsheet_Invoices = new Class_AH_Smartsheet_Invoices();
		
		require_once( __DIR__ . '/includes/classes/smartsheet-webhooks.php' );
		$this->Smartsheet_Webhooks = new Class_AH_Smartsheet_Webhooks();
		
		// ----------------------------------------
		// 5. Custom post types controllers
		require_once( AH_PATH . '/includes/post-types/_abstract_post_type.php' );
		
		require_once( __DIR__ . '/includes/post-types/account_page.php' );
		$this->Account_Page = new Class_Account_Page_Post_Type();
		
		require_once( __DIR__ . '/includes/post-types/document.php' );
		$this->Document = new Class_Document_Post_Type();
		
		require_once( __DIR__ . '/includes/post-types/invoice.php' );
		$this->Invoice = new Class_Invoice_Post_Type();
		
		require_once( __DIR__ . '/includes/post-types/itinerary.php' );
		$this->Itinerary = new Class_Itinerary_Post_Type();
		
		require_once( __DIR__ . '/includes/post-types/itinerary-template.php' );
		$this->Itinerary_Template = new Class_Itinerary_Template_Post_Type();
		
		require_once( __DIR__ . '/includes/post-types/village.php' );
		$this->Village = new Class_Village_Post_Type();
		
		require_once( __DIR__ . '/includes/post-types/hotel.php' );
		$this->Hotel = new Class_Hotel_Post_Type();
		
		require_once( __DIR__ . '/includes/post-types/hike.php' );
		$this->Hike = new Class_Hike_Post_Type();
		
		// ----------------------------------------
		// 6. Include other functions
		require_once( AH_PATH . '/includes/functions/general.php' );
		require_once( AH_PATH . '/includes/functions/utility.php' );
		
		// ----------------------------------------
		// 7. Shortcodes
		require_once( AH_PATH . '/includes/shortcodes/ah_documents.php' );
		require_once( AH_PATH . '/includes/shortcodes/ah_invoice_merge_tags_preview.php' );
		require_once( AH_PATH . '/includes/shortcodes/ah_invoices.php' );
		require_once( AH_PATH . '/includes/shortcodes/ah_itineraries.php' );
		require_once( AH_PATH . '/includes/shortcodes/ah_login_form.php' );
		
		// ----------------------------------------
		// 8. PDF Library
		require_once( AH_PATH . '/includes/pdf/pdf.php' );
		require_once( AH_PATH . '/includes/pdf/preview.php' );
		$this->PDF = new Class_AH_PDF();
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
		// require_once( __DIR__ . '/includes/classes/upgrade.php' );
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
		require_once( __DIR__ . '/includes/classes/cron.php' );
		$this->Cron = new Class_AH_Cron();
		
		// Clear the schedule
		$this->Cron->clear_cron();
		
	}
	
}

// Create the initial plugin instance
AH_Plugin();