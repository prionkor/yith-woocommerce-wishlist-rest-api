### Plugin is still under development but the endpoint that is created is tested and working

YITH Woocommerce wishlist REST API plugin exposes REST point to works with YITH Woocommerce wishlist data. This is very helpful for mobile & web app developers who needs json response for their applications.

**Namespace:** `/yith/wishlist/v1`

**Full URL:** `/wp-json/yith/wishlist/v1`

## Endpoints

#### Get wishlists

**`GET /wishlists`**: Get list of wishlist for current user

#### Get single wishlist

**`GET /wishlists/{id}`**: Get a single wishlist by given id

#### Add product to a wishlist

**`POST /wishlists/{wishlist_id}/product/{product_id}`**: Adds a product id to a wishlist. No post payload is required. if wishlist id is given 0 (`/wishlists/0/product/{product_id}`) a new wishlist will be created.

#### Remove product from a wishlist

**`DELETE /wishlists/{wishlist_id}/product/{product_id}`**: Removes a product id from a wishlist. 
