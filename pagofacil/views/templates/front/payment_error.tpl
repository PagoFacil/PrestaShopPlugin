{extends "$layout"}

{block name="content"}
<section>
    <div class="cart-grid row">
        <div class="cart-grid-body col-md-12">
            <div class="card cart-container">
                <div class="card-block">
                    <h2>Payment Denied!</h2>
                    <hr>
                </div>

                <div class="cart-overview">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="alert alert-danger">
                            	<strong>
                            		<h3>{l s=$params['error']}</h3>
                            	</strong>
                                <hr><br>
                                {if isset($errors) && count($errors) > 0}
                                    <ol>
                                        {foreach from=$errors item=error}
                                            <li>{$error}</li>
                                        {/foreach}
                                    </ol>
                                {/if}
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                        	<a href="{$params['link']}" class="btn btn-lg btn-success">
                        		{l s='Intentar Nuevamente'}
                        	</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
{/block}