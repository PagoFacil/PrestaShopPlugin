<?php

if (!defined('_PS_VERSION_'))
{
    die('No direct script access');
}


class PagofacilWebhooktnpModuleFrontController extends ModuleFrontController
{

    const AUTORIZADO = 1;
    const RECHAZADO = 0;

    protected $_method;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Init
     * @return void
     */
    public function initContent()
    {
        parent::initContent();
        $this->_method = 'AES-128-CBC';
        $this->executeWebhook();
        exit();
    }

    /**
     * Process Tarjeta Presente Payment
     * @param  mixed $response Response
     * @return mixed           Redirection | Show Errros
     */
    protected function executeWebhook()
    {

        $response = Tools::getValue( 'response' );
        $responseDecrypt = $this->decryptResponse( $response );
        $authorized = isset( $responseDecrypt['autorizado'] ) ? $responseDecrypt['autorizado'] : false;

        $encryptData = isset( $responseDecrypt['params']['param2'] ) ? $responseDecrypt['params']['param2'] : $responseDecrypt['data']['param2'];
        $cartSession = $this->findCartSession( $encryptData );
        $order = Order::getByCartId( $cartSession['id'] );

        $customer = new Customer( (int) $cartSession['id_customer'] );
        $cart = new Cart( $cartSession['id'] );


        if ( !boolval( $authorized ) ) {
            $this->getErrorUnknown();

            $this->restoreCartPayment( $order->id, $cartSession['id_customer'] );

            $this->context->smarty->assign([
                'errors' => isset( $responseDecrypt['error'] ) ? $responseDecrypt['error'] : 'Generic error'
            ]);

            return $this->createErrorTemplate();
        }
        /**/

        $this->updateCustomerSession( $customer );

        $secureKey = $cartSession['secure_key'];


        $orderHistory = new OrderHistory();
        $orderHistory->id_order = (int) $order->id;
        $orderHistory->changeIdOrderState(2, (int) ($order->id));


        $urlRedirection = $this->getRedirectConfirmationPage($order->id_cart, $order->id, $secureKey, [
            'transaction' => $responseDecrypt['transaccion'],
            'no_authorization' => $responseDecrypt['autorizacion'],
            'description' => $responseDecrypt['texto'],
            'message' => $responseDecrypt['pf_message'],
            'status' => 'success',
        ]);


        $this->context->cookie->id_cart = $cartSession['id'];
        $this->context->cookie->write();



        Tools::redirect($urlRedirection);

    }

    private function updateCustomerSession( $customer )
    {
        $this->context->customer = $customer;
        $this->context->cookie->id_customer = (int) $customer->id;
        $this->context->cookie->customer_lastname = $customer->lastname;
        $this->context->cookie->customer_firstname = $customer->firstname;
        $this->context->cookie->passwd = $customer->passwd;
        $this->context->cookie->logged = 1;
        $customer->logged = 1;
        $this->context->cookie->email = $customer->email;
        $this->context->cookie->is_guest = $customer->isGuest();


        /*

        if (Configuration::get('PS_CART_FOLLOWING') && (empty($this->context->cookie->id_cart) || Cart::getNbProducts($this->context->cookie->id_cart) == 0) && $idCart = (int) Cart::lastNoneOrderedCart($this->customer->id)) {
            $this->cart = new Cart($idCart);
        } else {
            $idCarrier = (int) $this->cart->id_carrier;
            $this->cart->id_carrier = 0;
            $this->cart->setDeliveryOption(null);
            $this->cart->updateAddressId($this->cart->id_address_delivery, (int) Address::getFirstCustomerAddressId((int) ($customer->id)));
            $this->cart->id_address_delivery = (int) Address::getFirstCustomerAddressId((int) ($customer->id));
            $this->cart->id_address_invoice = (int) Address::getFirstCustomerAddressId((int) ($customer->id));
        }
        $this->cart->id_customer = (int) $customer->id;

        if (isset($idCarrier) && $idCarrier) {
            $deliveryOption = [$this->cart->id_address_delivery => $idCarrier . ','];
            $this->cart->setDeliveryOption($deliveryOption);
        }

        $this->cart->save();
        $this->context->cookie->id_cart = (int) $this->cart->id;
        $this->cart->autosetProductAddress();
        */

        $this->context->cookie->write();

        $this->context->cookie->registerSession(new CustomerSession());


    }

    /**
     * Set Success Response
     */
    private function setSuccess()
    {
        echo $this->module->encode([
            'message' => 'Los datos han sido actualizados',
            'statusCode' => 200
        ]);
        return;
    }

    /**
     * Set Error Response
     */
    private function setError()
    {
        echo $this->module->encode([
            'error' => 'Values Not Found',
            'message' => 'Los parametros "customer_order", "status", "amount" no son validos',
            'statusCode' => 400
        ]);
        return;
    }

    /**
     * Update Order Status
     * @param int $orderId Order ID
     * @param int $status  Order Status
     */
    private function setOrderStatus($orderId, $status)
    {
        $history = new OrderHistory();
        $history->id_order = $orderId;
        $history->id_order_state = $status;
        $history->changeIdOrderState($status, $orderId);
        $history->addWithemail();

        return $history;
    }

    /**
     * Generate Error Template
     * @return SmartyTemplate Errors
     */
    private function createErrorTemplate()
    {
        $this->setTemplate('module:pagofacil/views/templates/front/payment_error.tpl');
        return $this->display();
    }

    /**
     * Get Text to Unknown Error
     * @return SmartyVars Errors
     */
    private function getErrorUnknown()
    {
        $this->context->smarty->assign([
            'params' => [
                'error' => 'Ocurrió un error al procesar su pago, intente más tarde.',
                'link' => $this->context->link->getPageLink('order') . '?step=4'
            ]
        ]);
    }

    private function getRedirectConfirmationPage( $cartId, $orderId, $secureKey, $params )
    {
        $params = http_build_query($params);
        return 'index.php?controller=order-confirmation&id_cart='. (int) $cartId .
            '&id_module=' . (int) $this->module->id .
            '&id_order=' . $orderId .
            '&key=' . $secureKey .
            '&type=' . "tp" .
            '&' . $params;
    }

    private function findCartSession( $cartSession )
    {
        try {

            $base64Decode = base64_decode( $cartSession );
            $jsonToArray = json_decode( $base64Decode, true );
            return $jsonToArray['cart'];

        }catch ( Exception $exception ){
            $this->getErrorUnknown();
            $this->context->smarty->assign([
                'errors' => 'Generic error DB'
            ]);
            return $this->createErrorTemplate();
        }

    }

    private function restoreCartPayment( $orderId, $customerId )
    {
        $oldCart = new Cart(Order::getCartIdStatic( $orderId, $customerId ) );
        $duplication = $oldCart->duplicate();
        if (!$duplication || !Validate::isLoadedObject($duplication['cart'])) {
            $this->errors[] = Tools::displayError('Sorry. We cannot renew your order.');
        } elseif (!$duplication['success']) {
            $this->errors[] = Tools::displayError('Some items are no longer available, and we are unable to renew your order.');
        } else {
            $this->context->cookie->id_cart = $duplication['cart']->id;
            $context = $this->context;
            $context->cart = $duplication['cart'];
            CartRule::autoAddToCart($context);
            $this->context->cookie->write();
            if (Configuration::get('PS_ORDER_PROCESS_TYPE') == 1) {
                #Tools::redirect('index.php?controller=order-opc');
            }
            #Tools::redirect('index.php?controller=order');
        }
    }

    /**************************************************************/
    /*********************** Decrypt method ***********************/
    /**************************************************************/



    public static function desencriptar($encodedInitialData, $key)
    {
        $encodedInitialData =  base64_decode($encodedInitialData);
        $cypher = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
        if (mcrypt_generic_init($cypher, $key, $key) != -1)
        {
            $decrypted = mdecrypt_generic($cypher, $encodedInitialData);
            mcrypt_generic_deinit($cypher);
            mcrypt_module_close($cypher);
            return self::pkcs5_unpad($decrypted);
        }
        return "";
    }

    /**
     * Permite desencriptar la respuesta que se retorna en transacciones 3D Secure
     * (3DS), para PHP versión 7.2 o superior. Requiere el módulo OpenSSL instalado.
     *
     * @param type $encodedInitialData Cadena encriptada que regresa la API
     * @param type $key Llave de cifrado proporcionada por PagoFácil
     */
    function desencriptar_php72($encodedInitialData, $key) {
        $auth = false;
        $data = base64_decode($encodedInitialData, true);
        try {
            $iv_size = openssl_cipher_iv_length($this->getMethod());
            $iv = substr($data, 0, $iv_size);
            $data = substr($data, $iv_size);
            $decrypted = openssl_decrypt($data, $this->getMethod(), $key, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, $iv);

            $decrypted = preg_replace('/^(",")/', '"', self::pkcs5_unpad($decrypted));
            $decrypted = preg_replace('/^(htt)/', '"u":"htt', $decrypted);

            if(stripos($decrypted, 'Transaccion exitosa')) {
                $auth = true;
            }
            $decryptedArray = json_decode('{'.$decrypted);

            $decryptedArray->autorizado = $auth ? self::AUTORIZADO : self::RECHAZADO;

            return json_encode($decryptedArray);
        } catch (Exception $exc) {
            return '';
        }
    }

    /**
     * This class uses by default hex2bin function, but it is only available since 5.4, because the current php version
     * is 5.2 this function was created with the purpose to replace the default function.
     * @param $hex_string
     * @return string
     */
    private static function hexToBin($hex_string)
    {
        $pos = 0;
        $result = '';
        while ($pos < strlen($hex_string)) {
            if (strpos(" \t\n\r", $hex_string{$pos}) !== FALSE) {
                $pos++;
            } else {
                $code = hexdec(substr($hex_string, $pos, 2));
                $pos = $pos + 2;
                $result .= chr($code);
            }
        }
        return $result;
    }

    private static function pkcs5_unpad($text)
    {
        $pad = ord($text{strlen($text) - 1});
        if ($pad > strlen($text))
            return false;
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad)
            return false;
        return substr($text, 0, -1 * $pad);
    }

    public function getMethod() {
        return $this->_method;
    }

    private function decryptResponse( $responseEncrypt )
    {
        return json_decode( $this->desencriptar_php72( $responseEncrypt, Configuration::get('PF_CIPHER_KEY') ), true );
    }
}