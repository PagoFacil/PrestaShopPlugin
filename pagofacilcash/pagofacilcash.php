<?php
if (!defined('_PS_VERSION_'))
	exit;

class Pagofacilcash extends PaymentModule
{
    public function __construct()
    {
        $ver = '1.0';
        $by = 'javolero';
        $this->name = 'pagofacilcash';
        $this->tab = 'payments_gateways';
        $this->version = $ver;
        $this->author = $by;
        $this->need_instance = 1;

        parent::__construct();

        $this->displayName = $this->l('PagoFacil Cash');
        $this->description = $this->l('Modulo para aceptar pagos en efectivo en tiendas de conveniencia');
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
        if (!Configuration::deleteByName('PFC_API_USER')
            || !Configuration::deleteByName('PFC_API_BRANCH')
            || !Configuration::deleteByName('PFC_ENVIRONMENT')
            || !Configuration::deleteByName('PFC_CONCEPTO')
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
            $pf_api_user     = strval(Tools::getValue('PFC_API_USER'));
            $pf_api_branch   = strval(Tools::getValue('PFC_API_BRANCH'));
            $pf_env          = strval(Tools::getValue('PFC_ENVIRONMENT'));
            $pf_concepto = strval(Tools::getValue('PFC_CONCEPTO'));
            
            //validate both apis TODO
            if(false)
            {
            
            }
            else
            {
                Configuration::updateValue('PFC_API_USER',    $pf_api_user);
                Configuration::updateValue('PFC_API_BRANCH',  $pf_api_branch);
                Configuration::updateValue('PFC_ENVIRONMENT', $pf_env);
                Configuration::updateValue('PFC_CONCEPTO',$pf_concepto);
                $output = "Values Saved -" . $pf_env . "-";
            }
        }
        
        
        $output.= $this->renderForm();
        
        
        $output.= '<h3>WEBHOOK</h3><br/><span>Debes configurar est&aacute; url en el panel de PagoF&aacute;cil en la secci&oacute;n de webhook. </span> <br/><br/>'. $this->context->link->getModuleLink('pagofacilcash', 'webhook') .'<br/>';
        
        return $output;
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
                    ,'name' => 'PFC_API_USER'
                    ,'size' => 60
                    ,'required' => true
                ),
                array(
                    'type' => 'text'
                    ,'label' => 'API Key Sucursal'
                    ,'name' => 'PFC_API_BRANCH'
                    ,'size' => 60
                    ,'required' => true
                ),
                array(
                    'type' => 'select'
                    ,'label' => 'Environment'
                    ,'name' => 'PFC_ENVIRONMENT'
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
                ),
                array(
                    'type' => 'text'
                    ,'label' => 'Concepto'
                    ,'name' => 'PFC_CONCEPTO'
                    ,'size' => 60
                    ,'required' => true
                ),
                
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
                
        $helper->fields_value['PFC_API_BRANCH']   = Configuration::get('PFC_API_BRANCH');
        $helper->fields_value['PFC_API_USER']     = Configuration::get('PFC_API_USER');
        $helper->fields_value['PFC_ENVIRONMENT']  = Configuration::get('PFC_ENVIRONMENT');
        $helper->fields_value['PFC_CONCEPTO'] = Configuration::get('PFC_CONCEPTO');
        
        return $helper->generateForm($fields_form);
    }
    
    
    public function setOrderStatus($order_id, $status)
	{
		$history = new OrderHistory();
		$history->id_order = $order_id;
		$history->id_order_state = $status;
		$history->changeIdOrderState($status, $order_id);
		
		$history->addWithemail();
		
		return $history;
	}
    
    
    
    
    
}