{capture name=path}{l s='pagofacil payment' mod='pagofacil'}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}

<h2 class="title-img"><img src="{$this_path}logo.png" alt="{l s='pagofacil' mod='pagofacil'}" /></h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if isset($nbProducts) && $nbProducts <= 0}
    <p class="warning">{l s='Su carrito se encuentra vac&iacute;o' mod='pagofacil'}</p>
{else}
<style>
    ul.pay-form {
        margin: 0;
        padding: 0;
        display: block;
    }
    ul.pay-form > li {
        list-style: none;
    }
    ul.pay-form > li > label {
        width: 28%;
        height: 20px;
        display: inline-block;
    }
    ul.pay-form > li > input    
    ,ul.pay-form > li > select {
        border: 1px solid #D9D9D9;
        border-top: 1px solid #c0c0c0;
    }
    
    .small {
        width: 20%;
    }
    .medium {
        width: 40%;
    }
    .large {
        width: 60%;
    }
</style>
<form name="formularioPago" method="POST" action="{$link->getModuleLink('pagofacil', 'validation', [], true)|escape:'html'}">
    <div class="info">
        <p>
            {l s='Escogi&oacute pagar con PagoF&aacute;cil.' mod='pagofacil'}
            - {l s='El total de su orden es:' mod='pagofacil'}
            <span id="amount" class="price">{displayPrice price=$monto}</span>
            <strong>Por Favor revise sus datos y complete el formulario.</strong>
            Para realizar su pago por medio de PagoF&aacute;cil haga click en el bot&oacute;n comprar
        </p>
    </div>
            
    {if count($errores) > 0}
    <div class="error">
        {foreach from=$errores item=error}
            <strong>{$error}</strong><br/>
        {/foreach}
    </div>
    {/if}

    <ul class="pay-form">
        <li>
            <label for="input-numeroTarjeta">N&uacute;mero de Tarjeta:</label>
            <input id="input-numeroTarjeta" type="text" name="numeroTarjeta" autocomplete="off" class="medium">
        </li>
        <li>            
            <label for="select-mesExpiracion">Expiraci&oacute;n:</label>
            <select name="mesExpiracion" id="select-mesExpiracion" class="small">
                <option value="" selected="selected">Mes de expiraci&oacute;n:</option>
                {foreach from=$meses item=mes}
                    <option value="{$mes}">{$mes}</option>
                {/foreach}
            </select>                        
            <select name="anyoExpiracion" id="select-anyoExpiracion" class="small">
                <option value="" selected="selected">A&ntilde;o de expiraci&oacute;n:</option>
                {foreach from=$anyos item=anyo}
                    <option value="{$anyo}">{$anyo}</option>
                {/foreach}
            </select>	
        </li>
        <li>
            <label for="input-cvt" class="required">C&oacute;digo Cvv2:</label>
            <input id="input-cvt" type="password" name="cvt" autocomplete="off" class="small" maxlength="4" />
        </li>
        <li>
            <label for="input-nombre">Nombre(s):</label>
            <input id="input-nombre" type="text" name="nombre" value="{$nombre}" autocomplete="off" class="large">
        </li>
        <li>
            <label for="input-apellidos">Apellidos:</label>
            <input id="input-apellidos" type="text" name="apellidos" value="{$apellidos}" autocomplete="off" class="large">
        </li>
        
        <li>
            <label for="input-cp">C&oacute;digo Postal:</label>
            <input id="input-cp" type="text" name="cp" value="{$cp}" autocomplete="off" class="small">
        </li>
        <li>
            <label for="input-email">Correo Electr&oacute;nico:</label>
            <input id="input-email" type="text" name="email" value="{$email}" autocomplete="off" class="medium">
        </li>
        <li>
            <label for="input-telefono">Tel&eacute;fono (10 d&iacute;gitos):</label>
            <input id="input-telefono" type="text" name="telefono" value="{$telefono}" autocomplete="off" class="medium" maxlength="10">
        </li>
        <li>
            <label for="input-celular">Celular (10 d&iacute;gitos):</label>
            <input id="input-celular" type="text" name="celular" value="{$celular}" autocomplete="off" class="medium" maxlength="10">
        </li>
        <li>
            <label for="input-calleyNumero">Calle y N&uacute;mero:</label>
            <input id="input-calleyNumero" type="text" name="calleyNumero" value="{$calleyNumero}" autocomplete="off" class="large">
        </li>
        <li>
            <label for="input-colonia">Colonia:</label>
            <input id="input-colonia" type="text" name="colonia" value="{$colonia}" autocomplete="off" class="medium">
        </li>
        <li>
            <label for="input-municipio" class="required">Delegaci&oacute;n o Municipio:</label>
            <input id="input-municipio" type="text" name="municipio" value="{$municipio}" autocomplete="off" class="medium">
        </li>
        <li>
            <label for="input-estado" class="required">Estado:</label>
            <input id="input-estado" type="text" name="estado" value="{$estado}" autocomplete="off" class="medium">
        </li>
        <li>
            <label for="input-pais" class="required">Pa&iacute;s:</label>
            <input id="input-pais" type="text" name="pais" value="{$pais}" autocomplete="off" class="medium">
        </li>
        {if Configuration::get('PF_INSTALLMENTS') eq '1'}
            <li>
                <label for="select_msi">Meses sin intereses:</label>
                <select id="select_msi" name="msi" class="medium">
                    <option value="00">Seleccione</option>
                    <optgroup label="MasterCard/Visa"></optgroup>
                    <option value="03">3 Meses</option>
                    <option value="06">6 Meses</option>
                    <optgroup label="American Express"></optgroup>
                    <option value="03">3 Meses</option>
                    <option value="06">6 Meses</option>
                    <option value="09">9 Meses</option>
                    <option value="12">12 Meses</option>
                </select>
            </li>
        {/if}
    </ul>
    <p class="cart_navigation" id="cart_navigation">
        <input type="submit" value="{l s='Place my order' mod='pagofacil'}" class="exclusive_large" />
        <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}" class="button_large">{l s='Other payment methods' mod='pagofacil'}</a>
    </p>
</form>
{/if}