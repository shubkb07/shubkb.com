<?php
/**
 * Load Global WiseSync Plugin Classes.
 *
 * @package   WISESYNC
 * @since    1.0.0
 */

use Sync\{Sync_Settings, Sync_Ajax, Sync_CLI, Sync_Filesystem, Sync_Site_Health, Sync_Remote_Request, Sync_Post, Sync_User, Sync_Helpers};

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$sync_helpers        = new Sync_Helpers();
$sync_ajax           = new Sync_Ajax();
$sync_settings       = new Sync_Settings();
$sync_cli            = new Sync_CLI();
$sync_filesystem     = new Sync_Filesystem();
$sync_site_health    = new Sync_Site_Health();
$sync_remote_request = new Sync_Remote_Request();
$sync_post           = new Sync_Post();
$sync_user           = new Sync_User();

add_action( 'sync_site_health_before', 'meowsh' );

/**
 * Display the site health status.
 * 
 * @return void
 */
function meowsh() {

	global $sync_site_health;

	// Register a main section.
	$sync_site_health->register_site_health_section(
		'status',
		'Sync Status',
		'Overview of your synchronization status'
	);

	// Add a table section.
	$sync_site_health->register_site_health_table_section(
		'status',
		'Last Synchronization',
		array(
			'Last Successful Sync' => 'meowsh',
			'Sync Frequency'       => 'Every 4 hours',
		),
		'It is a test description',
		'Performance',
		'Good'
	);

	// Add a log section.
	$sync_site_health->register_site_health_log_section(
		'status',
		'Recent Logs',
		'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed porta luctus mauris at rhoncus. Fusce eu blandit arcu, non fermentum metus. Integer ex lorem, pulvinar sit amet tincidunt at, accumsan ac ligula. Nunc in volutpat odio, vitae facilisis sem. Pellentesque mollis arcu id libero pellentesque, nec luctus arcu consectetur. Sed a ex hendrerit, fringilla mauris ac, lacinia erat. Duis condimentum tellus sit amet odio porttitor, sit amet ullamcorper magna volutpat. In cursus sollicitudin urna sed efficitur. Donec fermentum, magna sit amet porta interdum, ex quam mattis ante, a elementum est urna id nibh. Cras at eleifend lectus. Aliquam erat volutpat. Phasellus neque erat, consequat id urna ac, efficitur pellentesque lorem. Morbi placerat vel ipsum eu semper. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos.<br>
					   Quisque consectetur eu lacus non dapibus. Sed pharetra ut orci non tempus. Morbi vitae arcu velit. Suspendisse potenti. Etiam sed nulla molestie nibh maximus blandit in ultrices lacus. Sed tortor erat, molestie eget fringilla nec, viverra a nisi. Ut arcu velit, porttitor ut imperdiet sit amet, posuere eget lacus. Donec finibus lorem ut mauris volutpat, nec rhoncus nisi pharetra. Ut congue consectetur ipsum, a volutpat lacus finibus nec.<br>
					   In vehicula ornare tortor, elementum venenatis velit tincidunt vel. Nulla eu cursus est. Praesent vitae turpis tempus, pharetra eros quis, pharetra elit. Nulla pretium massa a nulla imperdiet blandit. Ut quis viverra augue. In sed molestie libero, nec mattis urna. Suspendisse eu dui non purus rutrum sagittis eu id urna. Suspendisse felis purus, fermentum nec felis eget, rutrum euismod leo. Donec cursus nunc ut magna venenatis, ut tincidunt ligula lobortis. Proin lacinia ante ac scelerisque sodales. Nulla magna quam, tincidunt et nisi vel, sodales malesuada odio. Etiam finibus elementum interdum. Integer sem nulla, viverra quis aliquet eget, cursus a nibh.<br>
					   Mauris posuere varius neque at ornare. In hac habitasse platea dictumst. Duis posuere arcu vel neque suscipit, in sodales mauris pretium. Nulla at porttitor augue, sed commodo ligula. Duis massa nulla, accumsan sit amet mollis et, volutpat congue justo. Etiam dictum nisi lacus, id scelerisque ipsum tempor a. Aenean erat metus, accumsan sed metus et, fringilla varius quam. Nulla convallis gravida auctor. Nunc aliquam feugiat elit eget egestas. Donec semper placerat tristique. Duis quis urna mauris. Curabitur ullamcorper leo ac neque sagittis sollicitudin. Etiam dignissim eget orci non egestas. Vivamus lacinia, metus et scelerisque euismod, felis ligula condimentum sapien, eu bibendum dui diam eget ex. Aenean auctor elit ut metus dictum hendrerit.<br>
					   Ut sit amet urna et nisl aliquam lobortis. Vivamus ac enim lorem. Sed id eros elementum, mollis lorem vitae, euismod leo. Praesent molestie, eros ac suscipit bibendum, orci eros egestas eros, ut ultricies justo nisl nec est. Sed ut varius ante, in ullamcorper dui. Mauris a ante faucibus, fermentum mauris sed, dignissim sapien. Aenean eleifend ligula velit, non consectetur tellus condimentum eget. In porta tincidunt erat, vitae condimentum orci vulputate eget. Etiam a interdum mauris. Morbi at tellus dui. Aenean condimentum, ligula id volutpat mattis, nulla massa volutpat dolor, id tempus massa ex non sem.',
		'It is a test description',
		false,
		true // Show separate copy button.
	);
}
