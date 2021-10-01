<?php

use Piggly\WooERedeGateway\Core\Api\Payload;

if ( ! defined( 'ABSPATH' ) ) { exit; }

global $post;

$order = new WC_Order( $post->ID );
$payload = Payload::fill($order);
?>

<div id="pgly-erede-por-piggly" class="pgly-wps--settings" style="padding: 10px;">
<?php if ( empty($payload) ) : ?>
	<h3 style="text-align: center" class="pgly-wps--title pgly-wps-is-7">Payload Indisponível</h3>
	<div class="pgly-wps--notification pgly-wps-is-warning">
		Nenhum payload de pagamento está associado ao pedido.
	</div>
<?php else: ?>
	<h3 style="text-align: center" class="pgly-wps--title pgly-wps-is-7">
		<?php echo esc_html($payload->get('kind') === 'credit' ? 'Operação de Crédito' : 'Operação de Débito'); ?>
	</h3>
	
	<?php if ( $payload->get('kind') == 'credit' && ($payload->isAuthorized() || $payload->isSubmitted()) ) : ?>
		<button 
			class="pgly-wps--button pgly-async--behaviour pgly-wps-is-primary pgly-wps-is-compact"
			data-action="pgly_erede_gateway_admin_capture"
			data-response-container="pgly-erede-gateway-capture"
			data-refresh="true"
			data-oid="<?php echo esc_html($order->get_id()); ?>"
			type="button"
			>
			Capturar Pagamento
			<svg 
				class="pgly-wps--spinner pgly-wps-is-primary"
				viewBox="0 0 50 50">
				<circle class="path" cx="25" cy="25" r="20" fill="none" stroke-width="5"></circle>
			</svg>
		</button>
		
		<div class="pgly-wps--response" id="pgly-erede-gateway-capture"></div>
	<?php endif; ?>
	
	<?php if ( $payload->isAuthorized() || $payload->isCaptured() ) : ?>
		<button 
			class="pgly-wps--button pgly-async--behaviour pgly-wps-is-primary pgly-wps-is-compact"
			data-action="pgly_erede_gateway_admin_refund"
			data-response-container="pgly-erede-gateway-refund"
			data-refresh="true"
			data-oid="<?php echo esc_html($order->get_id()); ?>"
			type="button"
			>
			Reembolso Total
			<svg 
				class="pgly-wps--spinner pgly-wps-is-primary"
				viewBox="0 0 50 50">
				<circle class="path" cx="25" cy="25" r="20" fill="none" stroke-width="5"></circle>
			</svg>
		</button>
		
		<div class="pgly-wps--response" id="pgly-erede-gateway-refund"></div>
	<?php endif; ?>

	<div class="pgly-wps--explorer pgly-wps-is-compact">
		<strong>Ambiente</strong>
		<span><?php echo esc_html($payload->get('environment', 'test') === 'test' ? 'Teste' : 'Produção'); ?></span>
	</div>

	<div class="pgly-wps--explorer pgly-wps-is-compact">
		<strong>Status</strong>
		<div style="margin-top: 4px" class="pgly-wps--badge pgly-wps-is-<?php echo esc_html($payload->getStatusColor()); ?>">
			<?php echo esc_html($payload->getStatusLabel()); ?>
		</div>
	</div>

	<div class="pgly-wps--explorer pgly-wps-is-compact">
		<strong>ID da Transação</strong>
		<span><?php echo esc_html($payload->get('tid', 'Indisponível')); ?></span>
	</div>

	<div class="pgly-wps--explorer pgly-wps-is-compact">
		<strong>Bandeira do Cartão</strong>
		<span><?php echo esc_html($payload->get('brand_name', 'Indisponível')); ?></span>
	</div>

	<?php if ( !empty($payload->get('last4', null)) ) : ?>
	<div class="pgly-wps--explorer pgly-wps-is-compact">
		<strong>Número do Cartão</strong>
		<span>&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; <?php echo esc_html($payload->get('last4')); ?></span>
	</div>
	<?php endif; ?>

	<div class="pgly-wps--explorer pgly-wps-is-compact">
		<strong>Nome do Titular</strong>
		<span><?php echo esc_html($payload->get('holderName', 'Indisponível')); ?></span>
	</div>

	<div class="pgly-wps--explorer pgly-wps-is-compact">
		<strong>Data de Expiração</strong>
		<span><?php echo esc_html($payload->get('expiration', 'Indisponível')); ?></span>
	</div>

	<?php if ( !empty($payload->get('installments', null)) ) : ?>
	<div class="pgly-wps--explorer pgly-wps-is-compact">
		<strong>Parcelamento</strong>
		<span><?php echo esc_html($payload->get('installments')); ?>x</span>
	</div>
	<?php endif; ?>

	<div class="pgly-wps--explorer pgly-wps-is-compact">
		<strong>Código de Retorno</strong>
		<span><?php echo esc_html($payload->get('return_code', 'Indisponível')); ?></span>
	</div>
	
	<?php if ( !empty($payload->get('rid', null)) ) : ?>
	<div class="pgly-wps--explorer pgly-wps-is-compact">
		<strong>ID do Reembolso</strong>
		<span><?php echo esc_html($payload->get('rid')); ?></span>
	</div>
	<?php endif; ?>
	
	<?php if ( !empty($payload->get('cid', null)) ) : ?>
	<div class="pgly-wps--explorer pgly-wps-is-compact">
		<strong>ID do Cancelamento</strong>
		<span><?php echo esc_html($payload->get('cid')); ?></span>
	</div>
	<?php endif; ?>
	
	<?php if ( !empty($payload->get('nsu', null)) ) : ?>
	<div class="pgly-wps--explorer pgly-wps-is-compact">
		<strong>NSU</strong>
		<span><?php echo esc_html($payload->get('nsu')); ?></span>
	</div>
	<?php endif; ?>
	
	<?php if ( !empty($payload->get('auth_code', null)) ) : ?>
	<div class="pgly-wps--explorer pgly-wps-is-compact">
		<strong>Código de Autorização</strong>
		<span><?php echo esc_html($payload->get('auth_code')); ?></span>
	</div>
	<?php endif; ?>
	
	<?php if ( !empty($payload->get('auth_status', null)) ) : ?>
	<div class="pgly-wps--explorer pgly-wps-is-compact">
		<strong>Status de Autorização</strong>
		<span><?php echo esc_html($payload->get('auth_status')); ?></span>
	</div>
	<?php endif; ?>
	<script>
		document.addEventListener('DOMContentLoaded', () => {
			new PglyWpsAsync({
				container: '#pgly-erede-por-piggly',
				responseContainer: 'pgly-erede-por-piggly--response',
				url: window.eRedeSettings.ajax_url,
				x_security: window.eRedeSettings.x_security,
				messages: {
					request_error: 'Ocorreu um erro ao processar a requisição',
					invalid_fields: 'Campos inválidos'
				},
				debug: true
			});
		});
	</script>
<?php endif; ?>
</div>