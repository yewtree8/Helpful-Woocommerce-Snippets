<?php

/**
 * Welcome to the disvow coupons for specific items easy hook for woocommerce
 *
 *
 */

/*
 * Let's use the product data hook for woocommerce under the "product data" tab to add some extra stuff.
 *  this will purely be FRONT END and will echo just under the "general" settings in a specific product tag.
 */
add_action( 'woocommerce_product_options_general_product_data', 'add_coupon_tickbox_field' );
function add_coupon_tickbox_field()
{
    global $post;
    echo '<div class="product_coupon_field">';

    // Custom Product Checkbox Field, using woocommerce's built in checkbox creator
    woocommerce_wp_checkbox( array(
        'id'        => '_disabled_for_coupons', //Remember this for later if it's enabled
        'label'     => __('Disabled for coupons', 'woocommerce'), //Just a tag line next to the checkbox
        'description' => __('Disable this products from coupon discounts (Black Friday Baby)', 'woocommerce'), //What they will see when they hover over the (i) box next to the checkbox
        'desc_tip'  => 'true', //So they can see the actual tooltip, remove these two lines if you don't want them
    ) );

    echo '</div>';
}

/**
 * Now we're going to the back end whilst the product meta is being processed, going to make sure that when it processes the meta it will actually
 * identify this product as "no coupons allowed", this uses the process product meta hook.
 */
add_action( 'woocommerce_process_product_meta', 'save_coupon_field_meta', 10, 1 );
function save_coupon_field_meta( $post_id ){

    $current_disabled = isset( $_POST['_disabled_for_coupons'] ) ? 'yes' : 'no'; //Remember that _disabled_for_coupons id? If it's been ticked, it returns yes, if not, no.

    $disabled_coupon_products = get_option( '_products_disabled_for_coupons'); //Getting the list of "disabled for coupon" items
    if( empty($disabled_coupon_products) ) { //Make sure we check ;)
        if( $current_disabled == 'yes' ) {
            $disabled_coupon_products = array($post_id); //Just go ahead anyways
        }
    } else {
        if( $current_disabled == 'yes' ) {
            $disabled_coupon_products[] = $post_id;
            $disabled_coupon_products = array_unique( $disabled_coupon_products );
        } else {
            if ( ( $key = array_search( $post_id, $disabled_coupon_products ) ) !== false ) //Find any unwanted "nos" from the list
                unset( $disabled_coupon_products[$key]); //Remove them from the iteration
        }
    }

    update_post_meta( $post_id, '_disabled_for_coupons', $current_disabled ); //update woo to let them know
    update_option( '_products_disabled_for_coupons', $disabled_coupon_products ); //Make sure that the option is known once set.
}

/**
 * Here we handle a case if a coupon is actually valid with out custom hook (for example exluded products)
 */
add_filter('woocommerce_coupon_is_valid_for_product', 'set_and_check_validity_of_excluded_products', 12, 4);
function set_and_check_validity_of_excluded_products($valid, $product, $coupon, $values ){
    if(!count(get_option( '_products_disabled_for_coupons' )) > 0 ) return $valid; //Remember that option id we set? if it's greater than 1 it's valid so we arry on

    /**
     * Now we go over and check if they're actually valid for coupons in the first place
     * then set the global $Valid variable for the item to false, just in case.
     */
    $disabled_products = get_option( '_products_disabled_for_coupons' );
    if( in_array( $product->get_id(), $disabled_products ) )
        $valid = false; //If they're not a product that you've configured that can use coupons, they're removed anyways!

    return $valid; //If it gets to this point it's essentially true.
}

/**
 * This is the part that basically does what you want it to do in the first place
 * removes the coupon value (of that item only), from the basket, so no other items that can have coupons on are affected.
 */
add_filter( 'woocommerce_coupon_get_discount_amount', 'remove_discount_from_excluded_products', 12, 5 );
function remove_discount_from_excluded_products($discount, $discounting_amount, $cart_item, $single, $coupon ){
    if( !count(get_option( '_products_disabled_for_coupons' )) > 0 ) return $discount; //No point if there's nothing there with the tick box option set.
    $disabled_coupon_products = get_option( '_products_disabled_for_coupons' ); //Get the list of items that have had their prices discounted due to a coupon
    if( in_array( $cart_item['product_id'], $disabled_coupon_products ) )
        $discount = 0; //If they're in the list, set the discount for that item to 0
    return $discount;
}

/**
 * And there you have it!
 */
?>