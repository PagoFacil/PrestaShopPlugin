<?php

/**
 * Validate data from PagoFacil Form
 * @package Modules\Controllers
 * @version  2.0 Version 2.0
 * @author PagoFacil <soporte@pagofacil.net>
 */
class PagofacilValidationsModuleFrontController extends ModuleFrontController
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
        if (!in_array(Tools::getValue('type'), ['spei'])) {
            $this->module->redirectToStep(4);
        }
        $this->typeProcess = strtolower(Tools::getValue('type'));
        $endpoint = $this->typeProcess == 'spei' ? 'https://api.pagofacil.tech/Stp/cuentaclave/crear' : '';
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
            'spei' => [
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
    private function getDataSpei()
    {
        return [
            'idSucursal' => $this->module->getValueConfig('PF_API_BRANCH', false),
            'idUsuario' => $this->module->getValueConfig('PF_API_USER', false),
            'id_pedido' => $this->cartCustomer->id,
            'concepto' => $this->cartCustomer->id,
            'product' => $this->module->getValueConfig('PF_CONCEPTO', false),
            'monto' => (float) $this->cartCustomer->getOrderTotal(true, Cart::BOTH),
            'customer' => $this->module->getValueConfig('nombre') . ' ' . $this->module->getValueConfig('apellidos'),
            'email' => $this->module->getValueConfig('email'),
            'stp_c_origin_id' => 4,
            'webhook' => $this->context->link->getModuleLink($this->module->name, 'webhookspei'),
        ];
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
        //$msgErrorsInputValidation = $this->getMessageErrorsValidation($this->typeProcess);
        //$msgErrorsConfigValidation = $this->getMessageErrorsValidation('config');
        //$this->validateData($msgErrorsConfigValidation);
        $data = $this->getData($this->typeProcess);
        $url =  $this->module->getEndpoint();

        if ($this->typeProcess == 'spei') {
            $method = 'POST';
            $body = $data;
        } 

        // Response
        $response = $this->module->executeCurl($url, $method, $body);
        //$this->validateData($response, false);
        $response = $this->module->decode($response);

        //Webhook
        $endPointhook = Configuration::get( 'PF_ENVIRONMENT' ) ? 'https://api.pagofacil.tech' : 'https://sandbox.pagofacil.tech';
        $url = $endPointhook . '/Stp/webhookspei/crear';
        $this->executehookCurl($url, 'POST', $data);

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

    private function processResponseSpei($response)
    {
        // Validate Response
        if ($response == null
            || !array_key_exists('errors', $response)
            || $response['errors'] != ''
        ) {
            // Show Errors
            $this->getErrorUnknown($response['errors']);
            return $this->createErrorTemplate();
        }
        // Confirm Order
        $this->validateConfirmationOrder(10);
        // Redirect To Confirmation Page
        //$response = $response['charge'];
        $urlRedirection = $this->getRedirectConfirmationPage([
            'monto' => $response['monto'],
            'cuenta_clabe' => $response['cuenta_clabe'],
        ]);
        Tools::redirect($urlRedirection);
    }


    /**
     * Get Text to Unknown Error
     * @return SmartyVars Errors
     */
    private function getErrorUnknown($response)
    {
        $this->context->smarty->assign([
            'params' => [
                'error' => 'Ocurrió un error al procesar su pago, intente más tarde.',
                'errors' => $response,
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
        return $this->setTemplate('module:pagofacil/views/templates/front/payment_errors.tpl');
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


    private function setEnvironment( $arrayParams )
    {
        if( Configuration::get( 'PF_ENVIRONMENT' ) ){
            $arrayParams['data'][ 'idSucursal' ] = Configuration::get( 'PF_API_BRANCH' );
            $arrayParams['data'][ 'idUsuario' ] = Configuration::get( 'PF_API_USER' );
            $arrayParams['data'][ 'environment' ] = intval( Configuration::get( 'PF_ENVIRONMENT' ) );

            return $arrayParams;
        }

        $arrayParams['data'][ 'idSucursal' ] = Configuration::get( 'PF_API_BRANCH_SANDBOX' );
        $arrayParams['data'][ 'idUsuario' ] = Configuration::get( 'PF_API_USER_SANDBOX' );
        $arrayParams['data'][ 'environment' ] = intval( Configuration::get( 'PF_ENVIRONMENT' ) );

        return $arrayParams;
    }

    public function executehookCurl($url, $method, $body )
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ( $method == 'POST' ) {
            curl_setopt($ch, CURLOPT_POST, count($body));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $this->lastCurlInfo = curl_getinfo($ch);
        curl_close($ch);

        return $response;
    }
}
