<?php
/*
Plugin Name: Woocommerce Discount Quantity ABM
Author: Adiel Ben Moshe
Version: 1.0.0
*/

class Woocommerce_Discount_Quantity_ABM {

  function __construct() {
    // Set Discount
    add_action('woocommerce_before_calculate_totals', array($this, 'set_discount'));
 
    
    // Append Label on cart list
    add_filter('woocommerce_cart_item_name', array($this, 'append_custom_label_to_item_name'), 20, 3);

    // Append notice on product page
    add_action('woocommerce_before_add_to_cart_form',  array($this, 'append_notice_on_product_page'));
  }


  /**
   * Loop on products & Set Discount for products in cart.
   *
   * @param WC_Cart $cart WooCommerce cart object.
   */
  function set_discount($cart) {

    foreach ($cart->get_cart_contents() as $product_item) {

      // Check if is Enable Discount, else skip to next product.
      $is_discount = get_post_meta($product_item['product_id'], '_dq_meta_key');

      if (!$is_discount) {
        continue;
      }


      // Check quantity of products.
      if ($product_item['quantity'] < get_post_meta($product_item['product_id'], 'dq_discount_min', true)) {
        continue;
      }


      // Set Discount.
      $productPrice = $product_item['data']->get_price();

      $discountPrice = get_post_meta($product_item['product_id'], 'dq_discount_price', true);

      $product_item['data']->set_price($discountPrice);
    }
  }


  /**
   * Loop on products & Append Label to item name.
   */
  function append_custom_label_to_item_name($product_name, $product_item) {


    // Check if is Enable Discount.
    $is_discount = get_post_meta($product_item['product_id'], '_dq_meta_key');

    if (!$is_discount) {
      return $product_name;
    }

    // Check quantity of products.
    if ($product_item['quantity'] < get_post_meta($product_item['product_id'], 'dq_discount_min', true)) {
      return $product_name;
    }

    // Append Label
    $product_name .= ' <em> (מחיר סיטאונאי) </em>';
    return $product_name;
  }



  /**
   * Append notice on Product page.
   */
  function append_notice_on_product_page() {

    global $product;

    // Check if is Enable Discount.
    $is_discount = get_post_meta($product->get_id(), '_dq_meta_key');
    if (!$is_discount) {
      return;
    }

    $discount_min = get_post_meta($product->get_id(), 'dq_discount_min', true);
    $discount_price = get_post_meta($product->get_id(), 'dq_discount_price', true);

    // Append notice

    $notice = '<p 
        style="
        background: #e0ecf3;
        padding: 15px;
        border-radius: 5px;
        display: flex;
        color: #17324f;
        gap: 10px;
        
        ">';


    $notice .= '<i class="icon-box"></i>';

    $notice .= '<span>מחיר סיטאונאי החל מ-' . $discount_min . ' יחידות :</span>';

    $notice .= '<strong>' . $discount_price . ' ש"ח</strong>';

    $notice .= '</p>';

    echo $notice;
  }
}


new Woocommerce_Discount_Quantity_ABM;










abstract class Discount_Quantity_Meta_Box {


  /**
   * Set up and add the meta box.
   */
  public static function add() {
    add_meta_box('wporg_box_id', 'Discount Quantity', [self::class, 'html'], 'product');
  }


  /**
   * Save the meta box selections.
   *
   * @param int $post_id  The post ID.
   */
  public static function save(int $post_id) {
    if (isset($_POST['discount_quantity_field'])) {
      update_post_meta($post_id, "_dq_meta_key", true);
    } else {
      update_post_meta($post_id, "_dq_meta_key", false);
    }


    update_post_meta($post_id, "dq_discount_price", $_POST['dq_discount_price']);

    update_post_meta($post_id, "dq_discount_min", $_POST['dq_discount_min']);
  }


  /**
   * Display the meta box HTML to the user.
   *
   * @param \WP_Post $post   Post object.
   */
  public static function html($post) {
    $value = get_post_meta($post->ID, '_dq_meta_key', true);

?>

    <div class="woocommerce_options_panel" style="min-height: initial;">
      <p class="form-field">
        <label for="discount_quantity_field">Check if enable discount</label>
        <input type="checkbox" <?php checked($value, true); ?> id="discount_quantity_field" name="discount_quantity_field" value="discount">
      </p>

      <div id="fileds-hide" style="display: none;">
        <p class="form-field">
          <label for="dq_discount_price">Price Discount</label>
          <input type="number" id="dq_discount_price" name="dq_discount_price" value="<?php echo get_post_meta($post->ID, 'dq_discount_price', true); ?>">
        </p>

        <p class="form-field">
          <label for="dq_discount_min">Min Count for Discount</label>
          <input type="number" id="dq_discount_min" name="dq_discount_min" value="<?php echo get_post_meta($post->ID, 'dq_discount_min', true); ?>">
        </p>
      </div>

    </div>

    <script>
      window.addEventListener('load', checkboxClick);
      document.getElementById('discount_quantity_field').addEventListener('click', checkboxClick);

      function checkboxClick() {
        var checkBox = document.getElementById("discount_quantity_field");
        var elToogle = document.getElementById("fileds-hide");

        // If the checkbox is checked, display the output text
        if (checkBox.checked == true) {
          elToogle.style.display = "block";
        } else {
          elToogle.style.display = "none";
        }
      }
    </script>

<?php
  }
}

add_action('add_meta_boxes', ['Discount_Quantity_Meta_Box', 'add']);
add_action('save_post', ['Discount_Quantity_Meta_Box', 'save']);
