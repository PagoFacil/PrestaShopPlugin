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
     * Errors Validation Message
     * @var null
     */
    private $messageErrorsValidation = null;

    /**
     * Type of Process
     * @var null
     */
    private $typeProcess = null;

    /**
     * Construct
     */
    public function __construct()
    {
        parent::__construct();
        // Veriy Type
        if (!in_array(Tools::getValue('type'), ['cash', 'tp'])) {
            $this->module->redirectToStep(4);
        }
        $this->typeProcess = strtolower(Tools::getValue('type'));
        $endpoint = $this->typeProcess == 'cash' ? 
            'cash/charge' : 'Wsrtransaccion/index/format/json';
        $this->module->setEndpoint($endpoint);
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
            $this->module->redirectToStep();
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
            $this->module->redirectToStep();
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
            'tp' => [
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
            ],
            'cash' => [
                'nombre' => ['message' => 'Debe capturar el nombre'],
                'apellidos' => ['message' => 'Debe capturar los apellidos'],
                'email' => ['message' => 'Debe capturar el email'],
                'tienda' => ['message' => 'Debe seleccionar una tienda']
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
            $this->module->redirectToStep(4);
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
     * @param string $type Type of Data
     * @return array Data
     */
    private function getData($type)
    {
        $type = ucfirst(strtolower($type));
        $data = "getData$type";
        return $this->$data();
    }

    /**
     * Get Data to Cash Payment
     * @return array Data Cash
     */
    private function getDataCash()
    {
        return [
            'branch_key' => $this->module->getValueConfig('PF_API_BRANCH', false),
            'user_key' => $this->module->getValueConfig('PF_API_USER', false),
            'order_id' => $this->cartCustomer->id,
            'product' => $this->module->getValueConfig('PF_CONCEPTO', false),
            'amount' => (float) $this->cartCustomer->getOrderTotal(true, Cart::BOTH),
            'store_code' => $this->module->getValueConfig('tienda'),
            'customer' => $this->module->getValueConfig('nombre') . ' ' . 
                $this->module->getValueConfig('apellidos'),
            'email' => $this->module->getValueConfig('email')
        ];
    }

    /**
     * Get Data Tarjeta Presente
     * @return array Data
     */
    private function getDataTp()
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
        $msgErrorsInputValidation = $this->getMessageErrorsValidation($this->typeProcess);
        $this->validateData($msgErrorsInputValidation, false);
        $msgErrorsConfigValidation = $this->getMessageErrorsValidation('config');
        $this->validateData($msgErrorsConfigValidation);
        $data = $this->getData($this->typeProcess);
        $url = $this->module->getProcessUrl($this->getValue('PF_ENVIRONMENT', true)) . 
            $this->module->getEndpoint();

        if ($this->typeProcess == 'cash') {
            $method = 'POST';
            $body = $data;
        } else {
            $url .= '?' . http_build_query($data);
            $method = 'GET';
            $body = [];
        }

        // Response
        $response = $this->module->executeCurl($url, $method, $body);
        $response = $this->module->decode($response);

        // Process Response
        $type = ucfirst(strtolower($this->typeProcess));
        $process = "processResponse$type";
        $this->$process($response);
    }

    /**
     * Process Response Cash Payment
     * @param  mixed $response Response from CURL
     * @return mixed           Redirect | Errors
     */
    private function processResponseCash($response)
    {
        // Validate Response
        if ($response == null 
            || !array_key_exists('error', $response)
            || $response['error'] != 0
            || !array_key_exists('charge', $response)
            || !isset($response['charge'])
        ) {
            // Show Errors
            $this->getErrorUnknown();
            return $this->createErrorTemplate();
        }
        // Confirm Order
        $this->validateConfirmationOrder(10);
        // Redirect To Confirmation Page
        $response = $response['charge'];
        $urlRedirection = $this->getRedirectConfirmationPage([
            'reference' => $response['reference'],
            'customer_order' => $response['customer_order'],
            'amount' => $response['amount'],
            'convenience_store' => ucwords(strtolower(str_replace('_', ' ', $response['convenience_store']))),
            'store_fixed_rate' => $response['store_fixed_rate'],
            'store_schedule' => $response['store_schedule'],
            'store_image' => $response['store_image'],
            'bank_account_number' => $response['bank_account_number'],
            'bank' => $response['bank'],
            'expiration_date' => $response['expiration_date'],
            'expiration_payment' => date("j M, Y", strtotime($response['expiration_date']))
        ]);
        Tools::redirect($urlRedirection);
    }

    /**
     * Process Tarjeta Presente Payment
     * @param  mixed $response Response
     * @return mixed           Redirection | Show Errros
     */
    private function processResponseTp($response)
    {
        // Process Payment failure
        if ($response === null
            || !isset($response['WebServices_Transacciones']['transaccion'])
            || $response['WebServices_Transacciones']['transaccion']['autorizado'] != '1'
        ) {
            $authorized = $response['WebServices_Transacciones']['transaccion']['autorizado'];
            $this->getErrorUnknown();

            if ((bool) !$authorized) {
                $this->context->smarty->assign([
                    'errors' => $response['WebServices_Transacciones']['transaccion']['error']
                ]);
            }
            return $this->createErrorTemplate();
        }

        // Validate Order if Payment was processed
        $this->validateConfirmationOrder(2);
        // Redirect To Confirmation Page
        $response = $response['WebServices_Transacciones']['transaccion'];
        $urlRedirection = $this->getRedirectConfirmationPage([
            'transaction' => $response['transaccion'],
            'no_authorization' => $response['autorizacion'],
            'description' => $response['texto'],
            'message' => $response['pf_message'],
            'status' => $response['status']
        ]);
        Tools::redirect($urlRedirection);
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

    /**
     * Generate Error Template
     * @return SmartyTemplate Errors
     */
    private function createErrorTemplate()
    {
        return $this->setTemplate('module:pagofacil/views/templates/front/payment_error.tpl');
    }

    /**
     * Validate Confirmation Order
     * @param  int $state State of Order
     * @return boolean
     */
    private function validateConfirmationOrder($state)
    {
        $this->module->validateOrder(
            (int) $this->cartCustomer->id,
            $state,
            (float) $this->cartCustomer->getOrderTotal(true, Cart::BOTH),
            $this->module->displayName,
            null,
            [],
            (int) $this->context->currency->id,
            false,
            $this->customer->secure_key
        );
    }

    /**
     * Generate Redirect Confirmation Page
     * @param  array $params Params
     * @return string         Link To Redirect
     */
    private function getRedirectConfirmationPage($params)
    {
        $params = http_build_query($params);
        return 'index.php?controller=order-confirmation&id_cart='. (int) $this->cartCustomer->id .
            '&id_module=' . (int) $this->module->id .
            '&id_order=' . $this->module->currentOrder .
            '&key=' . $this->customer->secure_key .
            '&type=' . $this->typeProcess .
            '&' . $params;
    }
}
