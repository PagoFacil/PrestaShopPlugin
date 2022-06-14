<?php

/**
 * Módulo para procesar pagos mediante PagoFacil
 * Versiones de Prestashop 1.7.* soportadas
 *
 * @package Modules\Pagofacil
 * @author  PagoFacil <soporte@pagofacil.net>
 * @version 2.0 Verion Updated
 * @link    http://pagofacil.net
 * @since 1.7 Since Prestashop v1.7.*
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

class Pagofacil extends PaymentModule
{
    private $cartCustomer = null;
    private $client = null;
    private $urls = [];
    private $endpoint = null;
    private $pathToCheckout = null;
    private $lastCurlInfo = null;
    /**
     * Construct
     * Initialize Module
     */
    public function __construct()
    {
        // Module Data
        $this->name = 'pagofacil';
        $this->tab = 'payments_gateways';
        $this->version = '2.0';
        $this->author = 'PagoFácil';
        $this->need_instance = 1;
        $this->ps_versions_compliancy = [
            'min' => '1.7',
            'max' => _PS_VERSION_
        ];
        $this->controllers = ['validation', 'webhook'];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('PagoFácil');
        $this->description = $this->l(
            'Éste módulo te permite aceptar pagos con Visa, Mastercard y AMEX'
        );
        $this->confirmUninstall = $this->l(
            '¿Estás seguro que desea desinstalar el módulo?'
        );

        // Own Config
        $this->urls = [
            'https://stapi.pagofacil.net/',
            'https://www.pagofacil.net/ws/public/'
        ];
        $this->pathToCheckout = 'index.php?controller=order';
    }

    /**
     * Get Process Url
     * @param  string $index Index
     * @return string        URL
     */
    public function getProcessUrl($index)
    {
        return $this->urls[$index];
    }

    /**
     * Set Endpoint
     * @param string $endpoint Endpoint
     */
    public function setEndpoint($endpoint)
    {
        $this->endpoint = $endpoint;
    }

    /**
     * Get Endpoint
     * @return string Endpoint
     */
    public function getEndpoint()
    {
        return $this->endpoint;
    }

    /**
     * Get Path to Checkout
     * @return string Checkout
     */
    public function getPathToCheckout()
    {
        return $this->pathToCheckout;
    }

    /**
     * Install Module
     * @return bool Is installed
     */
    public function install()
    {
        if (!parent::install()
            || !$this->registerHook('paymentOptions')
            || !$this->registerHook('paymentReturn')
        ) {
            return false;
        }
        return true;
    }

    /**
     * Uninstall Module
     * @return bool Uninstalled Module
     */
    public function uninstall()
    {
        if (   !Configuration::deleteByName('PF_API_USER')
            || !Configuration::deleteByName('PF_API_BRANCH')
            || !Configuration::deleteByName('PF_API_USER_SANDBOX')
            || !Configuration::deleteByName('PF_API_BRANCH_SANDBOX')
            || !Configuration::deleteByName('PF_ENVIRONMENT')
            || !Configuration::deleteByName('PF_NO_MAIL')
            || !Configuration::deleteByName('PF_EXCHANGE')
            || !Configuration::deleteByName('PF_INSTALLMENTS')
            || !Configuration::deleteByName('PF_CONCEPTO')
            || !Configuration::deleteByName('PF_OPERATION')
            || !Configuration::deleteByName('PF_CIPHER_KEY')
            || !Configuration::deleteByName('PF_CASH_PAYMENT')
            || !Configuration::deleteByName('PF_SPEI_PAYMENT')
            || !parent::uninstall()
        ) {
            return false;
        }
        return true;
    }

    /**
     * Hook Payment Options
     * @param  array $params Params
     * @return array         List of Payment Options
     */
    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }

        $this->cartCustomer = $params['cart'];
        $this->client = $params['cookie'];

        $paymentMethods = array(
            $this->getPagoFacilPyamentOption(),
        );

        if( Configuration::get( 'PF_CASH_PAYMENT' ) ) {
            $paymentMethods[] = $this->getPagoFacilCashPaymentOption();
        }

        if( Configuration::get( 'PF_SPEI_PAYMENT' ) ) {
            $paymentMethods[] = $this->getPagoFacilSpeiPaymentOption();
        }

        return $paymentMethods;
    }

    /**
     * Get PagoFacil Cash Payment Option
     * @return PaymentOption Payment Option
     */
    protected function getPagoFacilCashPaymentOption()
    {
        $option = new PaymentOption();
        $option->setCallToActionText($this->l('Pagar con PagoFácil Cash'))
            ->setForm($this->generateFormCash())
            ->setAdditionalInformation(
                $this->fetch('module:pagofacil/views/templates/front/payment_infos.tpl')
            );
        return $option;
    }

    /**
     * Get PagoFacil Payment Option
     * @return PaymentOption Payment Option
     */
    protected function getPagoFacilPyamentOption()
    {
        // Create new PagoFacil PaymentOption
        $option = new PaymentOption();
        $option->setCallToActionText($this->l('Pagar con PagoFácil'))
            ->setForm($this->generateForm())
            ->setAdditionalInformation(
                $this->fetch('module:pagofacil/views/templates/front/payment_infos.tpl')
            );
        return $option;
    }

    protected function getPagoFacilSpeiPaymentOption()
    {
        $option = new PaymentOption();
        $option->setCallToActionText($this->l('Pagar con PagoFácil SPEI'))
            ->setForm($this->generateFormSpei())
            ->setAdditionalInformation(
                $this->fetch('module:pagofacil/views/templates/front/payment_infos.tpl')
            );

        return $option;
    }

    /**
     * Generate Form
     * @return SmartyTemplate         View
     */
    protected function generateForm()
    {
        $invoiceAddress = new Address($this->cartCustomer->id_address_invoice);
        $state = new State($invoiceAddress->id_state);

        // Assign Vars to Smarty View
        $this->context->smarty->assign(
            [
                'meses' => $this->getMonths(),
                'anios' => $this->getYears(),
                'nbProducts' => $this->cartCustomer->nbProducts(),
                'monto' => $this->cartCustomer->getOrderTotal(true, Cart::BOTH),
                'total' => Tools::displayPrice(
                    $this->cartCustomer->getOrderTotal(true, Cart::BOTH),
                    new Currency($this->cartCustomer->id_currency),
                    false
                ),
                'nombre' => $this->client->customer_firstname,
                'apellidos' => $this->client->customer_lastname,
                'cp' => $invoiceAddress->postcode,
                'email' => $this->client->email,
                'telefono' => $invoiceAddress->phone,
                'celular' => $invoiceAddress->phone_mobile,
                'calleyNumero' => $invoiceAddress->address1,
                'colonia' => '',
                'municipio' => $invoiceAddress->city,
                'estado' => $state->name,
                'pais' => $invoiceAddress->country,
                'errors' => $this->getValidationErrors(),
                'installments' => (boolean) Configuration::get('PF_INSTALLMENTS'),
                'action' => $this->context->link->getModuleLink(
                    $this->name,
                    'validation',
                    ['type' => 'tp'],
                    true
                )
            ]
        );

        return $this->context->smarty->fetch('module:pagofacil/views/templates/front/payment_form.tpl');
    }

    /**
     * Generate Form Cash
     * @return SmartyTemplate Form
     */
    protected function generateFormCash()
    {
        $this->context->smarty->assign(
            [
                'nbProducts' => $this->cartCustomer->nbProducts(),
                'monto' => $this->cartCustomer->getOrderTotal(true, Cart::BOTH),
                'total' => Tools::displayPrice(
                    $this->cartCustomer->getOrderTotal(true, Cart::BOTH),
                    new Currency($this->cartCustomer->id_currency),
                    false
                ),
                'nombre' => $this->client->customer_firstname,
                'apellidos' => $this->client->customer_lastname,
                'email' => $this->client->email,
                'stores' => $this->getConvenienceStores(),
                'errors' => $this->getValidationErrors(),
                'action' => $this->context->link->getModuleLink(
                    $this->name,
                    'validation',
                    ['type' => 'cash'],
                    true
                )
            ]
        );
        return $this->context->smarty->fetch('module:pagofacil/views/templates/front/payment_cash_form.tpl');
    }

    protected function generateFormSpei()
    {
        $this->context->smarty->assign(
            [
                'nbProducts' => $this->cartCustomer->nbProducts(),
                'monto' => $this->cartCustomer->getOrderTotal(true, Cart::BOTH),
                'total' => Tools::displayPrice(
                    $this->cartCustomer->getOrderTotal(true, Cart::BOTH),
                    new Currency($this->cartCustomer->id_currency),
                    false
                ),
                'nombre' => $this->client->customer_firstname,
                'apellidos' => $this->client->customer_lastname,
                'email' => $this->client->email,
                'id_pedido' => $this->cartCustomer->id,
                'concepto' => $this->cartCustomer->id,
                'errors' => $this->getValidationErrors(),
                'action' => $this->context->link->getModuleLink(
                    $this->name,
                    'validations',
                    ['type' => 'spei'],
                    true
                )
            ]
        );
        return $this->context->smarty->fetch('module:pagofacil/views/templates/front/payment_spei_form.tpl');
    }


    /**
     * Get Convenience Stores
     * @return array Stores
     */
    protected function getConvenienceStores()
    {
        $this->endpoint = 'cash/Rest_Conveniencestores';

        $url = $this->getProcessUrl($this->getValueConfig('PF_ENVIRONMENT', false)) . $this->endpoint;
        $response = $this->executeCurl($url);
        $response = $this->decode($response);

        $stores = [];
        if ($this->lastCurlInfo['http_code'] == 200
            && array_key_exists('records', $response)
            && $response['total'] > 0
        ) {
            $stores = $response['records'];
        }
        return $stores;
    }

    /**
     * Get Valiation Errors
     * @return array Errors
     */
    private function getValidationErrors()
    {
        $errors = [];
        if (array_key_exists('errors', $_SESSION) && is_array($_SESSION['errors'])) {
            foreach ($_SESSION['errors'] as $k => $v) {
                $errors[$k] = $v;
            }
            unset($_SESSION['errors']);
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        return $errors;
    }

    /**
     * Generate Years
     * @return array Years
     */
    private function getYears()
    {
        $years = [];
        $currentYear = date('Y', time());
        for ($i = 0; $i < 6; $i++) {
            $years[] = substr($currentYear + $i, -2);
        }
        return $years;
    }

    /**
     * Generate Months
     * @return array Months
     */
    private function getMonths()
    {
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[] = sprintf("%02s", $i);
        }
        return $months;
    }

    /**
     * Hook Payment Return
     * @param  array $params Params
     * @return SmartyTemplate         Payment Successful
     */
    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return;
        }

        // Generate data
        $data = [
            'total' => Tools::displayPrice(
                $params['order']->getOrdersTotalPaid(),
                new Currency($params['order']->id_currency),
                false
            ),
            'shop_name' => $this->context->shop->name,
            'id_order' => $params['order']->reference,
            'payment' => $params['order']->payment
        ];
        $paramsGet = array_diff_key($_GET, array_flip([
            'id_cart',
            'id_order',
            'id_module',
            'key',
            'customer_order',
            'isolang',
            'id_lang',
            'controller'
        ]));
        $data = array_merge($data, $paramsGet);
        $this->smarty->assign($data);

        return $this->fetch('module:pagofacil/views/templates/front/payment_return.tpl');
    }

    /**
     * Get Content to Update Config
     * @return html Content
     */
    public function getContent()
    {
        $output = null;
        // Update Config Values
        if (Tools::isSubmit('submit'.$this->name)) {
            $this->updateValuesPagoFacil();
            $output .= $this->displayConfirmation(
                $this->l('Settings Updated')
            );
        }
        $output .= $this->extraConfig();
        $output .= $this->displayForm();

        return $output;
    }

    /**
     * Get Extra Config
     * @return SmartyTemplate Extra Config Settings
     */
    private function extraConfig()
    {
        $this->context->smarty->assign(
            [
                'manager_link' => 'http://manager.pagofacil.net/cash/config/',
                'link_webhook' => $this->context->link->getModuleLink($this->name, 'webhook')
            ]
        );
        return $this->display(__FILE__, '../admin/payment_extra_config.tpl');
    }

    /**
     * Display Form to Update Config
     * @return Html Content
     */
    protected function displayForm()
    {
        // Default Language
        $defaultLang = (int) Configuration::get('PS_LANG_DEFAULT');

        // Inint Form Fields
        $fieldsForm[0]['form'] = $this->getFieldsFormConfig();

        $helper = new HelperForm();
        // Module, Token and CurrentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;

        // Language
        $helper->default_form_language = $defaultLang;
        $helper->allow_employee_form_lang = $defaultLang;

        // Title & Toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submit' . $this->name;
        $url = $helper->currentIndex . '&token=' . $helper->token;
        $helper->toolbar_btn = [
            'save' => [
                'desc' => $this->l('Save'),
                'href' => $url . '&save' . $this->name
            ],
            'back' => [
                'href' => $url,
                'desc' => $this->l('Back to list')
            ]
        ];

        $helper->fields_value = $this->getValuesPagoFacil();

        return $helper->generateForm($fieldsForm);
    }

    /**
     * Get Fields to Update Config
     * @return array Inputs Config
     */
    protected function getFieldsFormConfig()
    {
        return [
            'legend' => [
                'title' => 'PagoFácil configuration settings'
            ],
            'input' => [
                [
                    'type' => 'text',
                    'class' => 'input-sm form-control',
                    'label' => 'API Key Usuario (Producción)',
                    'name' => 'PF_API_USER',
                    'size' => 60,
                    'required' => true
                ], [
                    'type' => 'text',
                    'class' => 'input-sm form-control',
                    'label' => 'API Key Sucursal (Producción)',
                    'name' => 'PF_API_BRANCH',
                    'size' => 60,
                    'required' => true
                ],[
                    'type' => 'text',
                    'class' => 'input-sm form-control',
                    'label' => 'API Key Usuario (Sandbox)',
                    'name' => 'PF_API_USER_SANDBOX',
                    'size' => 60,
                    'required' => false
                ], [
                    'type' => 'text',
                    'class' => 'input-sm form-control',
                    'label' => 'API Key Sucursal (Sandbox)',
                    'name' => 'PF_API_BRANCH_SANDBOX',
                    'size' => 60,
                    'required' => false
                ], [
                    'type' => 'text',
                    'class' => 'input-sm form-control',
                    'label' => 'Llave de cifrado',
                    'name' => 'PF_CIPHER_KEY',
                    'size' => 100,
                    'required' => true,
                    'desc' => 'Llave de cifrado para desencriptar la respuesta de 3ds.',
                ], [
                    'type' => 'select',
                    'label' => 'Ambiente',
                    'name' => 'PF_ENVIRONMENT',
                    'desc' => 'Stage/Production environments',
                    'required' => true,
                    'options' => [
                        'query' => [
                            [
                                'id_option' => 0,
                                'name' => 'Stage/Sandbox/Test'
                            ], [
                                'id_option' => 1,
                                'name' => 'Producción'
                            ]
                        ],
                        'id' => 'id_option',
                        'name' => 'name'
                    ]
                ], [
                    'type' => 'select',
                    'label' => 'Currency Exchange',
                    'name' => 'PF_EXCHANGE',
                    'options' => [
                        'query' => [
                            [
                                'id_option' => 'MXN',
                                'name' => 'Mexico (MXN)'
                            ], [
                                'id_option' => 'USD',
                                'name' => 'USA (USD)'
                            ]
                        ],
                        'id' => 'id_option',
                        'name' => 'name'
                    ]
                ], [
                    'type' => 'select',
                    'label' => 'Enabled Installments',
                    'name' => 'PF_INSTALLMENTS',
                    'options' => [
                        'query' => [
                            [
                                'id_option' => 0,
                                'name' => 'No'
                            ], [
                                'id_option' => 1,
                                'name' => 'SÍ'
                            ]
                        ],
                        'id' => 'id_option',
                        'name' => 'name'
                    ]
                ], [
                    'type' => 'text',
                    'class' => 'input-sm form-control',
                    'label' => 'Concept (PagoFácil Cash)',
                    'name' => 'PF_CONCEPTO',
                    'size' => 60,
                    'required' => true
                ],[
                    'type' => 'select',
                    'label' => 'Operación',
                    'name' => 'PF_OPERATION',
                    'desc' => 'Método por el que se van a procesar los pagos',
                    'required' => true,
                    'options' => [
                        'query' => [
                            [
                                'id_option' => 0,
                                'name' => 'Api'
                            ], [
                                'id_option' => 1,
                                'name' => '3DS'
                            ]
                        ],
                        'id' => 'id_option',
                        'name' => 'name'
                    ]
                ], [
                    'type' => 'select',
                    'label' => 'Activar pagos en efectivo',
                    'name' => 'PF_CASH_PAYMENT',
                    'desc' => 'Mostrar la opción de pagos en efectivo como un método de pago.',
                    'required' => true,
                    'options' => [
                        'query' => [
                            [
                                'id_option' => 0,
                                'name' => 'No'
                            ], [
                                'id_option' => 1,
                                'name' => 'Si'
                            ]
                        ],
                        'id' => 'id_option',
                        'name' => 'name'
                    ]
                ],
                [
                    'type' => 'select',
                    'label' => 'Activar pagos con SPEI',
                    'name' => 'PF_SPEI_PAYMENT',
                    'desc' => 'Mostrar la opción de pagos con SPEI como un método de pago.',
                    'required' => true,
                    'options' => [
                        'query' => [
                            [
                                'id_option' => 0,
                                'name' => 'No'
                            ], [
                                'id_option' => 1,
                                'name' => 'Si'
                            ]
                        ],
                        'id' => 'id_option',
                        'name' => 'name'
                    ]
                ]
            ],
            'submit' => [
                'title' => $this->trans('Save'),
                'class' => 'btn btn-primary pull-right'
            ]
        ];
    }

    /**
     * Update Values Config
     * @return void
     */
    protected function updateValuesPagoFacil()
    {
        $apiUser = $this->getValueConfig('PF_API_USER');
        $apiBranch = $this->getValueConfig('PF_API_BRANCH');
        $apiUserSandbox = $this->getValueConfig('PF_API_USER_SANDBOX');
        $apiBranchSandbox = $this->getValueConfig('PF_API_BRANCH_SANDBOX');
        $environment = $this->getValueConfig('PF_ENVIRONMENT');
        $noMail = $this->getValueConfig('PF_NO_MAIL');
        $exchange = $this->getValueConfig('PF_EXCHANGE');
        $installments = $this->getValueConfig('PF_INSTALLMENTS');
        $concept = $this->getValueConfig('PF_CONCEPTO');
        $typeOperation = $this->getValueConfig('PF_OPERATION');
        $cipherKey = $this->getValueConfig('PF_CIPHER_KEY');
        $cashPayment = $this->getValueConfig('PF_CASH_PAYMENT');
        $speiPayment = $this->getValueConfig('PF_SPEI_PAYMENT');

        #var_dump( $cipherKey );exit;

        Configuration::updateValue('PF_API_USER', $apiUser);
        Configuration::updateValue('PF_API_BRANCH', $apiBranch);
        Configuration::updateValue('PF_API_USER_SANDBOX', $apiUserSandbox);
        Configuration::updateValue('PF_API_BRANCH_SANDBOX', $apiBranchSandbox);
        Configuration::updateValue('PF_ENVIRONMENT', $environment);
        Configuration::updateValue('PF_NO_MAIL', $noMail);
        Configuration::updateValue('PF_EXCHANGE', $exchange);
        Configuration::updateValue('PF_INSTALLMENTS', $installments);
        Configuration::updateValue('PF_CONCEPTO', $concept);
        Configuration::updateValue('PF_OPERATION', $typeOperation);
        Configuration::updateValue('PF_CIPHER_KEY', $cipherKey);
        Configuration::updateValue('PF_CASH_PAYMENT', $cashPayment);
        Configuration::updateValue('PF_SPEI_PAYMENT', $speiPayment);
    }

    /**
     * Get Config Values
     * @return array Config Values
     */
    protected function getValuesPagoFacil()
    {
        return [
            'PF_API_BRANCH' => Configuration::get('PF_API_BRANCH'),
            'PF_API_USER' => Configuration::get('PF_API_USER'),
            'PF_API_BRANCH_SANDBOX' => Configuration::get('PF_API_BRANCH_SANDBOX'),
            'PF_API_USER_SANDBOX' => Configuration::get('PF_API_USER_SANDBOX'),
            'PF_ENVIRONMENT' => Configuration::get('PF_ENVIRONMENT'),
            'PF_NO_MAIL' => Configuration::get('PF_NO_MAIL'),
            'PF_EXCHANGE' => Configuration::get('PF_EXCHANGE'),
            'PF_INSTALLMENTS' => Configuration::get('PF_INSTALLMENTS'),
            'PF_CONCEPTO' => Configuration::get('PF_CONCEPTO'),
            'PF_OPERATION' => Configuration::get('PF_OPERATION'),
            'PF_CIPHER_KEY' => Configuration::get('PF_CIPHER_KEY'),
            'PF_CASH_PAYMENT' => Configuration::get('PF_CASH_PAYMENT'),
            'PF_SPEI_PAYMENT' => Configuration::get('PF_SPEI_PAYMENT'),
        ];
    }

    /**
     * Get Config Value
     * @param  string $value Value
     * @param boole $asParam Get From Params or DB
     * @return string        Value
     */
    public function getValueConfig($value, $asParam = true)
    {
        return $asParam ? strval(Tools::getValue($value)) : Configuration::get($value);
    }

    /**
     * Execute Curl to process payment
     * @param  string $url URL
     * @return array      Response
     */
    public function executeCurl($url, $method = 'GET', $body = [])
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

    /**
     * Decode Response
     * @param  mixed $response Response
     * @return array           Response Decoded
     */
    public function decode($response)
    {
        return json_decode($response, true);
    }

    /**
     * Encode Data
     * @param  mixed $data Data
     * @return JSON       JSON String
     */
    public function encode($data)
    {
        return json_encode($data);
    }

     /**
     * Redirect to Checkout Step
     * @param  integer $step Step
     * @return Redirect        Redirect to Step
     */
    public function redirectToStep($step = 1)
    {
        $step = $step >= 1 && $step <= 4 ? $step : 1;
        $path = $this->pathToCheckout . "&step=" . $step;
        Tools::redirect($path);
    }
}