<?php

/**
 * @package   FilterMagic
 * @copyright Copyright (c)2022-2023 Nicholas K. Dionysopoulos
 * @modified  Joomla 6 compatibility port
 * @license   GNU General Public License version 3, or later
 */

namespace Dionysopoulos\Plugin\System\FilterMagic\Util;

defined('_JEXEC') || die;

/**
 * Registers a plgSystemFilterMagic:// stream wrapper.
 *
 * Used to hot-patch PHP source in memory without writing to disk permanently.
 *
 * @since 1.0.0
 */
class Buffer
{
	/**
	 * In-memory buffer storage, keyed by stream name.
	 *
	 * @var    array<string, string|null>
	 * @since  1.0.0
	 */
	public static array $buffers = [];

	/**
	 * Cached result of wrapper registration capability check.
	 *
	 * @var    bool|null
	 * @since  1.0.0
	 */
	public static ?bool $canRegisterWrapper = null;

	/**
	 * Current stream position.
	 *
	 * @var    int
	 * @since  1.0.0
	 */
	public int $position = 0;

	/**
	 * Buffer name (derived from stream URL host + path).
	 *
	 * @var    string|null
	 * @since  1.0.0
	 */
	public ?string $name = null;

	/**
	 * Check whether the plgSystemFilterMagic:// stream wrapper can be registered.
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	public static function canRegisterWrapper(): bool
	{
		if (static::$canRegisterWrapper !== null) {
			return static::$canRegisterWrapper;
		}

		static::$canRegisterWrapper = false;

		if (!function_exists('stream_wrapper_register')) {
			return false;
		}

		// Detect Suhosin extension
		$hasSuhosin = false;

		if (function_exists('extension_loaded')) {
			$hasSuhosin = extension_loaded('suhosin');
		}

		if ($hasSuhosin === false) {
			$hasSuhosin = defined('SUHOSIN_PATCH') ? true : -1;
		}

		if ($hasSuhosin === -1 && function_exists('ini_get')) {
			$maxIdLength = ini_get('suhosin.session.max_id_length');
			$hasSuhosin  = ($maxIdLength !== false && $maxIdLength !== '') ? true : false;
		}

		// Can't detect Suhosin — bail out to prevent WSoD
		if ($hasSuhosin === -1) {
			return false;
		}

		// Suhosin present but ini_get unavailable — bail out
		if ($hasSuhosin === true && !function_exists('ini_get')) {
			return false;
		}

		// Suhosin present — check whitelist
		if ($hasSuhosin === true) {
			$whiteList = ini_get('suhosin.executor.include.whitelist');

			if (empty($whiteList)) {
				return false;
			}

			// J6/PHP8: arrow function invece di closure anonima
			$whiteList = array_map(fn(string $x) => trim($x), explode(',', $whiteList));

			if (!in_array('fof://', $whiteList, true)) {
				return false;
			}
		}

		static::$canRegisterWrapper = true;

		return true;
	}

	/**
	 * Open the stream.
	 *
	 * @param   string   $path         The stream URL
	 * @param   string   $mode         Mode used to open the file
	 * @param   int      $options      Stream API flags
	 * @param   string  &$opened_path  Full path of the resource
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
	{
		$url            = parse_url($path);
		$this->name     = ($url['host'] ?? '') . ($url['path'] ?? '');
		$this->position = 0;

		if (!isset(static::$buffers[$this->name])) {
			static::$buffers[$this->name] = null;
		}

		return true;
	}

	/**
	 * Set stream options (not supported).
	 *
	 * @param   int       $option  Option code
	 * @param   int|null  $arg1    First argument
	 * @param   int|null  $arg2    Second argument
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	public function stream_set_option(int $option, ?int $arg1 = null, ?int $arg2 = null): bool
	{
		return false;
	}

	/**
	 * Unlink (delete) a stream buffer.
	 *
	 * @param   string  $path  The stream URL
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function unlink(string $path): void
	{
		$url  = parse_url($path);
		$name = $url['host'] ?? '';

		if (isset(static::$buffers[$name])) {
			unset(static::$buffers[$name]);
		}
	}

	/**
	 * Return stream stat information.
	 *
	 * @return  array
	 * @since   1.0.0
	 */
	public function stream_stat(): array
	{
		// Null-safe strlen: buffer may be null before first write
		$size = strlen(static::$buffers[$this->name] ?? '');

		return [
			'dev'     => 0,
			'ino'     => 0,
			'mode'    => 0644,
			'nlink'   => 0,
			'uid'     => 0,
			'gid'     => 0,
			'rdev'    => 0,
			'size'    => $size,
			'atime'   => 0,
			'mtime'   => 0,
			'ctime'   => 0,
			'blksize' => -1,
			'blocks'  => -1,
		];
	}

	/**
	 * Read from the stream.
	 *
	 * @param   int  $count  Number of bytes to read
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	public function stream_read(int $count): string
	{
		$buffer         = static::$buffers[$this->name] ?? '';
		$ret            = substr($buffer, $this->position, $count);
		$this->position += strlen($ret);

		return $ret;
	}

	/**
	 * Write to the stream.
	 *
	 * @param   string  $data  Data to write
	 *
	 * @return  int  Number of bytes written
	 * @since   1.0.0
	 */
	public function stream_write(string $data): int
	{
		$buffer = static::$buffers[$this->name] ?? '';
		$left   = substr($buffer, 0, $this->position);
		$right  = substr($buffer, $this->position + strlen($data));

		static::$buffers[$this->name] = $left . $data . $right;
		$this->position               += strlen($data);

		return strlen($data);
	}

	/**
	 * Get the current stream position.
	 *
	 * @return  int
	 * @since   1.0.0
	 */
	public function stream_tell(): int
	{
		return $this->position;
	}

	/**
	 * Check if end of stream is reached.
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	public function stream_eof(): bool
	{
		return $this->position >= strlen(static::$buffers[$this->name] ?? '');
	}

	/**
	 * Seek to a position in the stream.
	 *
	 * @param   int  $offset  Byte offset
	 * @param   int  $whence  SEEK_SET, SEEK_CUR, or SEEK_END
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	public function stream_seek(int $offset, int $whence): bool
	{
		$length = strlen(static::$buffers[$this->name] ?? '');

		switch ($whence) {
			case SEEK_SET:
				if ($offset >= 0 && $offset <= $length) {
					$this->position = $offset;

					return true;
				}

				return false;

			case SEEK_CUR:
				if ($offset >= 0) {
					$this->position += $offset;

					return true;
				}

				return false;

			case SEEK_END:
				if ($length + $offset >= 0) {
					$this->position = $length + $offset;

					return true;
				}

				return false;

			default:
				return false;
		}
	}
}

if (Buffer::canRegisterWrapper()) {
	stream_wrapper_register('plgSystemFilterMagic', Buffer::class);
}
