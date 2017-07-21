<?php
if (!defined('_PS_VERSION_'))
	exit;

class Pagofacil extends PaymentModule
{
    public function __construct()
    {
        $ver = '1.0';
        $by = 'PagoF&aacute;cil / DreasmEngineering';
        $this->name = 'pagofacil';
        $this->tab = 'payments_gateways';
        $this->version = $ver;
        $this->author = $by;
        $this->need_instance = 1;

        parent::__construct();

        $this->displayName = $this->l('PagoFacil');
        $this->description = $this->l('Modulo para aceptar pago con Visa, Mastercard y AMEX');
        $this->confirmUninstall = $this->l('¿Esta seguro que desea borrar el modulo?');
    }

    public function install()
    {
        if (!parent::install() || !$this->registerHook('payment'))
        {
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

    public function hookPayment($params)
    {
        if (!$this->active)
        {
            return;
        }

        $this->smarty->assign(array(
            'this_path'     => $this->_path,
            'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/'
         ));
        return $this->display(__FILE__, 'payment.tpl');
    }
    
    public function getContent()
    {
        $output = null;
        
        if(Tools::isSubmit('submit'.$this->name)) {
            $pf_api_user     = strval(Tools::getValue('PF_API_USER'));
            $pf_api_branch   = strval(Tools::getValue('PF_API_BRANCH'));
            $pf_env          = strval(Tools::getValue('PF_ENVIRONMENT'));
            $pf_no_mail      = strval(Tools::getValue('PF_NO_MAIL'));
            $pf_exchange     = strval(Tools::getValue('PF_EXCHANGE'));
            $pf_installments = strval(Tools::getValue('PF_INSTALLMENTS'));
            
            //validate both apis TODO
            if(false)
            {
            
            }
            else
            {
                Configuration::updateValue('PF_API_USER',    $pf_api_user);
                Configuration::updateValue('PF_API_BRANCH',  $pf_api_branch);
                Configuration::updateValue('PF_ENVIRONMENT', $pf_env);
                Configuration::updateValue('PF_NO_MAIL',     $pf_no_mail);
                Configuration::updateValue('PF_EXCHANGE',    $pf_exchange);
                Configuration::updateValue('PF_INSTALLMENTS',$pf_installments);
                $output = "Values Saved -" . $pf_env . "-";
            }
        }
        
        return $output.$this->renderForm();
    }
    
    public function renderForm()
    {
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => 'PagoF&aacute;cil configuration settings',
            ),
            'input' => array(
                array(
                    'type' => 'text'
                    ,'label' => 'API Key Usuario'
                    ,'name' => 'PF_API_USER'
                    ,'size' => 60
                    ,'required' => true
                ),
                array(
                    'type' => 'text'
                    ,'label' => 'API Key Sucursal'
                    ,'name' => 'PF_API_BRANCH'
                    ,'size' => 60
                    ,'required' => true
                ),
                array(
                    'type' => 'select'
                    ,'label' => 'Environment'
                    ,'name' => 'PF_ENVIRONMENT'
                    ,'desc' => 'Select between Test/Production environments'
                    ,'required' => true
                    ,'options' => array(
                        'query' => array(
                            array(
                                'id_option' => 1
                                ,'name' => 'Staging/Tests (NON Production)'
                            )
                            ,array(
                                'id_option' => 2
                                ,'name' => 'Production'
                            )
                        )
                        ,'id' => 'id_option'
                        ,'name' => 'name'
                    )
                )
                ,array(
                    'type' => 'select'
                    ,'label' => 'Send Notification'
                    ,'name' => 'PF_NO_MAIL'
                    ,'desc' => 'Allow PagoF&aacute;cil send notification to customer'
                    ,'options' => array(
                        'query' => array(
                            array(
                                'id_option' => 0
                                ,'name' => 'SI'
                            )
                            ,array(
                                'id_option' => 1
                                ,'name' => 'NO'
                            )
                        )
                        ,'id' => 'id_option'
                        ,'name' => 'name'
                    )
                )
                ,array(
                    'type' => 'select'
                    ,'label' => 'Currency Exchange'
                    ,'name' => 'PF_EXCHANGE'
                    ,'options' => array(
                        'query' => array(
                            array(
                                'id_option' => 'MXN'
                                ,'name' => 'Mexico (MXN)'
                            )
                            ,array(
                                'id_option' => 'USD'
                                ,'name' => 'United States of America (USD)'
                            )
                        )
                        ,'id' => 'id_option'
                        ,'name' => 'name'
                    )
                )
                ,array(
                    'type' => 'select'
                    ,'label' => 'Enabled Installments'
                    ,'name' => 'PF_INSTALLMENTS'
                    ,'options' => array(
                        'query' => array(
                            array(
                                'id_option' => 0
                                ,'name' => 'NO'
                            )
                            ,array(
                                'id_option' => 1
                                ,'name' => 'SI'
                            )
                        )
                        ,'id' => 'id_option'
                        ,'name' => 'name'
                    )
                )
            )
            ,'submit' => array(
                'title' => 'Save Changes'
                ,'class' => 'button'
            )
        );
        
        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
          
        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = false;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = array(
            'save' =>
            array(
                'desc' => $this->l('Save')
                ,'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                           '&token='.Tools::getAdminTokenLite('AdminModules')
            )
            ,'back' => array(
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules')
                ,'desc' => $this->l('Back to list')
            )
        );
                
        $helper->fields_value['PF_API_BRANCH']   = Configuration::get('PF_API_BRANCH');
        $helper->fields_value['PF_API_USER']     = Configuration::get('PF_API_USER');
        $helper->fields_value['PF_ENVIRONMENT']  = Configuration::get('PF_ENVIRONMENT');
        $helper->fields_value['PF_NO_MAIL']      = Configuration::get('PF_NO_MAIL');
        $helper->fields_value['PF_EXCHANGE']     = Configuration::get('PF_EXCHANGE');
        $helper->fields_value['PF_INSTALLMENTS'] = Configuration::get('PF_INSTALLMENTS');
        
        return $helper->generateForm($fields_form);
    }
    
    
    
}