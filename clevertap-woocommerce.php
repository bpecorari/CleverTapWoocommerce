<?php
/*
Plugin Name: CleverTap WooCommerce Integration
Description: Integração do CleverTap com WooCommerce.
Version: 1.0
Author: Bruno Loss Pecorari
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Verifique se o WooCommerce está ativo
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

    // Inicializa a integração com CleverTap após o WooCommerce carregar
    add_action('woocommerce_loaded', 'clevertap_woocommerce_init');

    function clevertap_woocommerce_init()
    {
        // Enfileira o script CleverTap
        add_action('wp_enqueue_scripts', 'clevertap_enqueue_scripts');
        function clevertap_enqueue_scripts()
        {
            wp_enqueue_script('clevertap', 'https://static.clevertap.com/js/clevertap.min.js', array(), null, true);
            wp_add_inline_script('clevertap', '
                var clevertap = {event:[], profile:[], account:[], onUserLogin:[], notifications:[], privacy:[]};
                clevertap.account.push({ "id": "XXXXXX" });
                clevertap.privacy.push({optOut: false}); // set the flag to true, if the user of the device opts out of sharing their data
                clevertap.privacy.push({useIP: false}); // set the flag to true, if the user agrees to share their IP data

                (function () {
                    var wzrk = document.createElement("script");
                    wzrk.type = "text/javascript";
                    wzrk.async = true;
                    wzrk.src = ("https:" == document.location.protocol ? "https://d2r1yp2w7bby2u.cloudfront.net" : "http://static.clevertap.com") + "/js/clevertap.min.js";
                    var s = document.getElementsByTagName("script")[0];
                    s.parentNode.insertBefore(wzrk, s);

                    wzrk.onload = function(){
                        clevertap.init("8RZ-R78-KZ7Z");
                        console.log("CleverTap script loaded and initialized.");
                    };
                })();
            ');
        }

        // Envia dados do usuário logado para CleverTap
        add_action('wp_footer', 'clevertap_send_user_data');
        function clevertap_send_user_data()
        {
            if (is_user_logged_in()) {
                $current_user = wp_get_current_user();
                $user_id = $current_user->ID;
                $user_email = $current_user->user_email;
                $user_name = $current_user->user_login;

?>
                <script type="text/javascript">
                    document.addEventListener("DOMContentLoaded", function() {
                        if (typeof clevertap !== 'undefined') {
                            clevertap.onUserLogin.push({
                                "Site": {
                                    "Name": "<?php echo $user_name; ?>",
                                    "Email": "<?php echo $user_email; ?>",
                                    "User ID": "<?php echo $user_id; ?>"
                                }
                            });

                            console.log("CleverTap User Data Sent");
                            console.log("User Data:", {
                                "Name": "<?php echo $user_name; ?>",
                                "Email": "<?php echo $user_email; ?>",
                                "User ID": "<?php echo $user_id; ?>"
                            });
                        } else {
                            console.error("CleverTap is not defined.");
                        }
                    });
                </script>
                <?php
            }
        }

        // Captura dados do produto na página do produto e envia evento para CleverTap
        add_action('wp_footer', 'clevertap_capture_product_data');
        function clevertap_capture_product_data()
        {
            if (is_product()) {
                $product = wc_get_product(get_the_ID());

                if ($product) {
                    $product_id = $product->get_id();
                    $product_name = $product->get_name();
                    $product_price = $product->get_price();
                    $product_sku = $product->get_sku();

                ?>
                    <script type="text/javascript">
                        document.addEventListener("DOMContentLoaded", function() {
                            if (typeof clevertap !== 'undefined') {
                                clevertap.event.push("Product Viewed", {
                                    "Product ID": "<?php echo $product_id; ?>",
                                    "Product Name": "<?php echo $product_name; ?>",
                                    "Product Price": "<?php echo $product_price; ?>",
                                    "Product SKU": "<?php echo $product_sku; ?>"
                                });

                                console.log("CleverTap Event Sent: Product Viewed");
                                console.log("Product ID:", "<?php echo $product_id; ?>");
                                console.log("Product Name:", "<?php echo $product_name; ?>");
                                console.log("Product Price:", "<?php echo $product_price; ?>");
                                console.log("Product SKU:", "<?php echo $product_sku; ?>");
                            } else {
                                console.error("CleverTap is not defined.");
                            }
                        });
                    </script>
                <?php
                }
            }
        }

        // Captura dados do item adicionado ao carrinho e envia evento para CleverTap
        add_action('woocommerce_add_to_cart', 'clevertap_capture_add_to_cart', 10, 6);
        function clevertap_capture_add_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data)
        {
            $product = wc_get_product($product_id);

            if ($product) {
                $product_name = $product->get_name();
                $product_price = $product->get_price();
                $product_sku = $product->get_sku();

                ?>
                <script type="text/javascript">
                    document.addEventListener("DOMContentLoaded", function() {
                        if (typeof clevertap !== 'undefined') {
                            clevertap.event.push("Product Added to Cart", {
                                "Product ID": "<?php echo $product_id; ?>",
                                "Product Name": "<?php echo $product_name; ?>",
                                "Product Price": "<?php echo $product_price; ?>",
                                "Product SKU": "<?php echo $product_sku; ?>",
                                "Quantity": "<?php echo $quantity; ?>"
                            });

                            console.log("CleverTap Event Sent: Product Added to Cart");
                            console.log("Product ID:", "<?php echo $product_id; ?>");
                            console.log("Product Name:", "<?php echo $product_name; ?>");
                            console.log("Product Price:", "<?php echo $product_price; ?>");
                            console.log("Product SKU:", "<?php echo $product_sku; ?>");
                            console.log("Quantity:", "<?php echo $quantity; ?>");
                        } else {
                            console.error("CleverTap is not defined.");
                        }
                    });
                </script>
                <?php
            }
        }

        // Captura termos de pesquisa e envia evento para CleverTap
        add_action('wp_footer', 'clevertap_capture_search_query');
        function clevertap_capture_search_query()
        {
            if (is_search()) {
                $search_query = get_search_query();

                if (!empty($search_query)) {
                ?>
                    <script type="text/javascript">
                        document.addEventListener("DOMContentLoaded", function() {
                            if (typeof clevertap !== 'undefined') {
                                clevertap.event.push("Search Performed", {
                                    "Search Query": "<?php echo $search_query; ?>"
                                });

                                console.log("CleverTap Event Sent: Search Performed");
                                console.log("Search Query:", "<?php echo $search_query; ?>");
                            } else {
                                console.error("CleverTap is not defined.");
                            }
                        });
                    </script>
                <?php
                }
            }
        }

        // Captura dados do pedido concluído e envia evento para CleverTap
        add_action('woocommerce_order_status_completed', 'clevertap_capture_order_data');
        function clevertap_capture_order_data($order_id)
        {
            $order = wc_get_order($order_id);
            if ($order) {
                $items = $order->get_items();
                $items_data = array();

                foreach ($items as $item_id => $item) {
                    $product = $item->get_product();
                    $items_data[] = array(
                        "Product ID" => $product->get_id(),
                        "Product Name" => $product->get_name(),
                        "Product Price" => $product->get_price(),
                        "Quantity" => $item->get_quantity()
                    );
                }

                $order_total = $order->get_total();
                $payment_method = $order->get_payment_method_title();

                ?>
                <script type="text/javascript">
                    document.addEventListener("DOMContentLoaded", function() {
                        if (typeof clevertap !== 'undefined') {
                            clevertap.event.push("Charged", {
                                "Amount": "<?php echo $order_total; ?>",
                                "Payment mode": "<?php echo $payment_method; ?>",
                                "Charged ID": "<?php echo $order_id; ?>",
                                "Items": <?php echo json_encode($items_data); ?>
                            });

                            console.log("CleverTap Event Sent: Charged");
                            console.log("Amount:", "<?php echo $order_total; ?>");
                            console.log("Payment mode:", "<?php echo $payment_method; ?>");
                            console.log("Charged ID:", "<?php echo $order_id; ?>");
                            console.log("Items:", <?php echo json_encode($items_data); ?>);
                        } else {
                            console.error("CleverTap is not defined.");
                        }
                    });
                </script>
<?php
            }
        }
    }
}
?>