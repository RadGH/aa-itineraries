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

function AH_Smartsheet_API() { return AH_Plugin()->Smartsheet_API; }
function AH_Smartsheet_Invoices() { return AH_Plugin()->Smartsheet_Invoices; }
function AH_Smartsheet_Webhooks() { return AH_Plugin()->Smartsheet_Webhooks; }
function AH_Smartsheet_Sheet_Select() { return AH_Plugin()->Smartsheet_Sheet_Select; }

function AH_Smartsheet_Sync() { return AH_Plugin()->Smartsheet_Sync; }
function AH_Smartsheet_Sync_Hikes() { return AH_Plugin()->Smartsheet_Sync_Hikes; }
function AH_Smartsheet_Sync_Hotels_And_Villages() { return AH_Plugin()->Smartsheet_Sync_Hotels_And_Villages; }
function AH_Smartsheet_Sync_Itineraries() { return AH_Plugin()->Smartsheet_Sync_Itineraries; }
function AH_Smartsheet_Sync_Rooms_And_Meals() { return AH_Plugin()->Smartsheet_Sync_Rooms_And_Meals; }
function AH_Smartsheet_Sync_Sheets() { return AH_Plugin()->Smartsheet_Sync_Sheets; }

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
	
	public Class_AH_Smartsheet_API             $Smartsheet_API;
	public Class_AH_Smartsheet_Invoices        $Smartsheet_Invoices;
	public Class_AH_Smartsheet_Webhooks        $Smartsheet_Webhooks;
	public Class_AH_Smartsheet_Sheet_Select    $Smartsheet_Sheet_Select;
	
	public Class_AH_Smartsheet_Sync                      $Smartsheet_Sync;
	public Class_AH_Smartsheet_Sync_Hikes                $Smartsheet_Sync_Hikes;
	public Class_AH_Smartsheet_Sync_Hotels_And_Villages  $Smartsheet_Sync_Hotels_And_Villages;
	public Class_AH_Smartsheet_Sync_Itineraries          $Smartsheet_Sync_Itineraries;
	public Class_AH_Smartsheet_Sync_Rooms_And_Meals      $Smartsheet_Sync_Rooms_And_Meals;
	public Class_AH_Smartsheet_Sync_Sheets               $Smartsheet_Sync_Sheets;
	
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
		add_action( 'plugins_loaded', array( $this, 'initialize_plugin' ), 20 );
		
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
		
		require_once( __DIR__ . '/includes/instances/sync-itinerary-fields.php' );
		
		
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
		
		// ----------------------------------------
		// 5. Smartsheet Integrations		
		require_once( __DIR__ . '/includes/smartsheet/api.php' );
		$this->Smartsheet_API = new Class_AH_Smartsheet_API();
		
		require_once( __DIR__ . '/includes/smartsheet/webhooks.php' );
		$this->Smartsheet_Webhooks = new Class_AH_Smartsheet_Webhooks();
		
		require_once( __DIR__ . '/includes/smartsheet/sheet-select.php' );
		$this->Smartsheet_Sheet_Select = new Class_AH_Smartsheet_Sheet_Select();
		
		require_once( __DIR__ . '/includes/smartsheet/invoices.php' );
		$this->Smartsheet_Invoices = new Class_AH_Smartsheet_Invoices();
		
		// Smartsheet - Sync utilities
		require_once( __DIR__ . '/includes/smartsheet/sync.php' );
		$this->Smartsheet_Sync = new Class_AH_Smartsheet_Sync();
		
		require_once( __DIR__ . '/includes/smartsheet/sync-hotels-and-villages.php' );
		$this->Smartsheet_Sync_Hotels_And_Villages = new Class_AH_Smartsheet_Sync_Hotels_And_Villages();
		
		require_once( __DIR__ . '/includes/smartsheet/sync-hikes.php' );
		$this->Smartsheet_Sync_Hikes = new Class_AH_Smartsheet_Sync_Hikes();
		
		require_once( __DIR__ . '/includes/smartsheet/sync-itineraries.php' );
		$this->Smartsheet_Sync_Itineraries = new Class_AH_Smartsheet_Sync_Itineraries();
		
		require_once( __DIR__ . '/includes/smartsheet/sync-rooms-and-meals.php' );
		$this->Smartsheet_Sync_Rooms_And_Meals = new Class_AH_Smartsheet_Sync_Rooms_And_Meals();
		
		require_once( __DIR__ . '/includes/smartsheet/sync-sheets.php' );
		$this->Smartsheet_Sync_Sheets = new Class_AH_Smartsheet_Sync_Sheets();
		
		// ----------------------------------------
		// 6. Custom post types controllers
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
		// 7. Include other functions
		require_once( AH_PATH . '/includes/functions/general.php' );
		require_once( AH_PATH . '/includes/functions/utility.php' );
		
		// ----------------------------------------
		// 8. Shortcodes
		require_once( AH_PATH . '/includes/shortcodes/ah_accordion.php' );
		require_once( AH_PATH . '/includes/shortcodes/ah_documents.php' );
		require_once( AH_PATH . '/includes/shortcodes/ah_invoice_merge_tags_preview.php' );
		require_once( AH_PATH . '/includes/shortcodes/ah_invoices.php' );
		require_once( AH_PATH . '/includes/shortcodes/ah_itineraries.php' );
		require_once( AH_PATH . '/includes/shortcodes/ah_login_form.php' );
		require_once( AH_PATH . '/includes/shortcodes/ah_profile.php' );
		
		// ----------------------------------------
		// 9. PDF Library
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