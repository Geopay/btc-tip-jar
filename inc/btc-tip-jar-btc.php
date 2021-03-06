<?php

class Btc_Tip_Jar_Btc {
	private $settings;

	private $settings_menu;

	private $database;

	private $connect_string;

	public function __construct( $settings, $settings_menu, $database ) {
		$this->settings      = $settings;
		$this->settings_menu = $settings_menu;
		$this->database      = $database;

		if ( $this->settings_menu['rpcssl'] ) {
			$schema = 'https';
		} else {
			$schema = 'http';
		}

		$this->connect_string  = "{$schema}://";
		$this->connect_string .= "{$this->settings_menu['rpcuser']}:";
		$this->connect_string .= "{$this->settings_menu['rpcpassword']}@";
		$this->connect_string .= "{$this->settings_menu['rpcconnect']}:";
		$this->connect_string .= "{$this->settings_menu['rpcport']}";

	}
	public function connect() {

		require_once(
			plugin_dir_path( __FILE__ )
			.
			'../lib/json-rpc-php/jsonRPCClient.php'
		);

		if (
			empty( $this->settings_menu['rpcuser'] )
			||
			empty( $this->settings_menu['rpcpassword'] )
			||
			empty( $this->settings_menu['rpcwallet'] )
		) {
			return false;
		}

		try {
			$this->settings['debug'] = false;
			$connection = new jsonRPCClient( $this->connect_string, $this->settings['debug'] );

			$connection->walletpassphrase(
				$this->settings_menu['rpcwallet'],
				intval( $this->settings['rpctimeout'] )
			);

			return $connection;
		} catch( Exception $e ) {
			error_log( $e->getMessage() );
			return false;
		}

	}
	public function refresh_tx_history() {
		$connection = $this->connect();

		if ( !$connection ) {
			return false;
		}

		try {
			if ( !empty( $this->settings['lastblock'] ) ) {
				$history = $connection->listsinceblock( $this->settings['lastblock'] );
			} else {
				$history = $connection->listtransactions(
					'',
					$this->settings['list_tx_max'],
					0
				);
			}
		} catch( Exception $e ) {
			error_log( $e->getMessage() );
		}

		$this->settings['lastblock'] = $history['lastblock'];
		update_option( 'btc-tip-jar', $this->settings );

		if ( !empty( $history['transactions'] ) ) {
			$this->database->insert_transactions( $history['transactions'] );
		}
	}
	private function get_account_label( $user ) {
		$label  = home_url( '/' );
		$label .= 'btc-tip-jar/user_' . $user;

		return $label;
	}
	public function get_user_address( $user ) {

		$label = $this->get_account_label( $user );

		$user_address = get_user_meta(
			$user, 'btc-tip-jar_account', true
		);

		if ( empty( $user_address ) ) {
			$btc = $this->connect();
			try {
				$getaccountaddress = $btc->getaccountaddress( $label );
				$user_address = array();
				$user_address['label']   = $label;
				$user_address['address'] = $getaccountaddress;

				update_user_meta(
					$user, 'btc-tip-jar_account', $user_address
				);

			} catch( Exception $e ) {
				error_log( $e->getMessage() );
			}
		} else {
			return $user_address;
		}
	}
	public function get_post_address_user( $post_id, $author_id, $user_id ) {

		$author_account = $this->get_user_address( $author_id );

		$address = $this->database->get_user_address_query(
			$post_id,
			$author_id,
			$user_id
		);

		if ( !empty( $address ) ) {
			return $address;
		} else {
			$btc = $this->connect();
			try {
				$getnewaddress = $btc->getnewaddress( $author_account['label'] );

				$this->database->insert_post_address_user(
					$post_id,
					$author_id,
					$user_id,
					$getnewaddress
				);

				return $getnewaddress;
			} catch( Exception $e ) {
				error_log( $e->getMessage() );
				return false;
			}
		}
	}
	public function get_post_address_anonymous( $post_id, $author ) {

		$author_account = $this->get_user_address( $author );

		$anonymous_address = get_post_meta(
			$post_id, 'btc-tip-jar_anonymous', true
		);

		if ( empty( $anonymous_address ) ) {
			$btc = $this->connect();
			try {
				$getnewaddress = $btc->getnewaddress( $author_account['label'] );
			} catch( Exception $e ) {
				error_log( $e->getMessage() );
			}
			$anonymous_address = $getnewaddress;

			update_post_meta(
				$post_id, 'btc-tip-jar_anonymous', $anonymous_address
			);
		}

		return $anonymous_address;
	}
	public function get_user_balance( $user_id ) {
		$label = $this->get_account_label( $user_id );

		$btc = $this->connect();

		try {
			$user_balance = $btc->getbalance( $label, 0 );
			return $user_balance;
		} catch( Exception $e ) {
			error_log( $e->getMessage() );
			return 0.0;
		}
	}
	public function do_withdrawal( $user, $address, $amount ) {
		$btc = $this->connect();

		try {
			$validateaddress = $btc->validateaddress( $address );

			if ( !$validateaddress['isvalid'] ) {
				return 'INVALID_ADDRESS';
			} elseif ( $validateaddress['ismine'] ) {
				return 'INTERNAL_TRANSFER';
			}

			$label = $this->get_account_label( $user );

			$getbalance = $btc->getbalance( $label );

			if ( $amount > $getbalance ) {
				return 'OVERDRAFT';
			}

			$sendfrom = $btc->sendfrom(
				$label,
				$address,
				$amount,
				1,
				$label,
				'btc-tip-jar'
			);

			$transaction = $sendfrom['result'];

			return 'WITHDRAWN';

		} catch( Exception $e ) {
			error_log( $e->getMessage() );
			return 'CONNECTION';
		}
	}
}

?>
