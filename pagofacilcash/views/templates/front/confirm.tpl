<style>
	@media screen, print {
		.cp-select-form {
			margin: 20px 20px 20px 20px !important;
			border: 1px solid #dae3e9;
			border-radius: 3px;
			padding: 10px;
			background: #fafbfd;
		}
	
		.cp-instruction-section {
			background: #FFF;
			width: 600px;
			border: 1px solid #DAE2E7;
			margin: 0px 0px 15px 0px;
			border-radius: 8px;
			float: left;
			background-position: 0px 40px;
			font-size: 11pt;
			box-shadow: 0px 1px 4px 1px #E2E2E2;
		}
		.cp-instruction-section .cp-title {
			height: 31px;
			border-bottom: 1px solid #dee3ea;
			color: #000;
			font-size: 11pt;
			font-style: italic;
			line-height: 34px;
			float: left;
			width: 50%;
			margin: 25px 0px 15px 20px;
		}
		.cp-step-box {
			width: 600px;
			color: #000;
			clear: none;
			float: left;
		}
		.cp-step {
			line-height: 25pt;
			margin: 0px 0px 0px 30px;
			font-size: 13px;
			float: left;
			clear: both;
			width: 100%;
		}
		.cp-step .cp-num {
			float: left;
			margin: 0px 8px 0px 0px;
		}
		hr.cp-grey {
			margin: 10px 0;
			width: 100%;
			border-top: 1px solid #dae3e9 !important;
			border-bottom: 1px solid white;
			box-sizing: content-box;
			height: 0;
			text-rendering: optimizelegibility;
			font-family: inherit;
			border-style: none;
		}
		.cp-note {
			clear: both;
			float: left;
			margin: 0px 0px 0px 20px;
			height: 29px;
		}
	
		.cp-warning-box {
			float: left;
			background: #FFFEEC;
			border: 1px solid #DFDB83;
			border-radius: 3px;
			box-shadow: 0px 1px 3px 1px #E2E2E2;
			clear: both;
			color: #000;
			padding: 10px 10px;
			margin: 0px 0px 14px 0px !important;
			width: 600px;
		}
		
		.buttons-set{
			float: left;
			width: 600px;

		}
	
		ul.cp-warning {
			font-size: 12px;
			margin-left: 30px;
		}
		ul.cp-warning li {
			line-height: 20px;
			list-style-type: disc;
		}
		.cp-wrap-price{
			float:right;
			font-size: 12px;
	
		}
		.cp-price{
			font-weight: bold;
			font-size: 13px;
		}
	
		.cp-warning-box-price {
			background: #FFFEEC;
			border: 1px solid #DFDB83;
			border-radius: 3px;
			box-shadow: 0px 1px 3px 1px #E2E2E2;
			clear: both;
			color: #000;
			padding: 10px 10px;
			margin: 9px 0px 20px 0px !important;
		}
		.cp-label-instructions{
			line-height: 42px;
			font-size: 12px;
		}
		.cp-select-instructions{
			height: 30px;
		}
		.expiration-date{
			float: right;
			background: #EFF6FD;
			margin: 18px 18px 0px 440px;
			border: 1px solid #dae3e9;
			border-radius: 3px;
			box-shadow: 0px 1px 4px 1px #f1f1f3 inset;
			color: #000;
			width: 140px;
			font-size: 12px;
			position: absolute;
			text-align: center;
			padding: 10px 0px;
		}
		.expiration-date span {
			font-size: 18px;
			font-weight: 500;
			color: #32a0ee;
			margin: 7px 2px 0px;
		text-align: center;
		width: 100%;
		float: left;
		}
		.checkout-onepage-success .col-main {	  
		  text-align: left !important;
		}
		.cp-step .cp-image-store {
			padding: 5px 0 5px 50px;
		}	
	}
	
</style>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

<div class="page-title">
    <h3>¡Felicitaciones! Su pedido ha sido generado correctamente.</h3>
</div>


 <div class="cp-instruction-section">
  <div class="expiration-date">
    Último día para pagar:
    <span >
    {$dateEx}
    </span>
  </div>

  <div class="cp-title">Seguir los siguientes pasos:</div>

  <div class="cp-step-box">
    <div class="cp-step">
          <div class="cp-num">1.</div> <span> Ir a la caja {$transaction['convenience_store']} de {$transaction['store_schedule']} </span><br/> <img src="{$transaction['store_image']}" class="cp-image-store" />
          
    </div>
    <div class="cp-step">
          <div class="cp-num">2.</div> Solicitar depósito a cuenta (debito): {$transaction['bank']} - {$transaction['bank_account_number']}
    </div>
    <div class="cp-step">
        <div class="cp-num">3.</div> Deposita la cantidad <b>EXACTA</b> de: $<b>{$transaction['amount']}</b>
    </div>
  </div>
  <hr class="cp-grey">
  <span class="cp-note" style="font-size:12px;color: #333;">{$transaction['convenience_store']} cobra en caja una comisión de $ {$transaction['store_fixed_rate']} por el concepto de recepción de cobranza.</span>
</div>

<div class="cp-warning-box">
    
    <span style="font-size: 12px;"><b>Importante</b></span>
    <ul style="" class="cp-warning">
	    <li>El ID de control es: <b>{$transaction['reference']}</b></li>
		<li>El número de cuenta/tarjeta asignado es único por cada orden de compra.</li>
		<li>Orden válida antes de {$transaction['expiration_date']}, en caso de vencimiento genera una nueva compra.</li>
		<li>{$transaction['convenience_store']} cobra en caja una comisión de $ {$transaction['store_fixed_rate']} por el concepto de recepción de cobranza.</li>

    </ul>
</div> 



<br/><br/>


<div class="buttons-set"> 
    <a href="{$url}" class="button_large">{l s='Continuar' mod='pagofacilcash'}</a>
</div>