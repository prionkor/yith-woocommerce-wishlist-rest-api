<?php

namespace YITH\Wishlist;

use WP_REST_Response;

if( ! class_exists( 'YITH\Wishlist\Rest' ) ) {
	final class Rest {
		const REST_NAMESPACE = 'yith/wishlist';
		const REST_VERSION = 'v1';

		protected static $wishlist_routes = [
			'get' => [ // get list of wishlist for given user
				'route' => '/wishlists',
				'methods' => 'GET',
				'callback' => [ __CLASS__, 'get' ],
				'permission_callback' => [ __CLASS__, 'check_auth' ],
			],
			'post' => [ // create/update a whishlist
				'route' => '/wishlists',
				'methods' => 'POST',
				'callback' => [ __CLASS__, 'post' ],
				'permission_callback' => [ __CLASS__, 'check_write_cap' ],
			],
			'get_single' => [ // get single wishlist for given user
				'route' => '/wishlists/(?P<id>\d+)',
				'methods' => 'GET',
				'callback' => [ __CLASS__, 'get_single' ],
				'permission_callback' => [ __CLASS__, 'check_read_cap' ],
			],
			'update_single' => [ // update single wishlist for given user
				'route' => '/wishlists/(?P<id>\d+)',
				'methods' => 'PUT',
				'callback' => [ __CLASS__, 'update_single' ],
				'permission_callback' => [ __CLASS__, 'check_write_cap' ],
			],
			'delete' => [ // delete a wishlist
				'route' => '/wishlists/(?P<id>\d+)',
				'methods' => 'DELETE',
				'callback' => [ __CLASS__, 'delete' ],
				'permission_callback' => [ __CLASS__, 'check_write_cap' ],
			],
			'add_product' => [ // add a product to wishlist
				'route' => '/wishlists/(?P<id>\d+)/product/(?P<product_id>\d+)',
				'methods' => 'POST',
				'callback' => [ __CLASS__, 'add_product' ],
				'permission_callback' => [ __CLASS__, 'check_write_cap' ],
			],
			'remove_product' => [ // remove a product from wishlist
				'route' => '/wishlists/(?P<id>\d+)/product/(?P<product_id>\d+)',
				'methods' => 'DELETE',
				'callback' => [ __CLASS__, 'remove_product' ],
				'permission_callback' => [ __CLASS__, 'check_write_cap' ],
			],
		];

		public static function init() {
			self::register_routes();
		}

		protected static function register_routes(){
			do_action('yith_rest_wishlist_before_register_route');

			$wishlist_routes = apply_filters( 'yith_rest_wishlist_routes', self::$wishlist_routes );

			$prefix = self::REST_NAMESPACE . '/' . self::REST_VERSION;
			foreach( $wishlist_routes as $args ) {
				$route = $args['route'];
				unset( $args['route'] );
				register_rest_route( $prefix, $route, $args );
			}

			do_action('yith_rest_wishlist_after_register_route');
		}

		/**
		 * Get array of wishlists for current user
		 */
		public static function get() {

			$user_id = get_current_user_id();

			try {
				$results = \WC_Data_Store::load( 'wishlist' )->query( [ 'user_id' => $user_id, 'session_id' => false ] );
			} catch( \Exception $e ){
				// return error response
				return new \WP_REST_Response(array('status' => 500, 'error' => $e->getMessage() ), 500);
			}

			if( empty( $results ) ) {
				return [];
			}

			$wls = [];
			foreach( $results as $wl ) {
				$wls[] = $wl->get_data();
			}

			return new \WP_REST_Response( $wls );

		}

		/**
		 * Creates a wishlist for current user
		 */
		public static function post() {

		}

		/**
		 * Updates a wishlist for current user
		 */
		public static function update_single( $request ) {
			$id = isset( $request['id'] ) ? $request['id'] : 0;
			$product_ids = isset( $request['product_ids'] ) ? $request['product_ids'] : 0;

			$id = isset( $request['id'] ) ? $request['id'] : 0;

			if( ! $id || ! $product_ids ) {
				return new \WP_REST_Response( array( 'status' => 422, 'error' => 'Invalid id'), 422);
			}

			$wl = new \YITH_WCWL_Wishlist( $id );
		}

		/**
		 * Retrive a single wishlist item by id
		 * @return \WP_REST_Response
		 */
		public static function get_single( $request ) {

			$id = isset($request['id']) ? $request['id'] : 0;

			if( ! $id ) {
				return new \WP_REST_Response( array( 'status' => 422, 'error' => 'Invalid id'), 422);
			}

			$wl = new \YITH_WCWL_Wishlist( $id );

			if( ! $wl ) {
				return (new self)->err_404();
			}

			return new \WP_REST_Response( $wl->get_data() );
		}


		/**
		 * Deletes a wishlist
		 * @return \WP_REST_Response
		 */
		public static function delete( $request ){
			$id = $request['id'];
			return [ 'id' => $id ];
		}

		/**
		 * Adds a product to given wishlist
		 * @return \WP_REST_Response
		 */
		public static function add_product( $request ) {

			$wishlist_id = $request['id'] ? (int) $request['id'] : 0;
			$quantity = $request['quantity'] ? (int) $request['quantity'] : 1;
			$product_id = $request['product_id'] ? (int) $request['product_id'] : 0;

			if( ! $product_id ){
				return (new self)->err_404();
			}

			$args = [
				'add_to_wishlist' => $product_id,
				'user_id' => get_current_user_id(),
				'quantity' => $quantity,
				'wishlist_id' => $wishlist_id,
			];

			if( $wishlist_id ) {
				$args['wishlist_id'] = $wishlist_id;
			}

			try {
				YITH_WCWL()->add( $args );
			} catch ( \YITH_WCWL_Exception $e ) {
				return new \WP_REST_Response(array('status' => 422, 'error' => $e->getMessage() ), 422);
			} catch ( \Exception $e ) {
				return new \WP_REST_Response(array('status' => 500, 'error' => $e->getMessage() ), 500);
			}

			// successful! return updated wishlist
			$wl = new \YITH_WCWL_Wishlist( $wishlist_id );
			return new \WP_REST_Response( $wl->get_data() );
		}

		/**
		 * Removes a product from given wishlist
		 * @return \WP_REST_Response
		 */
		public static function remove_product( $request ) {

			$wishlist_id = $request['id'] ? (int) $request['id'] : 0;
			$product_id = $request['product_id'] ? (int) $request['product_id'] : 0;

			if( ! $product_id || ! $wishlist_id ){
				return (new self)->err_404();
			}

			$args = [
				'remove_from_wishlist' => $product_id,
				'user_id' => get_current_user_id(),
				'wishlist_id' => $wishlist_id,
			];

			try {
				YITH_WCWL()->remove( $args );
			} catch ( \YITH_WCWL_Exception $e ) {
				return new \WP_REST_Response(array('status' => 422, 'error' => $e->getMessage() ), 422);
			} catch ( \Exception $e ) {
				return new \WP_REST_Response(array('status' => 500, 'error' => $e->getMessage() ), 500);
			}

			// successful! return updated wishlist
			$wl = new \YITH_WCWL_Wishlist( $wishlist_id );
			return new \WP_REST_Response( $wl->get_data() );
		}

		/**
		 * Checks if user is logged in
		 * Used in rest api permission check
		 * @return true|/WP_Error
		 */
		public static function check_auth( $request ){

			if( is_user_logged_in() ) {
				return true;
			}

			return new \WP_Error('unauthorized', 'Authentication Required', [
				'code' => 401,
				'message' => 'Authentication Required',
				'data' => [],
			]);
		}

		/**
		 * Checks users read permssion for given wishlist
		 * Used in rest api permission check
		 */
		public static function check_read_cap( $request ){

			$res = self::check_auth( $request );
			if( is_wp_error( $res ) ){
				return $res;
			}

			$id = isset($request['id']) ? (int) $request['id'] : 0;

			$self = new self;
			if( ! $id ) {
				return $self->err_404();
			}

			$wl = new \YITH_WCWL_Wishlist( $id );
			if( ! $wl->current_user_can( 'view' ) ) {
				return $self->err_read_permission();
			}

			return true;
		}

		/**
		 * Checks users read permssion for given wishlist
		 * Used in rest api permission check
		 */
		public static function check_write_cap( $request ){
			$id = isset($request['id']) ? (int) $request['id'] : 0;

			$self = new self;
			if( ! $id ) {
				return $self->err_404();
			}

			$wl = new \YITH_WCWL_Wishlist( $id );
			if( ! $wl->current_user_can( 'write' ) ) {
				return $self->err_write_permission();
			}

			return true;
		}

		public function err_404(){
			return new \WP_REST_Response( [ 'status' => 404, 'error' => 'Wishlist not found!' ], 404);
		}

		protected function err_read_permission() {
			return new \WP_REST_Response( [ 'status' => 403, 'error' => 'You do not have permission to read.' ], 403);
		}

		protected function err_write_permission() {
			return new \WP_REST_Response( [ 'status' => 403, 'error' => 'You do not have permission to write.' ], 403);
		}

		protected function err_delete_permission() {
			return new \WP_REST_Response( [ 'status' => 403, 'error' => 'You do not have permission to delete.' ], 403);
		}
	}
}
