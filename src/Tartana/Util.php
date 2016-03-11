<?php
namespace Tartana;

final class Util
{

	/**
	 * Returns if there is a process with the given id running.
	 *
	 * @param integer $pid
	 * @return boolean
	 */
	public static function isPidRunning ($pid)
	{
		return posix_getpgid((int) $pid) > 0;
	}

	/**
	 * Convertes the given size to a human readable format.
	 *
	 * @param integer $size
	 * @param array $strings
	 * @return string
	 */
	public static function readableSize ($size, $strings = ['B','kB','MB','GB','TB','PB','EB','ZB','YB'])
	{
		for ($i = 0; ($size / 1024) > 0.9; $i ++, $size /= 1024)
		{
		}
		return round($size, 2) . (isset($strings[$i]) ? ' ' . $strings[$i] : '');
	}

	/**
	 * Returns the real path for the given path.
	 * If the path is relative and the file exists, the real fill path is
	 * returned.
	 *
	 * @param string $path
	 * @return NULL|string
	 */
	public static function realPath ($path)
	{
		if (! $path)
		{
			return null;
		}

		// If it is a relative path add the root path to it
		if ($path[0] !== DIRECTORY_SEPARATOR && preg_match('~\A[A-Z]:(?![^/\\\\])~i', $path) < 1)
		{
			$path = TARTANA_PATH_ROOT . '/' . $path;
		}

		if (! file_exists($path))
		{
			return null;
		}

		return $path;
	}

	/**
	 * Returns a deep cloned array of the given objects.
	 *
	 * @param array $objects
	 * @return array
	 */
	public static function cloneObjects (array $objects)
	{
		return array_map(function  ($obj) {
			return clone $obj;
		}, $objects);
	}

	/**
	 * Checks if the haystack starts with the needle.
	 *
	 * @param string $haystack
	 * @param string $needle
	 * @return boolean
	 */
	public static function startsWith ($haystack, $needle)
	{
		return (substr($haystack, 0, strlen($needle)) === $needle);
	}

	/**
	 * Checks if the haystack ends with the needle.
	 *
	 * @param string $haystack
	 * @param string $needle
	 * @return boolean
	 */
	public static function endsWith ($haystack, $needle)
	{
		$length = strlen($needle);
		if ($length == 0)
		{
			return true;
		}

		return (substr($haystack, - $length) === $needle);
	}

	/**
	 * Shortens the given string by replacing the middle part with three dots.
	 *
	 * @param string $string
	 * @param integer $length
	 *
	 * @return string
	 */
	public static function shorten ($string, $length = 20)
	{
		if (strlen($string) > $length)
		{
			$characters = floor($length / 2);
			return substr($string, 0, $characters) . '...' . substr($string, - 1 * $characters);
		}
		return $string;
	}
}