<?php
/**
 * @package		akgeoip
 * @copyright	Copyright (c)2013 Nicholas K. Dionysopoulos
 * @license		GNU General Public License version 3, or later
 *
 */

defined('_JEXEC') or die();

use GeoIp2\Database\Reader;

class AkeebaGeoipProvider
{
	/** @var	GeoIp2\Database\Reader	The MaxMind GeoLite database reader */
	private $reader = null;

	/** @var	array	Records for IP addresses already looked up */
	private $lookups = array();

	/**
	 * Public constructor. Loads up the GeoLite2 database.
	 */
	public function __construct()
	{
		$filePath = __DIR__ . '/../db/GeoLite2-Country.mmdb';

		$this->reader = new Reader($filePath);
	}

	/**
	 * Gets a raw country record from an IP address
	 *
	 * @param   string  $ip  The IP address to look up
	 *
	 * @return  mixed  A \GeoIp2\Model\Country record if found, false if the IP address is not found, null if the db can't be loaded
	 */
	public function getCountryRecord($ip)
	{
		if (!array_key_exists($ip, $this->lookups))
		{
			try
			{
				$this->lookups[$ip] = $this->reader->country($ip);
			}
			catch (\GeoIp2\Exception\AddressNotFoundException $e)
			{
				$this->lookups[$ip] = false;
			}
			catch (\MaxMind\Db\Reader\InvalidDatabaseException $e)
			{
				$this->lookups[$ip] = null;
			}
		}

		return $this->lookups[$ip];
	}

	/**
	 * Gets the ISO country code from an IP address
	 *
	 * @param   string  $ip  The IP address to look up
	 *
	 * @return  mixed  A string with the country ISO code if found, false if the IP address is not found, null if the db can't be loaded
	 */
	public function getCountryCode($ip)
	{
		$record = $this->getCountryRecord($ip);

		if ($record === false)
		{
			return false;
		}
		elseif (is_null($record))
		{
			return false;
		}
		else
		{
			return $record->country->isoCode;
		}
	}

	/**
	 * Gets the country name from an IP address
	 *
	 * @param   string  $ip      The IP address to look up
	 * @param   string  $locale  The locale of the country name, e.g 'de' to return the country names in German. If not specified the English (US) names are returned.
	 *
	 * @return  mixed  A string with the country name if found, false if the IP address is not found, null if the db can't be loaded
	 */
	public function getCountryName($ip, $locale = null)
	{
		$record = $this->getCountryRecord($ip);

		if ($record === false)
		{
			return false;
		}
		elseif (is_null($record))
		{
			return false;
		}
		else
		{
			if (empty($locale))
			{
				return $record->country->name;
			}
			else
			{
				return $record->country->names[$locale];
			}
		}
	}

	/**
	 * Gets the continent name from an IP address
	 *
	 * @param   string  $ip      The IP address to look up
	 * @param   string  $locale  The locale of the continent name, e.g 'de' to return the country names in German. If not specified the English (US) names are returned.
	 *
	 * @return  mixed  A string with the country name if found, false if the IP address is not found, null if the db can't be loaded
	 */
	public function getContinent($ip, $locale = null)
	{
		$record = $this->getCountryRecord($ip);

		if ($record === false)
		{
			return false;
		}
		elseif (is_null($record))
		{
			return false;
		}
		else
		{
			if (empty($locale))
			{
				return $record->continent;
			}
			else
			{
				return $record->continent->names[$locale];
			}
		}
	}
}