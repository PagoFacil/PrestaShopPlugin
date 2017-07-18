<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

class Pagofacil extends PaymentModule
{
    public function __construct()
    {
        $this->name = 'pagofacil';
        $this->tab = 'payments_gateways';
        $this->version = '2.0';
        $this->author = 'PagoFácil';
        $this->need_instance = 1;
        $this->ps_versions_compliancy = [
            'min' => '1.7',
            'max' => _PS_VERSION_
        ];
        $this->controllers = ['validation'];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('PagoFácil');
        $this->description = $this->l(
            'Éste módulo te permite aceptar pagos con Visa, Mastercard y AMEX'
        );
        $this->confirmUninstall = $this->l(
            '¿Estás seguro que desea desinstalar el módulo?'
        );
    }

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

    public function uninstall()
    {
        if (!Configuration::deleteByName('PF_API_USER')
            || !Configuration::deleteByName('PF_API_BRANCH')
            || !Configuration::deleteByName('PF_ENVIRONMENT')
            || !Configuration::deleteByName('PF_NO_MAIL')
            || !Configuration::deleteByName('PF_EXCHANGE')
            || !Configuration::deleteByName('PF_INSTALLMENTS')
            || !parent::uninstall()
        ) {
            return false;
        }
        return true;
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }

        $option = new PaymentOption();
        $option->setCallToActionText($this->l('Pagar con PagoFácil'))
            ->setForm($this->generateForm($params))
            ->setAdditionalInformation(
                $this->fetch('module:pagofacil/views/templates/front/payment_infos.tpl')
            );

        return [$option];
    }

    protected function generateForm($params)
    {
        $cart = $params['cart'];
        $customer = $params['cookie'];
        $invoiceAddress = new Address($cart->id_address_invoice);
        $state = new State($invoiceAddress->id_state);

        $this->context->smarty->assign([
            'meses' => $this->getMonths(),
            'anios' => $this->getYears(),
            'nbProducts' => $cart->nbProducts(),
            'monto' => $cart->getOrderTotal(true, Cart::BOTH),
            'total' => Tools::displayPrice(
                $cart->getOrderTotal(true, Cart::BOTH),
                new Currency($params['cart']->id_currency),
                false
            ),
            'currency' => $this->getCurrency($cart->id_currency)[0],
            'nombre' => $customer->customer_firstname,
            'apellidos' => $customer->customer_lastname,
            'cp' => $invoiceAddress->postcode,
            'email' => $customer->email,
            'telefono' => $invoiceAddress->phone,
            'celular' => $invoiceAddress->phone_mobile,
            'calleyNumero' => $invoiceAddress->address1,
            'colonia' => '',
            'municipio' => $invoiceAddress->city,
            'estado' => $state->name,
            'pais' => $invoiceAddress->country,
            'errors' => $this->getErrorsValidation(),
            'installments' => (boolean) Configuration::get('PF_INSTALLMENTS'),
            'action' => $this->context->link->getModuleLink($this->name, 'validation', array(), true)
        ]);

        return $this->context->smarty->fetch('module:pagofacil/views/templates/front/payment_form.tpl');
    }

    private function getErrorsValidation()
    {
        $errors = [];
        if ( array_key_exists('errors', $_SESSION) && is_array($_SESSION['errors'])) {
            foreach ($_SESSION['errors'] as $k => $v) {
                $errors[$k] = $v;
            }
            unset($_SESSION['errors']);
        }
        session_destroy();
        return $errors;
    }

    private function getYears()
    {
        $years = [];
        $currentYear = date('Y', time());
        for ($i = 0; $i < 6; $i++) {
            $years[] = substr($currentYear + $i, -2);
        }
        return $years;
    }

    private function getMonths()
    {
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[] = sprintf("%02s", $i);
        }
        return $months;
    }

    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return;
        }

        $this->smarty->assign(array(
            'total' => Tools::displayPrice(
                $params['order']->getOrdersTotalPaid(),
                new Currency($params['order']->id_currency),
                false
            ),
            'shop_name' => $this->context->shop->name,
            'id_order' => $params['order']->reference,
            'payment' => $params['order']->payment,
            'transaction' => Tools::getValue('transaction'),
            'no_authorization' => Tools::getValue('no_authorization'),
            'description' => Tools::getValue('description'),
            'message' => Tools::getValue('message'),
            'status' => Tools::getValue('status')
        ));

        return $this->fetch('module:pagofacil/views/templates/front/payment_return.tpl');
    }
    
    public function getContent()
    {
        $output = null;
        
        if (Tools::isSubmit('submit'.$this->name)) {
            $this->updateValuesPagoFacil();
            $output .= $this->displayConfirmation(
                $this->l('Settings Updated')
            );
        }

        return $output . $this->displayForm();
    }

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
                    'label' => 'API Key Usuario',
                    'name' => 'PF_API_USER',
                    'size' => 60,
                    'required' => true
                ], [
                    'type' => 'text',
                    'class' => 'input-sm form-control',
                    'label' => 'API Key Sucursal',
                    'name' => 'PF_API_BRANCH',
                    'size' => 60,
                    'required' => true
                ], [
                    'type' => 'select',
                    'label' => 'Environment',
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
                                'name' => 'Production'
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
                ]
            ],
            'submit' => [
                'title' => $this->trans('Save'),
                'class' => 'btn btn-primary pull-right'
            ]
        ];
    }

    protected function updateValuesPagoFacil()
    {
        $apiUser = $this->getValueConfig('PF_API_USER');
        $apiBranch = $this->getValueConfig('PF_API_BRANCH');
        $environment = $this->getValueConfig('PF_ENVIRONMENT');
        $noMail = $this->getValueConfig('PF_NO_MAIL');
        $exchange = $this->getValueConfig('PF_EXCHANGE');
        $installments = $this->getValueConfig('PF_INSTALLMENTS');

        Configuration::updateValue('PF_API_USER',    $apiUser);
        Configuration::updateValue('PF_API_BRANCH',  $apiBranch);
        Configuration::updateValue('PF_ENVIRONMENT', $environment);
        Configuration::updateValue('PF_NO_MAIL',     $noMail);
        Configuration::updateValue('PF_EXCHANGE',    $exchange);
        Configuration::updateValue('PF_INSTALLMENTS', $installments);
    }

    protected function getValuesPagoFacil()
    {
        return [
            'PF_API_BRANCH' => Configuration::get('PF_API_BRANCH'),
            'PF_API_USER' => Configuration::get('PF_API_USER'),
            'PF_ENVIRONMENT' => Configuration::get('PF_ENVIRONMENT'),
            'PF_NO_MAIL' => Configuration::get('PF_NO_MAIL'),
            'PF_EXCHANGE' => Configuration::get('PF_EXCHANGE'),
            'PF_INSTALLMENTS' => Configuration::get('PF_INSTALLMENTS')
        ];
    }

    protected function getValueConfig($value)
    {
        return strval(Tools::getValue($value));
    }   
}