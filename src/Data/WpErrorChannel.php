<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Inpsyde wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog\Data;

use Inpsyde\Wonolog\Channels;

/**
 * Class that is used to "guess" a proper channel from a WP_Error object based on its error codes.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
class WpErrorChannel {

	/**
	 * @var \WP_Error
	 */
	private $error;

	/**
	 * @var string
	 */
	private $channel = '';

	/**
	 * @param \WP_Error $error
	 * @param string    $channel
	 *
	 * @return WpErrorChannel
	 */
	public static function for_error( \WP_Error $error, $channel = '' ) {

		$instance        = new static;
		$instance->error = $error;
		$channel and $instance->channel = apply_filters( 'wonolog.wp-error-channel', $channel, $error );

		return $instance;
	}

	/**
	 * @return string
	 */
	public function channel() {

		if ( $this->channel ) {
			return $this->channel;
		}

		$channel = '';
		$codes   = $this->error->get_error_codes();

		while ( ! $channel && $codes ) {
			$code    = array_shift( $codes );
			$channel = $this->maybe_db_channel( $code );
			$channel or $channel = $this->maybe_http_channel( $code );
			$channel or $channel = $this->maybe_security_channel( $code );
		}

		$channel or $channel = Channels::DEBUG;

		$filtered = apply_filters( 'wonolog.wp-error-channel', $channel, $this->error );
		is_string( $filtered ) and $channel = $filtered;
		$this->channel = $channel;

		return $this->channel;
	}

	/**
	 * @param string $code
	 *
	 * @return string
	 */
	private function maybe_db_channel( $code ) {

		if ( stripos( $code, 'wpdb' ) !== FALSE || preg_match( '/(\b|_)db(\b|_)/i', $code ) ) {
			return Channels::DB;
		}

		return '';
	}

	/**
	 * @param string $code
	 *
	 * @return string
	 */
	private function maybe_http_channel( $code ) {

		if (
			stripos( $code, 'http' ) !== FALSE
			|| stripos( $code, 'request' ) !== FALSE
			|| stripos( $code, 'download' ) !== FALSE
			|| stripos( $code, 'upload' ) !== FALSE
			|| stripos( $code, 'simplepie' ) !== FALSE
			|| stripos( $code, 'mail' ) !== FALSE
			|| stripos( $code, 'rest' ) !== FALSE
		) {
			return Channels::HTTP;
		}

		return '';
	}

	/**
	 * @param string $code
	 *
	 * @return string
	 */
	private function maybe_security_channel( $code ) {

		if (
			stripos( $code, 'cookie' ) !== FALSE
			|| stripos( $code, 'login' ) !== FALSE
			|| stripos( $code, 'authentication' ) !== FALSE
		) {
			return Channels::SECURITY;
		}

		return '';
	}

}