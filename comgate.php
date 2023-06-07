<?php
/*
 * Plugin Name: Comgate WooCommerce Custom Payment Gateway
 * Plugin URI:
 * Description: WP integration with WP Gateway
 * Author: Veaceslav Mindru
 * Author URI:
 * Version: 0.0.1
 */

/* https://woocommerce.com/document/woocommerce-payment-gateway-plugin-base/ */
/* https://rudrastyh.com/woocommerce/payment-gateway-plugin.html */
/* https://woocommerce.com/document/payment-gateway-api/ */

/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter( 'woocommerce_payment_gateways', 'comgate_gateway_class' );
function comgate_gateway_class( $gateways ) {
        $gateways[] = 'Comgate_Gateway'; // your class name is here
        return $gateways;
}


add_action( 'plugins_loaded', 'comgate_init_gateway_class' );
function comgate_init_gateway_class() {

    class Comgate_Gateway extends WC_Payment_Gateway {

          /**
           *      * Class constructor, more about it in Step 3
           *           */
          public function __construct() {
                                                $this->id = 'comgate'; // payment gateway plugin ID
                                                $this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
                                                $this->has_fields = true; // in case you need a custom credit card form
                                                $this->method_title = 'Comgate Pay';
                                                $this->method_description = 'Comgate Payment Plugin'; // will be displayed on the options page

                                                // gateways can support subscriptions, refunds, saved payment methods,
                                                // but in this tutorial we begin with simple payments
                                                $this->supports = array(
                                                        'products'
                                                );

                                                // Method with all the options fields
                                                $this->init_form_fields();
                                                // Load the settings.
                                                $this->init_settings();
                                                $this->title = $this->get_option( 'title' );
                                                $this->description = $this->get_option( 'description' );
                                                $this->enabled = $this->get_option( 'enabled' );
                                                $this->testmode = 'yes' === $this->get_option( 'testmode' );
                                                $this->private_key = $this->testmode ? $this->get_option( 'test_private_key' ) : $this->get_option( 'private_key' );
            $this->merchant = $this->testmode ? $this->get_option( 'test_merchant' ) : $this->get_option( 'merchant' );
            $this->paymentsUrl = $this->testmode ? $this->get_option( 'test_paymentsUrl' ) : $this->get_option( 'paymentsUrl' );


                                                // Register Comgate Complete
//                                              add_action( 'woocommerce_api_' . $this->id , array( $this, 'comgate_callback' ) );
            add_action( 'woocommerce_api_comgate_callback', array( $this, 'comgate_callback' ) );
          }

                                  function comgate_callback() {
                                            header( 'HTTP/1.1 200 OK' );
              echo "processing payment<br>" ;
              $order_id = isset($_REQUEST['refId']) ? $_REQUEST['refId'] : null;
              $comgate_id = isset($_REQUEST['id']) ? $_REQUEST['id'] : null;
              if (is_null($order_id)) return;
              if (is_null($comgate_id)) return;
              $order = wc_get_order($order_id) ;
              if(empty($order)){
                die() ;
              }
              else
              {
                $trnsct_id = $order->get_transaction_id();
              }
              if ($trnsct_id == $comgate_id ){
              $order->payment_complete();
              wc_reduce_stock_levels($order_id);
              echo "order_id: $order_id<br>" ;
              echo "comgate_id: $comgate_id <br>" ;
              echo "trnsct_id: $trnsct_id <br>" ;
              echo "payment processed";
                                                        $items = $order->get_items();
                                                        foreach ( $items as $item ) {
                                                            $product_name = $item['name'];
                                                            $product_id = $item['product_id'];
                                                                        $product = get_product($product_id);
                                                                  $stock = $product->get_stock_quantity() ;
                                                        }
              error_log("call with order_id: $order_id and comgate_id: $comgate_id") ;
              error_log("product: $product_id, product_name: $product_name, stock_left: $stock") ;
              }
              wp_redirect("https://tshop.motokarymodrice.cz/checkout/order-received/") ;
              die();
                                        }

              /**
                *      * Plugin options, we deal with it in Step 3 too
                *            */
                                        public function init_form_fields(){

                                                $this->form_fields = array(
                                                        'enabled' => array(
                                                                'title'       => 'Enable/Disable',
                                                                'label'       => 'Enable Comgate Gateway',
                                                                'type'        => 'checkbox',
                                                                'description' => '',
                                                                'default'     => 'no'
                                                        ),
                                                        'title' => array(
                                                                'title'       => 'Title',
                                                                'type'        => 'text',
                                                                'description' => 'This controls the title which the user sees during checkout.',
                                                                'default'     => 'Credit Card',
                                                                'desc_tip'    => true,
                                                        ),
                                                        'description' => array(
                                                                'title'       => 'Description',
                                                                'type'        => 'textarea',
                                                                'description' => 'This controls the description which the user sees during checkout.',
                                                                'default'     => 'Platba Comgate',
                                                        ),
                                                        'testmode' => array(
                                                                'title'       => 'Test mode',
                                                                'label'       => 'Enable Test Mode',
                                                                'type'        => 'checkbox',
                                                                'description' => 'Place the payment gateway in test mode using test API keys.',
                                                                'default'     => 'yes',
                                                                'desc_tip'    => true,
                                                        ),
                                                        'test_private_key' => array(
                                                                'title'       => 'Test Comgate Private Key',
                                                                'type'        => 'password',
                                                        ),
                                                        'test_paymentsUrl' => array(
                                                                'title'       => 'Test Comgate Payments Url',
                                                                'type'        => 'text',
                'default'     => 'https://payments.comgate.cz/v1.0/create'
                                                        ),
                                                        'test_merchant' => array(
                                                                'title'       => 'Test Comgate merchant ID',
                                                                'type'        => 'text',
                                                        ),
                                                        'private_key' => array(
                                                                'title'       => 'Comgate Production Private Key',
                                                                'type'        => 'password'
              ),
              'paymentsUrl' => array(
                'title'       => 'Comgate Production Payments Url',
                'type'        => 'text',
                'default'     => 'https://payments.comgate.cz/v1.0/create'
                                                        ),
              'merchant' => array(
                'title'       => 'Production Comgate merchant ID',
                'type'        => 'text',
                                                        )
                                                );
                                        }

              public function validate_fields() {
              }

              public function create_comgate_payment( $order ) {
                                                        require_once dirname(__FILE__).'/lib/ComgatePaymentsSimpleProtocol.php';
                                                        // initialize payments protocol object
                                                        $paymentsProtocol = new ComgatePaymentsSimpleProtocol(
                                                            $this->paymentsUrl,
                                                            $this->merchant,
                                                            $this->testmode,
                                                            $this->private_key
                                                        );
                                                                        try {

                                                                            // create new payment transaction
                                                                            $paymentsProtocol->createTransaction(
                                                                                country: 'CZ',               // country
                                                                                price: $order->get_subtotal(),   // price
                                                                                currency: $order->get_currency(),   // currency
                                                                                label: 'Payment test',     // label
                                                                                refId: $order->get_id(),         // refId aka order_id
                                                                                                        email: $order->get_billing_email(),                             // Customer Email
                                                                                payerId: NULL,               // payerId
                                                                                preauth: false  // preauth
                                                                            );
                                                                            $transId = $paymentsProtocol->getTransactionId();
                                                                            // redirect to comgate payments system
                                                                            $RedirectUrl = $paymentsProtocol->getRedirectUrl();

                                                                        }
                                                                        catch (Exception $e) {
                                                                            header('Content-Type: text/plain; charset=UTF-8');
                                                                            echo "ERROR\n\n";
                                                                            echo $e->getMessage();
                                                                        }
                        $order->set_transaction_id($transId) ;
                        $order->save();
              return $RedirectUrl;
              }

              public function comgate_complete() {
                      //$order = wc_get_order( $_GET['id'] );
                      //$order->payment_complete();
                      //$order->reduce_order_stock();
                      update_option('webhook_debug', $_GET);
              }

              public function process_payment( $order_id ) {

                                                                global $woocommerce;

                                                                // we need it to get any order detailes
                                                                $order = wc_get_order($order_id );

               $transaction_id = $order->get_transaction_id();
               if ( strlen($transaction_id) > 0 ) {
                 $trans_status = "order exists" ;
                 $transaction_id = $order->get_transaction_id();
                 $comgate_url= add_query_arg('id',$transaction_id, "https://payments.comgate.cz/client/instructions/index") ;
                 $order->update_status('on-hold', __( 'Awaiting cheque payment', 'woocommerce' ));
               }
               else
               {
                 $trans_status = "creating order" ;
                 $comgate_url=$this->create_comgate_payment($order);
                 $transaction_id = $order->get_transaction_id();
                 $trans_status = "creating order" ;
                 $comgate_url= add_query_arg('id',$transaction_id, "https://payments.comgate.cz/client/instructions/index") ;
                 $order->update_status('on-hold', __( 'Awaiting cheque payment', 'woocommerce' ));
               }
               return array(
                   'result' => 'success',
//                   'redirect' => $this->get_return_url( $order )
                   'redirect' => $comgate_url
               );
  }
}
}
