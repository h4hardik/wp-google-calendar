<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

/*
    Plugin Name: Wp Google Calendar
    Description: Wordpress plugin to manage your google calendar events.
    Author: Hardik Bhavsar
    Version: 1.0.0
 */

add_action('admin_enqueue_scripts', 'wp_gc_styles');
add_action('admin_enqueue_scripts', 'wp_gc_scripts');
add_action('wp_head', 'wp_gcf_styles');
add_action('wp_footer', 'wp_gcf_scripts');
add_action('admin_menu', 'wp_gc_plugin_menu');
add_action('wp_enqueue_scripts', 'wp_gc_my_enqueue');
add_action('wp_ajax_add_event_action', 'add_event_action_callback');
register_activation_hook(__FILE__, 'wp_gc_calendar_activate');
add_shortcode('wp-gc-calendar', 'wp_gc_my_calendar');

// Frontend Style

function wp_gc_styles()
{
    wp_enqueue_style('wp-gc-style', plugins_url('css/wp-gc-style.css', __FILE__));
    wp_enqueue_style('fullcalendar', plugins_url('css/fullcalendar.css', __FILE__));
}

// Add styles for front side

function wp_gcf_styles()
{
    wp_enqueue_style('gc-style-frontend', plugins_url('css/gc-style-frontend.css', __FILE__));
    wp_enqueue_style('fullcalendar', plugins_url('css/fullcalendar.css', __FILE__));
    wp_enqueue_style('jqueryui', plugins_url('css/jquery-ui.css', __FILE__));
}

// Add js to backend
function wp_gc_scripts()
{
    wp_enqueue_script('jquery');
    wp_enqueue_script('moment.min', plugins_url('js/moment.min.js', __FILE__));
    wp_enqueue_script('fullcalendar.min', plugins_url('js/fullcalendar.min.js', __FILE__));
}

// Add js for front side
function wp_gcf_scripts()
{
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-dialog');
    wp_enqueue_script('moment.min', plugins_url('js/moment.min.js', __FILE__));
    wp_enqueue_script('fullcalendar.min', plugins_url('js/fullcalendar.min.js', __FILE__));
    wp_enqueue_script('jqueryvalidate.min', plugins_url('js/jquery.validate.min.js', __FILE__));
    wp_enqueue_script('wp-gcalendar', plugins_url('js/wp-gcalendar.js', __FILE__));
}

/**
 *
 */
function wp_gc_calendar_activate()
{
    global $wpdb;

    $table_name = $wpdb->prefix . "google_events";

    if ($wpdb->get_var('SHOW TABLES LIKE ' . $table_name) != $table_name) {
        $sql = 'CREATE TABLE ' . $table_name . '(
				description TEXT,
				name VARCHAR (255),
				phone VARCHAR (255),
				email VARCHAR (255),
				start_Date DATE,
				start_Time TIME,
				end_Date DATE,
				end_Time TIME,
				created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
				event_id INTEGER(10) UNSIGNED AUTO_INCREMENT,
				PRIMARY KEY  (event_id))';

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        add_option('events_database_version', '1.0');
    }
    $table_setting = $wpdb->prefix . "google_api_setting";
    if ($wpdb->get_var('SHOW TABLES LIKE ' . $table_setting) != $table_setting) {
        $sql = 'CREATE TABLE ' . $table_setting . '(
				id_setting INTEGER(10) UNSIGNED AUTO_INCREMENT,
				clientID VARCHAR (255),
				calendarID VARCHAR (255),
				calendarFile VARCHAR (255),
				defaultDate DATE,
				priority VARCHAR (255),
				PRIMARY KEY  (id_setting) )';
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        add_option('api_setting_database_version', '1.0');
    }
}

// Plugin Menu
function wp_gc_plugin_menu()
{
    $root = plugin_dir_url(__FILE__);

    add_menu_page('Google Calendar Settings', 'WP Google Calendar', 'manage_options', 'wp-google-calendar-plg', 'wp_gc_calendar_page', $root . 'img/calendar.png');

    add_submenu_page('wp-google-calendar-plg', 'Google Calendar Settings' . ' All Events', ' All Events', 'manage_options', 'wp-google-calendar-plg', 'wp_gc_calendar_page');

    add_submenu_page('wp-google-calendar-plg', 'Settings', 'Settings', 'manage_options',
        'wp-google-calendar-settings', 'wp_gc_settings');
    add_submenu_page('wp-google-calendar-plg','Documentation', 'Documentation', 'manage_options',
        'wp-google-calendar-documentation', 'wpgc_documentation');
}

/* Admin Interface - display google calendar */
function wp_gc_calendar_page()
{
    global $wpdb;
    $calendar_file = get_option('google_cal_file');
    $table_name = $wpdb->prefix . "google_events";
    $wp_plg_page = admin_url( 'admin.php?page=wp-google-calendar-plg');
    if ($calendar_file == '') { ?>
        <h2>Settings required ! </h2>
        <p><a href="<?php echo admin_url('admin.php?page=google-calendar-settings'); ?>">Go to settings</a></p>
        <?php exit();
    }
    include_once('class.iCalReader.php');
    date_default_timezone_set('US/Eastern'); // set default timezone
    $ical = new ICal($calendar_file);
    $events = $ical->eventsFromRange(true, true);

    foreach ($events as $event) { // for testing purposes
        $ev[] = array(
            'title' => addslashes($event['SUMMARY']),
            'start' => date('Y-m-d\TH:i:s', strtotime($event['DTSTART'])),
            'end' => date('Y-m-d\TH:i:s', strtotime($event['DTEND'])),
            'allDay' => strlen($event['DTSTART']) == 8 ? true : false,
            'busy' => 1
        );
    }
    $e = json_encode($ev);
    $all_events = $wpdb->get_results('select * from ' . $table_name . ' where start_Date >= "'.date('Y-m-d').'" ORDER BY start_Date ASC');
    ?>

    <script src="https://apis.google.com/js/client.js">
    </script>
    <div class="wrap">
        <div style="display:none" class="eventJson" defaultDate="<?php echo date('Y-m-d'); ?>"
             data='<?php echo $e; ?>'></div>


        <h5>Use the Short code <strong style="color:red;">[wp-gc-calendar]</strong> to display your calendar in your
            posts or pages.</h5>
        <h2>Requests for Appoitments</h2>
        <div id="res">
            <table class="widefat fixed wpgc-table" cellspacing="0">
                <thead>
                <tr>
                    <th id="columnname" class="manage-column column-columnname" scope="col">Request BY</th>
                    <th id="columnname" class="manage-column column-columnname" scope="col">Start date</th>
                    <th id="columnname" class="manage-column column-columnname" scope="col">End date</th>
                    <th id="columnname" class="manage-column column-columnname" scope="col">Message</th>
                    <th id="columnname" class="manage-column column-columnname" scope="col">Phone</th>
                    <th id="columnname" class="manage-column column-columnname" scope="col">Action</th>
                </tr>
                </thead>
                <tbody>
                <?php $ev = array();?>
                <?php foreach ($all_events as $event) {
                    ?>
                    <tr class="alternate">
                        <td class="column-columnname"><?php echo esc_attr($event->name) ?></td>
                        <td class="column-columnname"><?php echo esc_attr($event->start_Date).' '.esc_attr($event->start_Time) ?></td>
                        <td class="column-columnname"><?php echo esc_attr($event->end_Date).' '.esc_attr($event->end_Time) ?></td>
                        <td class="column-columnname"><?php echo esc_attr($event->description) ?></td>
                        <td class="column-columnname"><?php echo esc_attr($event->phone) ?></td>
                       <td class="column-columnname">
                            <a href="<?php echo $wp_plg_page . '&d='. esc_attr($event->event_id) ?>" onclick="return confirm('Are you sure you want to delete this Appoitment Request?');" title="Delete Appoitment"><span class="dashicons dashicons-trash"></span></a><span class="dashicons dashicons-upload"></span>
                       </td>
                    </tr>
                <?php } ?>
                <?php
                $e = json_encode($ev);
                ?>
                </tbody>
            </table>
            <div style="display:none" class="eventJson" defaultDate = "<?php echo date('Y-m-d'); ?>" data='<?php echo $e; ?>'></div>

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

        <script>
            jQuery(document).ready(function () {
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
                    select: function (start, end) {
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

/* Admin Setting Page function */
function wp_gc_settings()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'google_api_setting';
    $calendar_file = get_option('google_cal_file');
    if ($_POST) {
        if (isset($_POST['calendar_file'])) {
            $calendar_file = urldecode($_POST['calendar_file']);
            $id_setting = intval($_POST['id_setting']);
            $clientID = sanitize_text_field($_POST['clientID']);
            $calendarID = sanitize_text_field($_POST['calendarID']);
            if (isset($_POST['id_setting'])) {
                $data = array(
                    'id_setting' => $id_setting,
                    'clientID' => $clientID,
                    'calendarID' => $calendarID,
                    'calendarFile' => $calendar_file
                );
                $wpdb->update($table_name, $data, array('id_setting' => $id_setting));
            } else {
                $data = array(
                    'clientID' => $clientID,
                    'calendarID' => $calendarID,
                    'calendarFile' => $calendar_file
                );
                $wpdb->insert($table_name, $data);
            }
            add_option('google_cal_file', $calendar_file);
        }
    }
    $settings = $wpdb->get_row('select * from ' . $table_name);
    ?>
    <div class="wrap">
        <form action="" method="post" id="insert-event">
            <?php if ($settings) { ?>
                <input type="hidden" id="id_setting" name="id_setting"
                       value="<?php if ($settings) {
                           echo esc_attr($settings->id_setting);
                       } ?>"/>
            <?php } ?>
            <table class="wpgc-setting-table">
                <tr>
                    <td colspan="2" class="entry-view-field-name">Settings</td>
                </tr>
                <tr>
                    <td>
                        <h3><label for="clientID">Google Calendar ICAL URL </label></h3>
                    </td>
                    <td>
                        <input type="text" id="calendar_file" class="input" name="calendar_file"
                               value="<?php if ($settings) {
                                   echo esc_attr($settings->calendarFile);
                               } ?>"/>
                    </td>
                </tr>
                <tr>
                    <td>
                        <h3><label for="clientID">Client ID </label></h3>
                    </td>
                    <td>
                        <input type="text" id="clientID" class="input" name="clientID"
                               value="<?php if ($settings) {
                                   echo esc_attr($settings->clientID);
                               } ?>"/>
                    </td>
                </tr>
                <tr>
                    <td>
                        <h3><label for="calendarID">Calendar ID </label></h3>
                    </td>
                    <td>
                        <input type="text" id="calendarID" class="input" name="calendarID"
                               value="<?php if ($settings) {
                                   echo esc_attr($settings->calendarID);
                               } ?>"/>
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td colspan="2">
                        <p>
                            <input type="submit" name="submit" value="Save" class="wpgc-mdl-button"
                                   style="float:right; margin: 0 20px 10px 0"/>
                            <a href="<?php echo admin_url('admin.php?page=wp-google-calendar-plg'); ?>"
                               class="wpgc-mdl-button" style="float:right; margin: 0 20px 10px 0"/>Cancel</a>
                        </p>

                    </td>
                </tr>
            </table>
            <?php wp_nonce_field('calendar_event'); ?>
        </form>
    </div>
<?php
}

/* Front-end form to add event */
function wp_gc_my_calendar()
{
    ?>
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
                    <input id="pPhone" type="text" name="pPhone" required>
                </p>

                <p class="txt-fld">
                    <label for="pNotes">Your Message</label>
                    <textarea id="pNotes" name="pNotes" rows="10"></textarea>
                </p>

                <p class="btn-fld">
                    <input id="start_date" name="start_date" type="hidden">
                    <input id="end_date" name="end_date" type="hidden">
                    <input id="savebtn" class="btn-frm" type="submit" value="Book Appointment">
                </p>
            </fieldset>
        </form>
        <p id="eventInfo"></p>
    </div>
    <?php
    include_once('class.iCalReader.php');
    date_default_timezone_set('US/Eastern'); // set default timezone
    $calendar_file = get_option('google_cal_file');
    if ($calendar_file == '') {
        ?>
        <h2>Settings required ! </h2>
        <p><a href="<?php echo admin_url('admin.php?page=wp-google-calendar-settings'); ?>">Go to settings</a></p>
        <?php exit();
    }
    $ical = new ICal($calendar_file);
    $events = $ical->eventsFromRange(true, true);

    foreach ($events as $event) {
        if (isset($event['TRANSP'])) {
            $key = "url";
            $value = "javascript: showForm()";
        } else {
            $key = "busy";
            $value = "1";
        }
        $ev[] = array(
            'title' => addslashes($event['SUMMARY']),
            'start' => date('Y-m-d\TH:i:s', strtotime($event['DTSTART'])),
            'end' => date('Y-m-d\TH:i:s', strtotime($event['DTEND'])),
            'allDay' => strlen($event['DTSTART']) == 8 ? true : false,
            $key => $value
        );
    }
    $e = json_encode($ev);
    ?>
    <div style="display:none" class="eventJson" defaultDate="<?php echo date('Y-m-d'); ?>"
         data='<?php echo $e; ?>'></div>
    <?php
    return "<div id='calendar'></div>";
}

function wp_gc_my_enqueue()
{

    wp_enqueue_script('ajax-script', plugins_url('js/mh-gcalendar.js', __FILE__));
    wp_localize_script('ajax-script', 'my_ajax_object',
        array('ajax_url' => admin_url('admin-ajax.php')));
}

/**
 * Add Appointment in DB
 */
function add_event_action_callback()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'google_events';
    $frm_data = array();
    parse_str($_POST['frm_data'], $frm_data);

    $start_date_time = explode(" ", $frm_data['start_date']);
    $end_date_time = explode(" ", $frm_data['end_date']);
    $message = $frm_data['pNotes'];
    $phone = $frm_data['pPhone'];
    $email = $frm_data['pEmail'];
    $name = $frm_data['pName'];

    $event = array(
        'name' => sanitize_text_field($name),
        'phone' => sanitize_text_field($phone),
        'email' => sanitize_text_field($email),
        'description' => sanitize_text_field($message),
        'start_Date' => sanitize_text_field($start_date_time[0]),
        'start_Time' => sanitize_text_field($start_date_time[1]),
        'end_Date' => sanitize_text_field($end_date_time[0]),
        'end_Time' => sanitize_text_field($end_date_time[1])
    );
    $result = $wpdb->insert($table_name, $event);
    if ($result === false) {
        echo "Error in Insert!!";
    }
    wp_die(); // this is required to terminate immediately and return a proper response
}

/**
 * Function to delete requested appointment
 */
function wp_gc_calendar_delete_appointment()
    {
        if(isset($_GET['d'])) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'google_events';
            $d = intval($_GET['d']);
            $wpdb->delete($table_name, array('event_id' => $d), array('%d'));
            $url = admin_url('admin.php?page=wp-google-calendar-plg');
        ?>
        <meta http-equiv="refresh" content="0;URL='<?php echo $url; ?>'" />
    <?php
   }}
add_action('admin_init','wp_gc_calendar_delete_appointment');


/**
 * Uninstall Plugin
 */
if ( function_exists('register_uninstall_hook') )
    register_uninstall_hook(__FILE__, 'wp_gc_calendar_uninstall');

function wp_gc_calendar_uninstall() {
    global $wpdb;
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}google_events" );
}
?>

