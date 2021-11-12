<?php

use Piggly\WooERedeGateway\CoreConnector;

if ( ! defined( 'ABSPATH' ) ) { exit; }

$plugin = CoreConnector::plugin();

$path  = WP_CONTENT_DIR.'/integracao-erede-por-piggly/';
$files = [];
$files = glob($path.'*.log');

usort($files, function ( $x, $y ) {
	return filemtime($x) < filemtime($y);
});

$files = array_map(function ($item) {
	return [
		'path' => $item,
		'basename' => basename($item),
		'label' => sprintf('%s => (%s)', basename($item), (new DateTime('@'.filemtime($item)))->setTimezone(wp_timezone())->format('d/m/Y H:i:s'))
	];
}, $files);

$curr_file = $files[0];

if ( !empty(($get_file = \filter_input( INPUT_POST, 'log_path', FILTER_SANITIZE_STRING ))) )
{
	foreach ( $files as $file )
	{
		if ( $file['basename'] === $get_file )
		{ $curr_file = $file; }
	}
}

?>

<svg viewBox="0 0 32 32" style="width: 32px; height: 32px" xmlns="http://www.w3.org/2000/svg"><path d="M6.57,20.59H10a.51.51,0,0,0,.5-.5V17.64a.5.5,0,0,0-.5-.5H6.57a.5.5,0,0,0-.5.5v2.45a.51.51,0,0,0,.5.5Z"/><path d="M6.57,15.68H10a.5.5,0,0,0,0-1H6.57a.5.5,0,0,0,0,1Z"/><path d="M8.29,22.05H6.57a.5.5,0,0,0-.5.5.51.51,0,0,0,.5.5H8.29a.51.51,0,0,0,.5-.5A.5.5,0,0,0,8.29,22.05Z"/><path d="M12,22.05H10.29a.5.5,0,0,0-.5.5.5.5,0,0,0,.5.5H12a.5.5,0,0,0,.5-.5A.5.5,0,0,0,12,22.05Z"/><path d="M15.73,22H14a.5.5,0,0,0-.5.5.51.51,0,0,0,.5.5h1.73a.5.5,0,0,0,.5-.5A.51.51,0,0,0,15.73,22Z"/><path d="M19.43,22H17.71a.49.49,0,0,0-.49.5.5.5,0,0,0,.5.5h1.71a.5.5,0,0,0,.5-.5A.5.5,0,0,0,19.43,22Z"/><path d="M28,6.5H9.14a.5.5,0,0,0-.5.5v5.23H4a.5.5,0,0,0-.5.5V25a.5.5,0,0,0,.5.5H23.71a.49.49,0,0,0,.36-.15.5.5,0,0,0,.14-.35V20.59H28a.5.5,0,0,0,.5-.5V7A.5.5,0,0,0,28,6.5Zm-4.79,18H4.5V13.23H23.21V24.5Zm4.29-4.91H24.21V12.73a.5.5,0,0,0-.5-.5H9.64l0-1H27.51ZM9.66,8.5l0-1H27.5V8.63Z"/></svg>
<h1 class="pgly-wps--title pgly-wps-is-4">
	e-Rede por Piggly
</h1>

<script>
	document.addEventListener('DOMContentLoaded', () => {
		new PglyWpsAsync({
			container: '#pgly-wps-plugin',
			responseContainer: 'pgly-wps--response',
			url: wcPigglyPix.ajax_url,
			x_security: wcPigglyPix.x_security,
			messages: {
				request_error: 'Ocorreu um erro ao processar a requisição',
				invalid_fields: 'Campos inválidos'
			},
			debug: <?php echo CoreConnector::debugger()->isDebugging() ? 'true' : 'false'; ?>
		});
	});
</script>

<div class="pgly-wps--row">
	<div class="pgly-wps--column">
		<button 
			class="pgly-wps--button pgly-async--behaviour pgly-wps-is-warning"
			data-action="pgly_erede_gateway_admin_clean_logs"
			>
			Limpar Logs
			<svg 
				class="pgly-wps--spinner pgly-wps-is-white"
				viewBox="0 0 50 50">
				<circle class="path" cx="25" cy="25" r="20" fill="none" stroke-width="5"></circle>
			</svg>
		</button>

		<div class="pgly-wps--response" id="pgly-wps--response">
		</div>
	</div>
</div>

<form action="<?php echo esc_html(admin_url('admin.php?page='.$plugin->getDomain().'-logs')); ?>" method="POST">
	<div class="pgly-wps--row">
		<div class="pgly-wps--column">
			<div class="pgly-wps--field">
				<label class="pgly-wps--label" for="log_path">Logs disponíveis</label>
				<select
					name="log_path"
					id="log_path">
					<?php
					foreach ( $files as $file )
					{ printf('<option value="%s" %s>%s</option>', $file['basename'], $curr_file['basename'] === $file['basename'] ? 'selected="selected"' : '', $file['label']); }
					?>
				</select>
			</div>
		</div>
	</div>
	<div class="pgly-wps--row">
		<div class="pgly-wps--column">
			<button 
				class="pgly-wps--button pgly-wps-is-primary"
				type="submit"
				>
				Abrir Log
			</button>
		</div>
	</div>
</form>

<div class="pgly-wps--row">
	<div class="pgly-wps--column">
		<h3 class="pgly-wps--title"><?php echo esc_html($curr_file['label']); ?>)</h3>

		<div class="pgly-wps--logger">
			<pre><?php readfile($curr_file['path']); ?></pre>
		</div>
	</div>
</div>