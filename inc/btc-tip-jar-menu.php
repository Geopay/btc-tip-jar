<?php

class Btc_Tip_Jar_Menu {
	public $settings;

	public function __construct( $defaults = array() ) {

		$this->settings = get_option( 'btc-tip-jar_options', $defaults );

		add_action( 'admin_menu', array( &$this, 'menu' ) );
		add_action( 'admin_init', array( &$this, 'menu_settings' ) );

	}
public function menu() {

		add_options_page(
			__( 'Bitcoin Tip Jar', 'btc-tip-jar' ),
			__( 'Bitcoin Tip Jar', 'btc-tip-jar' ),
			'manage_options',
			__FILE__,
			array( &$this, 'menu_page' )
		);

	}
	public function menu_settings() {
		register_setting( 'btc-tip-jar_options', 'btc-tip-jar_options' );
	}
	public function menu_page() {

		echo '<div class="wrap">';
		printf( '<h2>%s</h2>', __( 'Bitcoin Tip Jar Settings', 'btc-tip-jar' ) );
		echo '<form method="post" action="options.php">';
		settings_fields( 'btc-tip-jar_options' );
		do_settings_fields( 'btc-tip-jar_options', 'btc-tip-jar_options' );
		echo '<table class="form-table">';

		$this->menu_page_item(
			'rpcssl',
			__( 'Secure socket', 'btc-tip-jar'  )
		);

		$this->menu_page_item(
			'rpcconnect',
			__( 'Address', 'btc-tip-jar' )
		);

		$this->menu_page_item(
			'rpcport',
			__( 'Port', 'btc-tip-jar' )
		);

		$this->menu_page_item(
			'rpcuser',
			__( 'Username', 'btc-tip-jar' )
		);

		$this->menu_page_item(
			'rpcpassword',
			__( 'Password', 'btc-tip-jar' )
		);

		$this->menu_page_item(
			'rpcwallet',
			__( 'Wallet Password', 'btc-tip-jar' )
		);

		$this->menu_page_item(
			'fx',
			__( 'Conversion Currency', 'btc-tip-jar' )
		);

		$this->menu_page_item(
			'decimals',
			__( 'Bitcoin Decimals', 'btc-tip-jar' )
		);

		echo '</table>';
		submit_button();
		echo '</form></div>';
	}
private function menu_page_item( $item, $label ) {

		echo '<tr valign="top">';
		echo '<th scope="row"><label for="btc-tip-jar_options[' . $item . ']">' . $label . '</label></th>';
		echo '<td>';

		if ( $item == 'rpcssl' ) {
			echo '<input type="checkbox" class="regular-text" ';
			echo 'name="btc-tip-jar_options[' . $item . ']" id="btc-tip-jar_options[' . $item . ']" ';
			if ( !empty( $this->settings[$item] ) ) {
				checked( $this->settings[$item] );
			}
			echo 'value="1" />';
		} else {
			echo '<input type="text" class="regular-text" ';
			echo 'name="btc-tip-jar_options[' . $item . ']" id="btc-tip-jar_options[' . $item . ']" ';
			echo 'value="' . $this->settings[$item] . '" />';
		}

		echo '</td>';
		echo '</tr>';

	}
}

?>
