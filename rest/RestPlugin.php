<?php

namespace YITH\Wishlist;

if( ! class_exists( 'YITH\WC\Wishlist\RestPlugin' ) ) {
	class RestPlugin{
		public static function init(){
			add_action( 'rest_api_init', [ '\YITH\Wishlist\Rest', 'init' ] );
		}
	}
}
