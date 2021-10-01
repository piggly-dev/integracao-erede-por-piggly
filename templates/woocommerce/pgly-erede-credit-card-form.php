<?php

use Piggly\WooERedeGateway\CoreConnector;

if ( ! defined( 'ABSPATH' ) ) { exit(); } 
?>
<fieldset id="pgly-erede-credit-payment-form" class="pgly-erede-payment-form">
    <p class="form-row form-row-wide">
      <label for="pgly-erede-card-number">
			<?php echo esc_html(CoreConnector::__translate('Número do cartão')); ?>
			<span class="required">*</span>
		</label> 
		<input 
			id="pgly-erede-card-number"
			name="pgly_erede_credit_number"
			class="input-text wc-credit-card-form-card-number"
			type="tel"
			maxlength="22" autocomplete="off"
			placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;"
			style="font-size: 1.5em; padding: 8px;"/>
    </p>

	<?php if ( is_array( $installments ) && count( $installments ) > 1 ) : ?>
	<p class="form-row form-row-wide">
		<label for="installments">
			<?php echo esc_html(CoreConnector::__translate('Selecione a quantidade de parcelas')); ?>
			<span class="required">*</span>
		</label>
		<select id="installments" class="wc-enhanced-select select" name="pgly_erede_credit_installments">
			<?php
			foreach ( $installments as $installment ) 
			{ printf( '<option value="%d">%s</option>', $installment['num'], $installment['label'] ); }
			?>
		</select>
	</p>
	<?php endif; ?>
   <p class="form-row form-row-wide">
      <label for="pgly-erede-card-holder-name">
			<?php echo esc_html(CoreConnector::__translate('Nome impresso no cartão')); ?>
			<span class="required">*</span>
		</label>
			<input 
				id="pgly-erede-card-holder-name"
				name="pgly_erede_credit_holder_name" class="input-text"
				type="text"
				autocomplete="off"
				style="font-size: 1.5em; padding: 8px;"/>
   </p>
   <p class="form-row form-row-first">
      <label for="pgly-erede-card-expiry">
			<?php echo esc_html(CoreConnector::__translate('Validade do cartão')); ?>
			<span class="required">*</span>
		</label> 
			<input 
				id="pgly-erede-card-expiry"
				name="pgly_erede_credit_expiry"
				class="input-text wc-credit-card-form-card-expiry"
				type="tel"
				autocomplete="off"
				placeholder="MM / AAAA"
				style="font-size: 1.5em; padding: 8px;"/>
	</p>
	<p class="form-row form-row-last">
		<label for="pgly-erede-card-cvv">
			<?php echo esc_html(CoreConnector::__translate('Código de segurança')); ?>
			<span class="required">*</span>
		</label> 
		<input 
			id="pgly-erede-card-cvv"
			name="pgly_erede_credit_cvv"
			class="input-text wc-credit-card-form-card-cvv" type="tel"
			autocomplete="off"
			placeholder="CVV"
			style="font-size: 1.5em; padding: 8px;"/>
    </p>
    <div class="clear"></div>
</fieldset>