<?php
namespace App\Xdag\Block\Line;

class LineParser
{
	const TRANSACTION_REGEX = '/\s*(fee|input|output): ([a-zA-Z0-9\/+]{32})\s*([0-9]*\.[0-9]*)/i';
	const ADDRESS_REGEX = '/\s*(fee|input|output|earning): ([a-zA-Z0-9\/+]{32})\s*([0-9]*\.[0-9]*)\s*(.*)/i';

	public function blockExists($line)
	{
		return stripos($line, 'Block is not found') === false;
	}

	public function shouldProceedToTransactions($line)
	{
		return stripos($line, 'Block as transaction: details') !== false;
	}

	public function shouldProcceedToAddresses($line)
	{
		return stripos($line, 'block as address: details') !== false;
	}

	public function parseProperty($line)
	{
		if (preg_match('/\s*(.*): (.*)/', $line, $matches)) {
			$key   = strtolower(trim($matches[1]));
			$value = strtolower(trim($matches[2]));

			$properties = [];

			if ($key == 'balance') {
				$properties['balance_address'] = trim(current($balance = explode(' ', $matches[2])));
				$value						   = end($balance);
			}

			$properties[ snake_case($key) ] = $value;

			return $properties;
		}

		return null;
	}

	public function isValidTransaction($line)
	{
		return ! ! preg_match(self::TRANSACTION_REGEX, $line);
	}

	public function parseTransaction($line)
	{
		if (preg_match(self::TRANSACTION_REGEX, $line, $matches)) {
			list(, $direction, $address, $amount) = $matches;

			return [
				'direction' => strtolower(trim($direction)),
				'address'	=> trim($address),
				'amount'	=> strtolower(trim($amount)),
			];
		}

		return null;
	}

	public function isValidAddress($line)
	{
		return ! ! preg_match(self::ADDRESS_REGEX, $line);
	}

	public function parseAddress($line)
	{
		if (preg_match(self::ADDRESS_REGEX, $line, $matches)) {
			list(, $direction, $address, $amount, $time) = $matches;

			return [
				'direction' => strtolower(trim($direction)),
				'address'	=> trim($address),
				'amount'	=> strtolower(trim($amount)),
				'time'		=> strtolower(trim($time)),
			];
		}
	}
}
