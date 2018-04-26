<?php
namespace App\Service;

use Symfony\Component\Cache\Simple\FilesystemCache;

class Xdag
{
	protected $socket_file;
	protected $cache;

	public function __construct($socket_file)
        {
		if(!extension_loaded('sockets'))
		{
			throw new \Exception('Sockets extension not loaded');
		}

		$this->socket_file = $socket_file;

		$this->cache = new FilesystemCache();
	}

	public static function isAddress($address)
	{
		return preg_match("/^[a-zA-Z0-9\/+]{32}$/", $address);
	}

	public static function isBlockHash($hash)
        {
                return preg_match("/^[a-f0-9]{64}$/", $hash);
        }

	public function isReady()
	{
		return preg_match("/Normal operation./i", $this->command('state'));
	}

	public function command($cmd)
	{
		$socket = socket_create(AF_UNIX, SOCK_STREAM, 0);

		if (!$socket || !socket_connect($socket, $this->socket_file)) {
			throw new \Exception('Error establishing a connection with the socket');
		}

		$command = "$cmd\0";
		socket_send($socket, $command, strlen($command), 0);

		$output = '';
		while($buffer = @socket_read($socket, 512)) {
				$output .= $buffer;
		}

		socket_close($socket);

		return $output;
	}

	// For huge outputs, to avoid out of memory errors
	public function commandStream($cmd)
	{
		$socket = socket_create(AF_UNIX, SOCK_STREAM, 0);

		if (!$socket || !socket_connect($socket, $this->socket_file)) {
			throw new \Exception('Error establishing a connection with the socket');
		}

		$command = "$cmd\0";
		socket_send($socket, $command, strlen($command), 0);

		while($line = @socket_read($socket, 512, PHP_NORMAL_READ)) {
			yield $line;
		}

		socket_close($socket);
	}

	public function getBalance($address)
	{
		if (!self::isAddress($address)) {
			throw new \Exception('Invalid address');
		}

		$command = "balance $address";

		if(!$this->cache->has(md5($command))) {
			$this->cache->set(md5($command), $this->command($command), 60);
		}

		$output = $this->cache->get(md5($command));

		return explode(' ', $output)[1];
	}

	public function getBlock($input)
	{
		if (!self::isAddress($input) && !self::isBlockHash($input)) {
			throw new \Exception('Invalid address or block hash');
		}

		$generator = $this->commandStream("block $input");
		$block = [];

		while(true) {
			$line = $generator->current();
			$generator->next();

			if(preg_match("/Block is not found/i", $line)) {
				throw new XdagBlockNotFoundException;
			} else if(preg_match("/Block as transaction: details/i", $line)) {
				// Jump to block as transaction parser
				break;
			} else if(preg_match("/\s*(.*): (.*)/i", $line, $matches)) {
				list($key, $value) = [$matches[1], $matches[2]];
				if ($key == 'balance') {
                                    $block['balance_address'] = current($balance = explode(' ', $matches[2]));
                                    $value = end($balance);
                                }
				$block[$key] = $value;
			}
		}

		$block['transaction'] = [];
		while(true) {
			$line = $generator->current();
			$generator->next();

			if(preg_match("/block as address: details/i", $line)) {
					// Jump to block as address parser
					break;
			} else if(preg_match("/\s*(fee|input|output|earning): ([a-zA-Z0-9\/+]{32})\s*([0-9]*\.[0-9]*)/i", $line, $matches)) {
				list(, $direction, $address, $amount) = $matches;
				$block['transaction'][] = ['direction' => $direction, 'address' => $address, 'amount' => $amount];
			}
		}

		$block['address'] = [];
		while(true) {
			if(!$generator->valid()) {
				break;
			}

			$line = $generator->current();
			$generator->next();

			if(preg_match("/\s*(fee|input|output|earning): ([a-zA-Z0-9\/+]{32})\s*([0-9]*\.[0-9]*)\s*(.*)/i", $line, $matches)) {
					list(, $direction, $address, $amount, $time) = $matches;
					$block['address'][] = ['direction' => $direction, 'address' => $address, 'amount' => $amount, 'time' => $time];
			}
		}

		return $block;
	}

	public function getStats()
	{
		$command = "stats";

		if(!$this->cache->has(md5($command))) {
			$this->cache->set(md5($command), $this->command($command), 300);
		}

		$output = $this->cache->get(md5($command));

		$stats = [];
		$lines = explode("\n", $output);

		foreach($lines as $line) {
			if(preg_match("/\s*(.*): (.*)/i", $line, $matches)) {
				list($key, $value) = [$matches[1], $matches[2]];
				$stats[$key] = $value;
			}
		}

		return $stats;
	}

	public function getBlocks($stats = '')
	{
		if(!is_array($stats)) {
			$stats = $this->getStats();
		}

		$arr = explode(' ', $stats['blocks']);
		return array_pop($arr);
	}

	public function getMainBlocks($stats = '')
	{
		if(!is_array($stats)) {
			$stats = $this->getStats();
		}

		$arr = explode(' ', $stats['main blocks']);
		return array_pop($arr);
	}

	public function getSupply($stats = '')
	{
		if(!is_array($stats)) {
			$stats = $this->getStats();
		}

		$arr = explode(' ', $stats['XDAG supply']);
		return (int) array_pop($arr);
	}

	public function getHashrate($stats = '')
	{
		if(!is_array($stats)) {
			$stats = $this->getStats();
		}

		$arr = explode(' ', $stats['4 hr hashrate MHs']);
		return array_pop($arr);
	}

	public function getDifficulty($stats = '')
	{
		if(!is_array($stats)) {
			$stats = $this->getStats();
		}

		$arr = explode(' ', $stats['chain difficulty']);
		return array_pop($arr);
	}

	public function getLastBlocks($number = 100)
	{
		$command = "lastblocks " . max(1, intval($number));

		if(!$this->cache->has(md5($command))) {
			$this->cache->set(md5($command), $this->command($command), 60);
		}

		$output = $this->cache->get(md5($command));

		return explode("\n", $output);
	}
}

class XdagBlockNotFoundException extends \Exception {}
