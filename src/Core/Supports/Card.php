<?php
namespace Piggly\WooERedeGateway\Core\Supports;

use Exception;
use Piggly\WooERedeGateway\CoreConnector;

/**
 * Parses all card data.
 * 
 * @package \Piggly\WooERedeGateway
 * @subpackage \Piggly\WooERedeGateway\Core\Supports
 * @version 1.0.0
 * @since 1.0.0
 * @category Supports
 * @author Caique Araujo <caique@piggly.com.br>
 * @author Piggly Lab <dev@piggly.com.br>
 * @license GPLv3 or later <key>
 * @copyright 2021 Piggly Lab <dev@piggly.com.br>
 */
class Card
{
	/**
	 * Validate a card number.
	 * 
	 * @param string $cardNumber
	 * @since 1.0.0
	 * @return bool
	 * @throws Exception When invalid credit card number.
	 */
	public static function validate_number ( string $cardNumber ) : bool
	{
		$checksum = '';

		foreach ( str_split( strrev( preg_replace( '/[^\d]/', '', $cardNumber ) ) ) as $i => $d ) 
		{ $checksum .= $i % 2 !== 0 ? $d * 2 : $d; }

		if ( array_sum( str_split( $checksum ) ) % 10 !== 0 ) 
		{ 
			CoreConnector::debugger()->error('Ocorreu um erro ao validar o número do cartão.');
			throw new Exception( CoreConnector::__translate('Por favor, informe um número válido para o cartão.') ); 
		}

		return true;
	}

	/**
	 * Format anything in $expiration to XX/XXXX.
	 * Where X means digit.
	 * 
	 * e.g.
	 * 01/22 -> 01/2022
	 * 01 / 22 -> 01/2022
	 * 01/2022 -> 01/2022
	 * 01 / 2022 -> 01/2022
	 * 
	 * @param string $expiration
	 * @since 1.0.0
	 * @return string
	 */
	public static function fix_exp_date ( string $expiration ) : string
	{
		if ( preg_match( '/(\d{2})\s*\/\s*(\d{2})$/', $expiration, $matches ) ) 
		{ $expiration = sprintf( '%d/%04d', $matches[1], 2000 + $matches[2] ); }

		$expiration = preg_replace( '/\s*\/\s*/', '/', $expiration );
		return $expiration;
	}

	/**
	 * Validate if current $installments is allowed and
	 * follow the plugin settings.
	 * 
	 * @param int $installments Desired installments.
	 * @param float $total Order total.
	 * @param int $minValue Minimum value to each installment.
	 * @param int $maxParcels Maximum installments allowed.
	 * @since 1.0.0
	 * @return bool
	 * @throws Exception When $installments not allowed.
	 */
	public static function validate_installments (
		?int $installments,
		float $total,
		int $minValue,
		int $maxParcels
	) : bool
	{
		if ( empty($installments) )
		{ $installments = 1; }

		// Allowed
		if ( $installments === 1 )
		{ return true; }

		// Not allowed
		if ( $installments > $maxParcels || ( ( $minValue != 0 ) 
				&& ( ( $total / $installments ) < $minValue ) ) ) 
		{ 
			CoreConnector::debugger()->error('As parcelas solicitadas excederam o número máximo de parcelas ou o valor mínimo por cada parcela.');
			throw new Exception( \sprintf(CoreConnector::__translate('%s parcelas não permitadas.'), $installments) ); 
		}

		return true;
	}

	/**
	 * Try to get card field and return an array including:
	 * 
	 * card_number => (string) Card number with only numbers.
	 * card_expiration_month => (string) Two digit month number.
	 * card_expiration_year => (string) Four digit year number.
	 * card_cvv => (string) Card CVV.
	 * card_holder => (string) Card holder name.
	 * 
	 * @param array $fields
	 * @since 1.0.0
	 * @return array
	 * @throws Exception When some field is invalid.
	 */
	public static function parse_fields ( array $fields ) : array
	{
		$required = [
			'number' => CoreConnector::__translate('Por favor, informe o número do cartão.'),
			'holder_name' => CoreConnector::__translate('Por favor, informe o nome do titular do cartão.'),
			'expiry' => CoreConnector::__translate('Por favor, informe a data de expiração do cartão.'),
			'cvv' => CoreConnector::__translate('Por favor, informe o código de segurança do cartão.')
		];
		
		foreach ( $required as $key => $message )
		{
			if ( empty($fields[$key]) )
			{ 
				CoreConnector::debugger()->error(\sprintf('O campo `%s` não foi preenchido.', $key));
				throw new Exception($message); 
			}
		}

		// Do fixes to data
		$number      = preg_replace( '/[^\d]/', '', $fields['number'] );
		$holder_name = preg_replace( '/[^a-zA-Z\s]/', '', $fields['holder_name'] );
		$cvv         = preg_replace( '/[^\d]/', '', $fields['cvv'] );
		$expiration  = strtotime( preg_replace( '/(\d{2})\s*\/\s*(\d{4})/', '$2-$1-01', self::fix_exp_date($fields['expiry']) ) );
	
		// Tries to validate card number
		self::validate_number($number);

		// Validate holder name
		if ( $holder_name !== $fields['holder_name'] )
		{ 
			CoreConnector::debugger()->error('O nome do titular foi preenchido incorretamente.');
			throw new Exception(CoreConnector::__translate('O nome do titular do cartão só pode conter letras e espaços.')); 
		}

		// Validate cvv
		if ( $cvv !== $fields['cvv'] || (strlen($cvv) < 1 || strlen($cvv) > 4) )
		{ 
			CoreConnector::debugger()->error('O código de segurança foi preenchido incorretamente.');
			throw new Exception(CoreConnector::__translate('O código de segurança deve conter apenas números.')); 
		}

		// Validate expiration date
		if ( $expiration < strtotime( date( 'Y-m' ) . '-01' ) )
		{ 
			CoreConnector::debugger()->error('A data de expiração foi preenchida incorretamente.');
			throw new Exception(CoreConnector::__translate('A data de expiração deve ser futura.')); 
		}

		$expiration = explode('/', $fields['expiry']);

		return [
			'card_number'           => $number,
			'card_expiration_month' => trim($expiration[0]),
			'card_expiration_year'  => trim($expiration[1]),
			'card_cvv'              => $cvv,
			'card_holder'           => $holder_name
		];
	}
}