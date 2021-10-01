<?php
use Piggly\WooERedeGateway\CoreConnector;
?>
<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<a
	href="<?php echo esc_url(admin_url( 'admin.php?page='.CoreConnector::domain() )); ?>"
	class="button-primary">
	Ir para Configurações Avançadas
</a>

<script>
	(function () { window.location.href = "<?php echo esc_url(admin_url( 'admin.php?page='.CoreConnector::domain() )); ?>"; })();
</script>

<style>p.submit { display: none !important; }</style> 