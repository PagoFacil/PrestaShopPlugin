<form action="{$action}" id="pagofacil" method="POST">

{if count($errors) > 0}
    <div class="alert alert-danger">
        <h4>{l s='Errores'}</h4>
        <hr>
        <ol>
        {foreach from=$errors item=error}
            <li>
                <strong>{$error}</strong>
            </li>
        {/foreach}
        </ol>
    </div>
{/if}
	
	<div class="alert alert-success">
		<strong>{l s="Monto a pagar $ $total"}</strong>
	</div>

	<div class="form-group">
    	<label for="nombre" class="control-label">{l s='Nombre(s)'}</label>
		<input type="text" class="form-control input-sm" name="nombre" id="nombre" value="{$nombre}" required="required">
  	</div>

  	<div class="form-group">
    	<label for="apellidos" class="control-label">{l s='Apellidos(s)'}</label>
		<input type="text" class="form-control input-sm" name="apellidos" id="apellidos" value="{$apellidos}" required="required">
  	</div>

  	<div class="form-group">
    	<label for="numeroTarjeta" class="control-label">{l s='Número de Tarjeta'}</label>
		<input type="text" class="form-control input-sm" name="numeroTarjeta" id="numeroTarjeta" required="required" autocomplete="off">
  	</div>

  	<div class="form-group">
    	<label for="mesExpiracion" class="control-label">{l s='Fecha Expiración'}</label>
    	<div class="col-md-6">
    		<select name="mesExpiracion" id="mesExpiracion" class="form-control input-sm">
	    		{foreach from=$meses item=mes}
	    			<option value="{$mes}">{$mes}</option>
	    		{/foreach}
	    	</select>
    	</div>

    	<div class="col-md-6">
    		<select name="anioExpiracion" id="anioExpiracion" class="form-control input-sm">
	    		{foreach from=$anios item=anio}
	    			<option value="{$anio}">{$anio}</option>
	    		{/foreach}
	    	</select>
    	</div>
  	</div>

  	<div class="form-group">
    	<label for="cvt" class="control-label">{l s='Código de Seguridad'}</label>
		<input type="text" class="form-control input-sm" name="cvt" id="cvt" required="required" autocomplete="off" maxlength="4">
  	</div>

  	<div class="form-group">
    	<label for="email" class="control-label">{l s='Email'}</label>
		<input type="email" class="form-control input-sm" id="email" name="email" value="{$email}" required="required" autocomplete="off">
  	</div>

  	<div class="form-group">
    	<label for="telefono" class="control-label">{l s='Teléfono'}</label>
		<input type="tel" class="form-control input-sm" id="telefono" name="telefono" value="{$telefono}" required="required" maxlength="10" autocomplete="off">
  	</div>

  	<div class="form-group">
    	<label for="celular" class="control-label">{l s='Celular'}</label>
		<input type="tel" class="form-control input-sm" id="celular" name="celular" value="{$celular}" required="required" maxlength="10" autocomplete="off">
  	</div>

  	<div class="form-group">
    	<label for="calleyNumero" class="control-label">{l s='Calle - Número'}</label>
		<input type="text" class="form-control input-sm" id="calleyNumero" name="calleyNumero" value="{$calleyNumero}" required="required" autocomplete="off">
  	</div>

  	<div class="form-group">
    	<label for="colonia" class="control-label">{l s='Colonia'}</label>
		<input type="text" class="form-control input-sm" name="colonia" id="colonia" value="{$colonia}" required="required" autocomplete="off">
  	</div>

  	<div class="form-group">
    	<label for="municipio" class="control-label">{l s='Delegación/Municipio'}</label>
		<input type="text" class="form-control input-sm" name="municipio" id="municipio" value="{$municipio}" required="required" autocomplete="off">
  	</div>

  	<div class="form-group">
    	<label for="estado" class="control-label">{l s='Estado'}</label>
		<input type="text" class="form-control input-sm" name="estado" id="estado" value="{$estado}" required="required" autocomplete="off">
  	</div>

  	<div class="form-group">
    	<label for="pais" class="control-label">{l s='País'}</label>
		<input type="text" class="form-control input-sm" name="pais" id="pais" value="{$pais}" required="required" autocomplete="off">
  	</div>

  	<div class="form-group">
    	<label for="cp" class="control-label">{l s='Código Postal'}</label>
		<input type="text" class="form-control input-sm" name="cp" id="cp" value="{$cp}" required="required" maxlength="5">
  	</div>

  	{if $installments eq true}
	  	<div class="form-group">
	    	<label for="msi" class="control-label">{l s='Meses sin intereses'}</label>
	    	<select name="msi" id="msi" class="form-control input-sm">
	    		<option value="00">Seleccione</option>
		        <optgroup label="MasterCard/Visa"></optgroup>
		        <option value="03">3 Meses</option>
		        <option value="06">6 Meses</option>
				<option value="09">9 Meses</option>
		        <option value="12">12 Meses</option>
		        <optgroup label="American Express"></optgroup>
		        <option value="03">3 Meses</option>
		        <option value="06">6 Meses</option>
		        <option value="09">9 Meses</option>
		        <option value="12">12 Meses</option>
	    	</select>
	  	</div>
  	{/if}

    <div class="alert alert-warning">
        <strong>*Todos los campos son obligatorios</strong>
    </div>
</form>