<?php

/**
 * Validate data from PagoFacil Form
 * @package Modules\Controllers
 * @version  2.0 Version 2.0
 * @author PagoFacil <soporte@pagofacil.net>
 */
class PagofacilValidationModuleFrontController extends ModuleFrontController
{
    /**
     * Customer Cart
     * @var null
     */
    private $cartCustomer = null;

    /**
     * Customer
     * @var null
     */
    private $customer = null;

    /**
     * Path to Checkout
     * @var null
     */
    private $pathToCheckout = null;

    /**
     * Errors Validation Message
     * @var null
     */
    private $messageErrorsValidation = null;

    /**
     * URL's to Process Payment
     * @var array
     */
    private $urls = [];

    /**
     * Endpoint to Process Payment
     * @var null
     */
    private $endpoint = null;

    /**
     * Construct
     */
    public function __construct()
    {
        parent::__construct();
        $this->pathToCheckout = 'index.php?controller=order';
        $this->endpoint = 'Wsrtransaccion/index/format/json';
        $this->urls = [
            'http://corepf.local.com/',
            'https://www.pagofacil.net/ws/public/'
        ];
    }

    /**
     * Redirect to Checkout Step
     * @param  integer $step Step
     * @return Redirect        Redirect to Step
     */
    private function redirectToStep($step = 1)
    {
        $step = $step >= 1 && $step <= 4 ? $step : 1;
        $path = $this->pathToCheckout . "&step=" . $step;
        Tools::redirect($path);
    }

    /**
     * Valdiate Process Order
     * @return mixed Redirect if is not valid
     */
    private function validateProcessOrder()
    {
        if ($this->cartCustomer->id_customer == 0
            || $this->cartCustomer->id_address_delivery == 0
            || $this->cartCustomer->id_address_invoice == 0
            || !$this->module->active
        ) {
            $this->redirectToStep();
        }
    }

    /**
     * Authorize to use Module
     * @return mixed Redirect if is not auhtorized
     */
    private function authorize()
    {
        // Check that this payment option is still available in case the
        // customer changed his address just before the end of the checkout process
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'pagofacil') {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            die($this->module->l(
                'Este método de pago no está disponible.',
                'validation'
            ));
        }
    }

    /**
     * Validate Object Loaded
     * @param  Object $object Object to Check
     * @return mixed         Redirect if is not valid
     */
    private function validateLoadedObject($object)
    {
        if (!Validate::isLoadedObject($object)) {
            $this->redirectToStep();
        }
    }

    /**
     * Get Messages Errors Validation
     * @param  string $type Type of Error
     * @return array       Messages Errors
     */
    private function getMessageErrorsValidation($type)
    {
        $errors = [
            'config' => [
                'PF_API_USER' => ['message' => 'El usuario de pago no está configurado, contacte al administrador'],
                'PF_API_BRANCH' => ['message' => 'Los datos de pago no están configurados, contacte al administrador'],
                'PF_ENVIRONMENT' => ['message' => 'El método de pago está en modo sandbox'],
                'PF_EXCHANGE' => ['message' => 'La moneda de pago no está configurado, contacte al administrador'],
                'PF_INSTALLMENTS' => ['message' => 'Meses sin intereses no está configurado']
            ],
            'input' => [
                'nombre' => ['message' => 'Debe capturar el nombre'],
                'apellidos' => ['message' => 'Debe capturar los apellidos'],
                'numeroTarjeta' => ['message' => 'Debe capturar el número de tarjeta'],
                'cvt' => ['message' => 'Debe capturar el cvt'],
                'cp' => ['message' => 'Debe capturar el cp'],
                'mesExpiracion' => ['message' => 'Debe seleccionar el mes de expiración'],
                'anioExpiracion' => ['message' => 'Debe seleccionar el año de expiración'],
                'email' => ['message' => 'Debe capturar el email'],
                'telefono' => ['message' => 'Debe capturar el teléfono'],
                'celular' => ['message' => 'Debe caputar el celular'],
                'calleyNumero' => ['message' => 'Debe capturar la calle y número'],
                'municipio' => ['message' => 'Debe capturar el municipio'],
                'estado' => ['message' => 'Debe capturar el estado'],
                'pais' => ['message' => 'Debe capturar el país']
            ]
        ];
        return $errors[$type];
    }

    /**
     * Validate Data
     * @param  array   $messages Messages
     * @param  boolean $config   Type Config
     * @return mixed Redirect if validation fail
     */
    private function validateData($messages = array(), $config = true)
    {
        $errors = [];
        foreach ($messages as $k => $m) {
            $value = $config ? Configuration::get($k) : Tools::getValue($k);
            if (trim($value) == '') {
                $errors[] = $m['message'];
            }
        }
        if (count($errors) > 0) {
            session_start();
            $_SESSION['errors'] = $errors;
            $this->redirectToStep(4);
        }
    }

    /**
     * Get Value to URL Encode
     * @param  mixed $value Value
     * @return mixed        Value Encoded
     */
    private function getUrlEncoded($value)
    {
        return urlencode($value);
    }

    /**
     * Get value from Params or Config
     * @param  mixed  $value  Value
     * @param  boolean $config Is Config Type
     * @return mixed          Value
     */
    private function getValue($value, $config = false)
    {
        $value = $config ? Configuration::get($value) : Tools::getValue($value);
        return trim($value);
    }

    /**
     * Get Value Encoded from Config or Params
     * @param  mixed  $value  Value
     * @param  boolean $config Type
     * @return mixed          Value Encoded
     */
    private function getValueEncoded($value, $config = false)
    {
        $value = $this->getValue($value, $config);
        return $this->getUrlEncoded($value);
    }

    /**
     * Get Data to Process Payment
     * @return array Data
     */
    private function getData()
    {
        $data = [
            'method' => 'transaccion',
            'data' => [
                'idServicio' => $this->getUrlEncoded('3'),
                'idSucursal' => $this->getValueEncoded('PF_API_BRANCH', true),
                'idUsuario' => $this->getValueEncoded('PF_API_USER', true),
                'nombre' => $this->getValue('nombre'),
                'apellidos' => $this->getValue('apellidos'),
                'numeroTarjeta' => $this->getValueEncoded('numeroTarjeta'),
                'cvt' => $this->getValueEncoded('cvt'),
                'cp' => $this->getValueEncoded('cp'),
                'mesExpiracion' => $this->getValueEncoded('mesExpiracion'),
                'anyoExpiracion' => $this->getValueEncoded('anioExpiracion'),
                'mesExpiracion' => $this->getValueEncoded('mesExpiracion'),
                'monto' => $this->getUrlEncoded(
                    (float) $this->cartCustomer->getOrderTotal(true, Cart::BOTH)
                ),
                'email' => $this->getValue('email'),
                'telefono' => $this->getValueEncoded('telefono'),
                'celular' => $this->getValueEncoded('celular'),
                'calleyNumero' => $this->getValue('calleyNumero'),
                'colonia' => $this->getValue('colonia') == '' ? 'S/D' : $this->getValueEncoded('colonia'),
                'municipio' => $this->getValue('municipio'),
                'estado' => $this->getValue('estado'),
                'pais' => $this->getValue('pais'),
                'idPedido' => $this->getUrlEncoded($this->cartCustomer->id),
                'ip' => $this->getUrlEncoded(Tools::getRemoteAddr()),
                'httpUserAgent' => $_SERVER['HTTP_USER_AGENT']
            ]
        ];
        
        if ($this->getValue('PF_NO_MAIL', true) == '1') {
            $data['data']['noMail'] = $this->getUrlEncoded(1);
        }
        if ($this->getValue('PF_EXCHANGE', true) != 'MXN') {
            $data['data']['divisa'] = $this->getValueEncoded('PF_EXCHANGE');
        }
        if ($this->getValue('PF_INSTALLMENTS', true)) {
            if ($this->getValue('msi') != '' && $this->getValue('msi') != '00') {
                $data['data']['plan'] = 'MSI';
                $data['data']['mensualidades'] = $this->getValueEncoded('msi');
            }
        }
        return $data;
    }

    /**
     * Execute Curl to process payment
     * @param  string $url URL
     * @return array      Response
     */
    private function executeCurl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    /**
     * Decode Response
     * @param  mixed $response Response
     * @return array           Response Decoded
     */
    private function decode($response)
    {
        return json_decode($response, true);
    }

    /**
     * Process Payment
     * @return mixed Response
     */
    public function postProcess()
    {
        $this->cartCustomer = $this->context->cart;
        $this->validateProcessOrder();
        $this->authorize();
        $this->customer = new Customer($this->cartCustomer->id_customer);
        $this->validateLoadedObject($this->customer);
        $msgErrorsInputValidation = $this->getMessageErrorsValidation('input');
        $this->validateData($msgErrorsInputValidation, false);
        $msgErrorsConfigValidation = $this->getMessageErrorsValidation('config');
        $this->validateData($msgErrorsConfigValidation);
        $data = $this->getData();
        $url = $this->urls[$this->getValue('PF_ENVIRONMENT', true)] . $this->endpoint;
        $url .= '?' . http_build_query($data);

        // Response
        $response = $this->executeCurl($url);
        $response = $this->decode($response);

        // Process Payment failure
        if ($response === null
            || !isset($response['WebServices_Transacciones']['transaccion'])
            || $response['WebServices_Transacciones']['transaccion']['autorizado'] != '1'
        ) {
            $authorized = $response['WebServices_Transacciones']['transaccion']['autorizado'];
            $this->context->smarty->assign([
                'params' => [
                    'error' => 'Ocurrió un error al procesar su pago, intente más tarde.',
                    'link' => $this->context->link->getPageLink('order') . '?step=4'
                ]
            ]);

            if ((bool) !$authorized) {
                $this->context->smarty->assign([
                    'errors' => $response['WebServices_Transacciones']['transaccion']['error']
                ]);
            }
            return $this->setTemplate('module:pagofacil/views/templates/front/payment_error.tpl');
        }

        // Validate Order if Payment was processed
        $this->module->validateOrder(
            (int)$this->cartCustomer->id,
            2,
            (float) $this->cartCustomer->getOrderTotal(true, Cart::BOTH),
            $this->module->displayName,
            null,
            [],
            (int)$this->context->currency->id,
            false,
            $this->customer->secure_key
        );
        // Redirect To Confirmation Page
        $response = $response['WebServices_Transacciones']['transaccion'];
        Tools::redirect(
            'index.php?controller=order-confirmation&id_cart='. (int) $this->cartCustomer->id .
            '&id_module=' . (int)$this->module->id .
            '&id_order=' . $this->module->currentOrder .
            '&key=' . $this->customer->secure_key .
            '&transaction=' . $response['transaccion'] .
            '&no_authorization=' . $response['autorizacion'] .
            '&description=' . $response['texto'] .
            '&message=' . $response['pf_message'] .
            '&status=' . $response['status']
        );
    }
}
