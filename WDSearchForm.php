<?php
/*
Plugin Name: WD Search Widget
Plugin URI: http://www.webdesk.co.il/search-widget/
Description: A simple search widget with Autocomplete capability
Version: 1.2.3
Author: Yoav Kadosh
Author URI: http://www.webdesk.co.il/
Author Email: yoavks@gmail.com
Text Domain: WDSearchWidget-locale
Domain Path: /languages/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Copyright 2012 WebDesk (admin@webdesk.co.il)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class WDSearchWidget extends WP_Widget {
	// Public vars
	protected $widgetName = 'WDSearchWidget';
	protected $widgetFancyName = 'WD Search Widget';
	protected $textDomain = 'WDSearchWidget-locale';
	private $options; // Holds widget options
	
	/*--------------------------------------------------*/
	/* Constructor
	/*--------------------------------------------------*/

	/**
	 * Specifies the classname and description, instantiates the widget, 
	 * loads localization files, and includes necessary stylesheets and JavaScript.
	 */
	public function __construct() {
				
		// load plugin text domain
		add_action( 'init', array( $this, 'widget_textdomain' ) );
				
		// Initiate Ajax
		$this->initAjax();
				
		// Hooks fired when the Widget is activated and deactivated
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		parent::__construct(
			$this->widgetName ,
			__( $this->widgetFancyName , $this->textDomain ), // This is shown in the 'widgets' panel
			array(
				'classname'		=>	$this->widgetName ,
				'description'	=>	__( 'A simple search widget with Autocomplete capability.' , $this->textDomain )
			)
		);

		// Register admin styles and scripts
		add_action( 'admin_print_styles', array( $this, 'register_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_scripts' ) );

		// Register site styles and scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'register_widget_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_widget_scripts' ) );
		
		// Fetch options from database
		$this->options = $this->get_widget_option('widget_'.$this->widgetName);

	} // end constructor
	
	// Get widget options
	function get_widget_option($option_name){
		if(!get_option( $option_name ))
			return null;
		
		$options = array_filter( get_option( $option_name ) );
		unset( $options['_multiwidget'] );
		
		foreach( $options as $key => $val )
			$options = $options[$key];
		
		return $options;
	}
	
	/*--------------------------------------------------*/
	/* Autocomplete Callback Function
	/*--------------------------------------------------*/
	public function acCallback() {
		
		// Verify the ajax request
		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( $_REQUEST['nonce'], 'ajax-example-nonce' ) )
			die ( 'Invalid Nonce' );
		header( "Content-Type: application/json" );
		
		// Query the database
		$numResults = $this->options['numResults'];
		$type = $this->options['acType'];
		global $wpdb;
		$term = sanitize_text_field($_GET['term']);
		if($type == 'terms') {
			$query = 'SELECT term.name as post_title, term.slug as guid, tax.taxonomy, 0 AS content_frequency, 0 AS title_frequency FROM '.$wpdb->term_taxonomy.' tax '.
				'LEFT JOIN '.$wpdb->terms.' term ON term.term_id = tax.term_id WHERE 1 = 1 '.
				'AND term.name LIKE "%'.$term.'%" '.
				'ORDER BY tax.count DESC '.
				'LIMIT 0, '.$numResults;
			$tempTerms = $wpdb->get_results($query);
			foreach($tempTerms as $term) {
				$resultsTerms[] = array(
					'label' => $term->post_title,
					'value' => $term,
					'url' => get_term_link($term->guid, $term->taxonomy),
				);
			}
		}
		if($type == 'posts') {
			$tempPosts = get_posts(array(
				's' => $term,
				'numberposts' => $numResults ,
				'post_type' => 'post',
			));
			foreach($tempPosts as $post) {
				$resultsTerms[] = array(
					'label' => $post->post_title,
					'value' => $term,
					'url' => get_permalink($post->ID)
				);
			}
		}
		
		// Return data as JSON back to the script
		echo json_encode($resultsTerms);
		exit;
	}
	
	// Initiate the callback function
	public function initAjax() {
		// Logged in users
		add_action('wp_ajax_autocompleteCallback', array($this, 'acCallback'));
		// No privileges users
		add_action('wp_ajax_nopriv_autocompleteCallback', array($this, 'acCallback'));
	}
		
	/*--------------------------------------------------*/
	/* Widget API Functions
	/*--------------------------------------------------*/
	
	/**
	 * Outputs the content of the widget.
	 *
	 * @param	array	args		The array of form elements
	 * @param	array	instance	The current instance of the widget
	 */
	public function widget( $args, $instance ) {

		extract( $args, EXTR_SKIP );
		
		// Variables from the widget settings.
		$title = apply_filters('widget_title', $instance['title'] );
		$dString = $instance['string'];
		$showWrapper = $instance['show_wrapper'];
		$showPoweredBy = $instance['show_powered_by'];
		$icon = $instance['icon'];
		
		// Display the widget wrapper and title 
		if ( $showWrapper )
			echo $before_widget . $before_title . $title . $after_title;
		?>
		<div class="wd_search">
			<div class="shadowBackground"></div>
			<form method="get" action="<?php echo site_url(); ?>">
				<div class="inputWrapper">
					<input id="tags" type="text" name="s" class="idleField" value="<?php echo $dString ?>" placeholder="<?php echo $dString ?>">
				</div>
				<input type="submit" value="" name="searchsubmit" class="<?php echo $icon; ?>">
			</form>
			<?php if($showPoweredBy) { ?>
			<p class="poweredBy">Powered by <a href="http://www.webdesk.co.il/" title="WebDesk web development">WebDesk</a></p>
			<?php } ?>
			<div class="acResults"></div>
		</div>
		<?php // Display the last part of the wrapper
		echo ($showWrapper ? $after_widget : '');

	} // end widget

	/**
	 * Processes the widget's options to be saved.
	 *
	 * @param	array	new_instance	The previous instance of values before the update.
	 * @param	array	old_instance	The new instance of values to be generated via the update.
	 */
	public function update( $new_instance, $old_instance ) {

		$instance = $old_instance;
		
		//Strip tags to remove HTML 
		$instance['string'] = strip_tags( $new_instance['string'] );
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['show_wrapper'] = strip_tags( $new_instance['show_wrapper'] );
		$instance['animate'] = strip_tags( $new_instance['animate'] );
		$instance['show_powered_by'] = strip_tags( $new_instance['show_powered_by'] );
		$instance['icon'] = strip_tags( $new_instance['icon'] );
		$instance['numResults'] = strip_tags( $new_instance['numResults'] );
		$instance['minLength'] = strip_tags( $new_instance['minLength'] );
		$instance['enableAC'] = strip_tags( $new_instance['enableAC'] );
		$instance['acType'] = strip_tags( $new_instance['acType'] );
		

		return $instance;

	} // end widget


	/* Make Radio Button function */
	// Creates custom radio buttons for the admin panel
	function makeRadioBtn($id, $currentOption) { ?>
		
		<label for="<?php echo $id; ?>" class="wdsIcons <?php echo $id; ?>">
        	<input class="widefat" id="<?php echo $id; ?>" name="<?php echo $this->get_field_name( 'icon' ); ?>" type="radio" <?php checked( $currentOption == $id ); ?> value="<?php echo $id; ?>" />
        </label>
		
	<?php } 
	
	/**
	 * Generates the administration form for the widget.
	 *
	 * @param	array	instance	The array of keys and values for the widget.
	 */
	public function form( $instance ) {

    	// Define default values for your variables
		$defaults = array( 'title' => __( $this->widgetName , $this->textDomain ), 
						   'string' => __('Search', $this->textDomain ), 
						   'show_wrapper' => true, 
						   'animate' => true, 
						   'show_powered_by' => true,
						   'icon' => 'icon1' ,
						   'numResults' => 10 ,
						   'minLength' => 2 ,
						   'enableAC' => false ,
						   'acType' => 'posts' );
		
		// Initiate default values
		$instance = wp_parse_args( 
			(array) $instance, $defaults 
		);
		?>
		
		<?php /* Widget Title: Text Input.*/ ?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:', $this->textDomain); ?></label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $instance['title']; ?>" class="widefat" />
		</p>
		
		<?php /* Default String */ ?>
		<p>
			<label for="<?php echo $this->get_field_id( 'string' ); ?>"><?php _e('Default string:', $this->textDomain); ?></label>
			<input id="<?php echo $this->get_field_id( 'string' ); ?>" name="<?php echo $this->get_field_name( 'string' ); ?>" type="text" value="<?php echo $instance['string']; ?>" class="widefat" />
		</p>
		
		<?php /* Show_wrapper checkbox */ ?>
		<p>
			<input class="checkbox" type="checkbox" <?php checked( $instance['show_wrapper'], 'on' ); ?> id="<?php echo $this->get_field_id( 'show_wrapper' ); ?>" name="<?php echo $this->get_field_name( 'show_wrapper' ); ?>" /> 
			<label for="<?php echo $this->get_field_id( 'show_wrapper' ); ?>"><?php _e('Display widget wrapper', $this->textDomain); ?></label>
		</p>
		
		<?php /* Animate checkbox */ ?>
		<p>
			<input class="checkbox" type="checkbox" <?php checked( $instance['animate'], 'on' ); ?> id="<?php echo $this->get_field_id( 'animate' ); ?>" name="<?php echo $this->get_field_name( 'animate' ); ?>" /> 
			<label for="<?php echo $this->get_field_id( 'animate' ); ?>"><?php _e('Animate', $this->textDomain); ?></label>
		</p>
		
		<?php /* show_powered_by checkbox */ ?>
		<p>
			<input class="checkbox" type="checkbox" <?php checked( $instance['show_powered_by'], 'on' ); ?> id="<?php echo $this->get_field_id( 'show_powered_by' ); ?>" name="<?php echo $this->get_field_name( 'show_powered_by' ); ?>" /> 
			<label for="<?php echo $this->get_field_id( 'show_powered_by' ); ?>"><?php _e('Show "powered by" link', $this->textDomain); ?></label>
		</p>
		
		<?php /* Icon set selection */ ?>
		<p>
        	<fieldset class="wd">
				<legend><?php _e('Choose an icon', $this->textDomain ); ?></legend>
    			<?php 
    			$this->makeRadioBtn('icon1', $instance['icon']);
    			$this->makeRadioBtn('icon2', $instance['icon']);
    			$this->makeRadioBtn('icon3', $instance['icon']);
    			$this->makeRadioBtn('icon4', $instance['icon']); 
    			$this->makeRadioBtn('icon5', $instance['icon']);
    			$this->makeRadioBtn('icon6', $instance['icon']);
    			?>
    		</fieldset>
    	</p>
    	
    	<?php /* Autocomplete checkbox */ ?>
		<p>
			<label>
				<input class="checkbox enableAC" type="checkbox" <?php checked( $instance['enableAC'], 'on' ); ?> id="<?php echo $this->get_field_id( 'enableAC' ); ?>" name="<?php echo $this->get_field_name( 'enableAC' ); ?>" /> 
				<?php _e('Enable Autocomplete', $this->textDomain); ?>
			</label>
		</p>
		
    	<fieldset class="wd" id="autocomplete" <?php disabled( $instance['enableAC'], false ); ?>>
    	<legend>Auto-Complete</legend>
    	
    	<?php /* Results type */ ?>
		<p>	
			<label for="<?php echo $this->get_field_id( 'acType' ); ?>"><?php _e('Results type:', $this->textDomain); ?></label>
			<select id="<?php echo $this->get_field_id( 'acType' ); ?>" name="<?php echo $this->get_field_name( 'acType' ); ?>" >
				<option <?php selected( $instance['acType'], 'posts' ); ?> value="posts"><?php _e('Posts', $this->textDomain); ?></option>
				<option <?php selected( $instance['acType'], 'terms' ); ?> value="terms"><?php _e('Terms', $this->textDomain); ?></option>
            </select>
        </p>
    	
    	<?php /* Number of results */ ?>
		<p>
			<label for="<?php echo $this->get_field_id( 'numResults' ); ?>"><?php _e('Max results:', $this->textDomain); ?></label>
			<input id="<?php echo $this->get_field_id( 'numResults' ); ?>" name="<?php echo $this->get_field_name( 'numResults' ); ?>" type="text" value="<?php echo $instance['numResults']; ?>" class="widefat" />
		</p>
		
		<?php /* Min length */ ?>
		<p>
			<label for="<?php echo $this->get_field_id( 'minLength' ); ?>"><?php _e('Minimum characters to activate:', $this->textDomain); ?></label>
			<input id="<?php echo $this->get_field_id( 'minLength' ); ?>" name="<?php echo $this->get_field_name( 'minLength' ); ?>" type="text" value="<?php echo $instance['minLength']; ?>" class="widefat" />
		</p>
		</fieldset>
		
	<?php

	} // end form

	/*--------------------------------------------------*/
	/* Public Functions
	/*--------------------------------------------------*/

	/**
	 * Loads the Widget's text domain for localization and translation.
	 */
	public function widget_textdomain() {
		
		// Make plugin available for translation
		load_plugin_textdomain( $this->textDomain , false, plugin_dir_path( __FILE__ ) . '/languages/' );
		
	} // end widget_textdomain

	/**
	 * Fired when the plugin is activated.
	 *
	 * @param		boolean	$network_wide	True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog.
	 */
	public function activate( $network_wide ) {
		// TODO define activation functionality here
	} // end activate

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @param	boolean	$network_wide	True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog 
	 */
	public function deactivate( $network_wide ) {
		// This will remove the db saved options
		delete_option( 'widget_'.$this->widgetName );		
	} // end deactivate

	/**
	 * Registers and enqueues admin-specific styles.
	 */
	public function register_admin_styles() {

		wp_enqueue_style( $this->widgetName.'-admin-styles', plugins_url( 'admin.css', __FILE__ ) );

	} // end register_admin_styles

	/**
	 * Registers and enqueues admin-specific JavaScript.
	 */	
	public function register_admin_scripts() {

		wp_enqueue_script( $this->widgetName.'-admin-script', plugins_url( 'admin.js', __FILE__ ) );

	} // end register_admin_scripts

	/**
	 * Registers and enqueues widget-specific styles.
	 */
	public function register_widget_styles() {

		// Load the search form style
    	wp_register_style( $this->widgetName.'-style', plugins_url('style.css', __FILE__));
    	wp_enqueue_style( $this->widgetName.'-style');
    	
    	// Load the Autocomplete style
    	wp_register_style( $this->widgetName.'-ac-style', plugins_url('ac-style.css', __FILE__));
    	wp_enqueue_style( $this->widgetName.'-ac-style');

	} // end register_widget_styles

	/**
	 * Registers and enqueues widget-specific scripts.
	 */
	public function register_widget_scripts() {
	
		// Load jQuery.Autocomplete
		wp_enqueue_script('jquery');
		wp_enqueue_script("jquery-ui-autocomplete");
		
		// Load the search script
		wp_register_script( 'wds_search', plugins_url( 'script.js', __FILE__ ), array('jquery'));  
		wp_enqueue_script('wds_search');
		
		//Add custom options
		$options = $this->options;
		$options['ajaxUrl'] = admin_url('admin-ajax.php');
		$options['nonce'] = wp_create_nonce( 'ajax-example-nonce' );
		
		// In the following, the second argument is the object name.
		// it can be accessed by writing `object_name.variable_name`
		wp_localize_script('wds_search', 'wdsf_options', $options);
		
	} // end register_widget_scripts

} // end class

// Register widget
add_action( 'widgets_init', create_function( '', 'register_widget("WDSearchWidget");' ) ); 