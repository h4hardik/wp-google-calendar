<?php 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/*
    Plugin Name: MH GCalendar
    Description: Wordpress plugin to manage your google calendar events.
    Author: Hardik Bhavsar
    Version: 1.1
 */

add_action( 'admin_enqueue_scripts', 'wpgc_styles' );
add_action( 'admin_enqueue_scripts', 'wpgc_scripts' );

// Frontend Style

function wpgc_styles( ) {
    wp_enqueue_style( 'wp-gc-style', plugins_url('css/wp-gc-style.css', __FILE__));
    wp_enqueue_style( 'fullcalendar', plugins_url('css/fullcalendar.css', __FILE__));
}
// Add styles for front side

function wpgcf_styles( ) {
    wp_enqueue_style( 'wp-gc-style-frontend', plugins_url('css/wp-gc-style-frontend.css', __FILE__));
    wp_enqueue_style( 'fullcalendar', plugins_url('css/fullcalendar.css', __FILE__));
    wp_enqueue_style( 'jqueryui', plugins_url('css/jquery-ui.css', __FILE__));
}

// Add js to backend
function wpgc_scripts() {
	wp_enqueue_script('jquery');
    wp_enqueue_script( 'moment.min', plugins_url('js/moment.min.js', __FILE__) );
    wp_enqueue_script( 'fullcalendar.min', plugins_url('js/fullcalendar.min.js', __FILE__) );
}

// Add js for front side
function wpgcf_scripts() {
	wp_enqueue_script('jquery');
    wp_enqueue_script( 'moment.min', plugins_url('js/moment.min.js', __FILE__) );
    wp_enqueue_script( 'fullcalendar.min', plugins_url('js/fullcalendar.min.js', __FILE__) );
    wp_enqueue_script( 'jqueryui.min', plugins_url('js/jquery-ui.min.js', __FILE__) );
    wp_enqueue_script( 'jqueryvalidate.min', plugins_url('js/jquery.validate.js', __FILE__) );
    wp_enqueue_script( 'mh-gcalendar', plugins_url('js/mh-gcalendar.js', __FILE__) );
}

add_action('wp_head','wpgcf_styles');
add_action('wp_footer','wpgcf_scripts');

// Plugin Menu
function wpgc_plugin_menu()
{
	$root = plugin_dir_url( __FILE__ );
	add_menu_page('calendar Settings','WP GCalendar', 'manage_options', 'google-calendar-plg', 'wpgc_calendar_page', $root.'img/calendar.png');
	add_submenu_page( 'google-calendar-plg', 'calendar Settings' . ' All Events', ' All Events', 'manage_options','google-calendar-plg', 'mhgc_calendar_page');
	add_submenu_page('google-calendar-plg','Settings', 'Settings', 'manage_options', 
					'google-calendar-settings', 'wpgc_settings');
	/*add_submenu_page('google-calendar-plg','Documentation', 'Documentation', 'manage_options',
					'wpgc-documentation', 'wpgc_documentation');*/
	
}
add_action('admin_menu', 'wpgc_plugin_menu');

/* Admin Interface - display google calendar */
function wpgc_calendar_page()
{
    $calendar_file = get_option( 'google_cal_file' );
    if($calendar_file==''){ ?>
    <h2>Settings required ! </h2>
    <p><a href="<?php echo admin_url('admin.php?page=google-calendar-settings'); ?>">Go to settings</a></p>
    <?php exit(); }
    include_once('class.iCalReader.php');
    date_default_timezone_set('US/Eastern'); // set default timezone
    $ical   = new ICal($calendar_file);
    $events = $ical->eventsFromRange(true, true);

    foreach ($events as $event) { // for testing purposes
        $ev[] = array(
            'title' => addslashes($event['SUMMARY']),
            'start' => date('Y-m-d\TH:i:s', strtotime($event['DTSTART'])),
            'end' => date('Y-m-d\TH:i:s', strtotime($event['DTEND'])),
            'allDay' => strlen($event['DTSTART'])==8 ? true : false,
            'busy'=>1
        );
    }
    $e = json_encode($ev);
  ?>

    <script src="https://apis.google.com/js/client.js">
    </script>
	<div class="wrap">
        <div style="display:none" class="eventJson" defaultDate = "<?php echo date('Y-m-d'); ?>" data='<?php echo $e; ?>'></div>

	<h2>List of upcoming events</h2>
	
	<h5>Use the Short code <strong style="color:red;">[mhgc-calendar]</strong> to display your calendar in your posts or pages.</h5>
	
	

            <script>
                jQuery(document).ready(function() {
                    var ev = eval(jQuery("div.eventJson").attr("data"));
                    var default_Date = jQuery("div.eventJson").attr("defaultDate");
                    jQuery('#calendar').fullCalendar({
                        header: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'month,agendaWeek,agendaDay'
                        },
                        defaultDate: default_Date,
                        selectable: false,
                        selectHelper: false,
                        select: function(start, end) {
                            var title = prompt('Event Title:');
                            var eventData;
                            if (title) {
                                eventData = {
                                    title: title,
                                    start: start,
                                    end: end
                                };
                                jQuery('#calendar').fullCalendar('renderEvent', eventData, true);
                            }
                            jQuery('#calendar').fullCalendar('unselect');
                        },
                        editable: false,
                        eventLimit: false,
                        events: ev
                        });

                    });
            </script>
		<div id='calendar'></div>
	</div>
	<?php
}

function wpgc_settings()
{
    $calendar_file = get_option( 'google_cal_file' );
	if($_POST){
		if(isset($_POST['calendar_file'])){
            $calendar_file = urldecode($_POST['calendar_file']);
            add_option('google_cal_file',$calendar_file);
		}
	}
	?>
	<div class="wrap">
	<form action="" method="post" id="insert-event">
	<table class="wpgc-setting-table">
		<tr>
			<td colspan="2" class="entry-view-field-name">Settings</td>
		</tr>
		<tr>
			<td>
				<h3><label for="clientID">Google Calendar ICAL URL </label> </h3>
			</td>
			<td>
				<input type="text" id="calendar_file" class="input" name="calendar_file"
				value="<?php if($calendar_file){ echo esc_attr($calendar_file); } ?>"/>
			</td>
		</tr>
        <tr><td>&nbsp;</td></tr>
		<tr>
			<td >
				<p>
					<input type="submit" name="submit" value="Save" class="wpgc-mdl-button" style="float:right; margin: 0 20px 10px 0" />
					<a href="<?php echo admin_url( 'admin.php?page=google-calendar-plg'); ?>" class="wpgc-mdl-button" style="float:right; margin: 0 20px 10px 0"/>Cancel</a>
				</p>

			</td>
		</tr>
	</table>
	<?php wp_nonce_field('calendar_event'); ?>
	</form>
	</div>
<?php
}

function mhgc_my_calendar()
{?>
    <div id="eventContent" title="Event Details" style="display:none;" class="contact-content">
        <div class="contact-loading" style="display:none"></div>
        <div class="success_msg"></div>
        <form class="cmxform" id="eventform" method="get" action="">
            <fieldset>
                <p class="txt-fld">
                    <label for="pName">Your Name</label>
                    <input id="pName" name="pName" minlength="2" type="text" required>
                </p>
                <p class="txt-fld">
                    <label for="pEmail">Your E-Mail</label>
                    <input id="pEmail" type="email" name="pEmail" required>
                </p>
                <p class="txt-fld">
                    <label for="pPhone">Your Phone</label>
                    <input id="pPhone" type="phone" name="pPhone" required>
                </p>
                <p class="txt-fld">
                    <label for="pNotes">Your Message</label>
                    <textarea id="pNotes" name="pNotes"></textarea>
                </p>
                <p class="btn-fld">
                    <input id="savebtn" class="btn-frm" type="submit" value="Save">
                    <input id="cancebtn" class="btn-frm" type="button" value="Cancel" onClick="jQuery('#eventContent').dialog('close');jQuery('#eventform').trigger( 'reset' );">
                </p>
            </fieldset>
        </form>
        <p id="eventInfo"></p>
    </div>
<?php
    include_once('class.iCalReader.php');
    date_default_timezone_set('US/Eastern'); // set default timezone
    $calendar_file = get_option( 'google_cal_file' );
    if($calendar_file==''){?>
        <h2>Settings required ! </h2>
        <p><a href="<?php echo admin_url('admin.php?page=google-calendar-settings'); ?>">Go to settings</a></p>
        <?php exit(); }
    $ical   = new ICal($calendar_file);
    $events = $ical->eventsFromRange(true, true);

    foreach ($events as $event) {
        if (isset($event['TRANSP'])) {
            $key = "url";
            $value ="javascript: showForm()";
        }
        else {
            $key = "busy";
            $value="1";
        }
        $ev[] = array(
            'title' => addslashes($event['SUMMARY']),
            'start' => date('Y-m-d\TH:i:s', strtotime($event['DTSTART'])),
            'end' => date('Y-m-d\TH:i:s', strtotime($event['DTEND'])),
            'allDay' => strlen($event['DTSTART'])==8 ? true : false,
            $key => $value
        );
    }
	$e = json_encode($ev);
	?>
	<div style="display:none" class="eventJson" defaultDate="<?php echo date('Y-m-d'); ?>" data='<?php echo $e; ?>'></div>
<?php
	return "<div id='calendar'></div>";
}

function my_enqueue() {

    wp_enqueue_script( 'ajax-script',plugins_url('js/mh-gcalendar.js', __FILE__) );
    wp_localize_script( 'ajax-script', 'my_ajax_object',
        array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
}

add_action( 'wp_enqueue_scripts', 'my_enqueue' );
add_shortcode('mhgc-calendar','mhgc_my_calendar');
add_action( 'wp_ajax_my_action', 'my_action_callback' );
add_filter( 'wp_mail_content_type', 'set_html_content_type' );

function set_html_content_type() {
    return 'text/html';
}

/**
 * Send mail to user
 */
function my_action_callback() {

    $frm_data = $_POST['frm_data'];
    $message = '';
    for($i=0;$i<count($frm_data);$i++){
        $message.= $frm_data[$i]['name']."=".$frm_data[$i]['value']."\n";
    }
    $user_email = get_option('admin_email'); //'hardik.bhavsar@martindale.com';
    $subject = "SCHEDULE AN APPOINTMENT REQUEST:".$frm_data[0]['name'];
    if ( $message && !wp_mail( $user_email, wp_specialchars_decode( $subject ), $message ) )
        wp_die( __('The email could not be sent.') . "<br />\n" . __('Possible reason: your host may have disabled the mail() function.') );
    wp_die(); // this is required to terminate immediately and return a proper response
}

add_action( 'wp_ajax_wpgc_events_action', 'wpgc_events_action_callback' );