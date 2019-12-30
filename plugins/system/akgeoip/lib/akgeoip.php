<?php
/**
 * @package   akgeoip
 * @copyright Copyright (c)2013-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

defined('_JEXEC') or die();

use GeoIp2\Database\Reader;

class AkeebaGeoipProvider
{
	/**
	 * The MaxMind GeoLite database reader
	 *
	 * @var    Reader
	 *
	 * @since  2.0
	 */
	private $reader = null;

	/**
	 * Records for IP addresses already looked up
	 *
	 * @var    array
	 *
	 * @since  2.0
	 */
	private $lookups = array();

	/**
	 * City records for IP addresses already looked up
	 *
	 * @var    array
	 *
	 * @since  2.0
	 */
	private $cityLookups = array();

	/** @var   bool  Do I have a database with city-level information? */

	/**
	 * Do I have a database with city-level information?
	 *
	 * @var    bool
	 *
	 * @since  2.0
	 */
	private $hasCity = false;

	/**
	 * Public constructor. Loads up the GeoLite2 database.
	 */
	public function __construct()
	{
		if (!function_exists('bcadd') || !function_exists('bcmul') || !function_exists('bcpow'))
		{
			require_once __DIR__ . '/fakebcmath.php';
		}

		// Default to a country-level database
		$filePath = __DIR__ . '/../db/GeoLite2-Country.mmdb';
		$this->hasCity = false;

		// If I have a city-level database prefer it
		$altFilePath = __DIR__ . '/../db/GeoLite2-City.mmdb';

		if (file_exists($altFilePath))
		{
			$filePath = $altFilePath;
			$this->hasCity = true;
		}

		try
		{
			$this->reader = new Reader($filePath);
		}
		// If anything goes wrong, MaxMind will raise an exception, resulting in a WSOD. Let's be sure to catch everything
		catch(\Exception $e)
		{
			$this->reader = null;
		}
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
		if ($this->hasCity)
		{
			return $this->getCityRecord($ip);
		}

		if (!array_key_exists($ip, $this->lookups))
		{
			try
			{
				$this->lookups[$ip] = null;

				if (!is_null($this->reader))
				{
					$this->lookups[$ip] = $this->reader->country($ip);
				}
			}
			catch (\GeoIp2\Exception\AddressNotFoundException $e)
			{
				$this->lookups[$ip] = false;
			}
			catch (\Exception $e)
			{
	            // GeoIp2 could throw several different types of exceptions. Let's be sure that we're going to catch them all
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

		if (is_null($record))
		{
			return false;
		}

		return $record->country->isoCode;
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

		if (is_null($record))
		{
			return false;
		}

		if (empty($locale))
		{
			return $record->country->name;
		}

		return $record->country->names[$locale];
	}

	/**
	 * Gets the continent ISO code from an IP address
	 *
	 * @param   string  $ip      The IP address to look up
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

		if (is_null($record))
		{
			return false;
		}

		return $record->continent->code;
	}

	/**
	 * Gets the continent name from an IP address
	 *
	 * @param   string  $ip      The IP address to look up
	 * @param   string  $locale  The locale of the continent name, e.g 'de' to return the country names in German. If not specified the English (US) names are returned.
	 *
	 * @return  mixed  A string with the country name if found, false if the IP address is not found, null if the db can't be loaded
	 */
	public function getContinentName($ip, $locale = null)
	{
		$record = $this->getCountryRecord($ip);

		if ($record === false)
		{
			return false;
		}

		if (is_null($record))
		{
			return false;
		}

		if (empty($locale))
		{
			return $record->continent;
		}

		return $record->continent->names[$locale];
	}

	/**
	 * Gets a raw city record from an IP address
	 *
	 * @param   string  $ip  The IP address to look up
	 *
	 * @return  mixed  A \GeoIp2\Model\City record if found, false if the IP address is not found, null if the db can't be loaded
	 */
	public function getCityRecord($ip)
	{
		if (!$this->hasCity)
		{
			return null;
		}

		$needsToLoad = !array_key_exists($ip, $this->cityLookups);

		if ($needsToLoad)
		{
			try
			{
				if (!is_null($this->reader))
				{
					$this->cityLookups[$ip] = $this->reader->city($ip);
				}
				else
				{
					$this->cityLookups[$ip] = null;
				}
			}
			catch (\GeoIp2\Exception\AddressNotFoundException $e)
			{
				$this->cityLookups[$ip] = false;
			}
			catch (\Exception $e)
			{
				// GeoIp2 could throw several different types of exceptions. Let's be sure that we're going to catch them all
				$this->cityLookups[$ip] = null;
			}
		}

		return $this->cityLookups[$ip];
	}

	/**
	 * Gets the continent ISO code from an IP address
	 *
	 * @param   string  $ip      The IP address to look up
	 *
	 * @return  mixed  A string with the country name if found, false if the IP address is not found, null if the db can't be loaded
	 */
	public function getCity($ip, $locale = null)
	{
		/** @var \GeoIp2\Record\City $record */
		$record = $this->getCityRecord($ip);

		if ($record === false)
		{
			return false;
		}

		if (is_null($record))
		{
			return false;
		}

		return $record->city->name;
	}


	/**
	 * Refreshes the Joomla! update sites for this extension as needed
	 *
	 * @return  void
	 */
	public function refreshUpdateSite()
	{
		JLoader::import('joomla.application.plugin.helper');

		// Create the update site definition we want to store to the database
		$update_site = array(
			'name'		=> 'Akeeba GeoIP Provider Plugin',
			'type'		=> 'extension',
			'location'	=> 'http://cdn.akeebabackup.com/updates/akgeoip.xml',
			'enabled'	=> 1,
			'last_check_timestamp'	=> 0,
		);

		$db = JFactory::getDbo();

		// Get the extension ID to ourselves
		$query = $db->getQuery(true)
			->select($db->qn('extension_id'))
			->from($db->qn('#__extensions'))
			->where($db->qn('type') . ' = ' . $db->q('plugin'))
			->where($db->qn('element') . ' = ' . $db->q('akgeoip'))
			->where($db->qn('folder') . ' = ' . $db->q('system'));
		$db->setQuery($query);

		$extension_id = $db->loadResult();

		if (empty($extension_id))
		{
			return;
		}

		// Get the update sites for our extension
		$query = $db->getQuery(true)
			->select($db->qn('update_site_id'))
			->from($db->qn('#__update_sites_extensions'))
			->where($db->qn('extension_id') . ' = ' . $db->q($extension_id));
		$db->setQuery($query);

		$updateSiteIDs = $db->loadColumn(0);

		if (!count($updateSiteIDs))
		{
			// No update sites defined. Create a new one.
			$newSite = (object)$update_site;
			$db->insertObject('#__update_sites', $newSite);

			$id = $db->insertid();

			$updateSiteExtension = (object)array(
				'update_site_id'	=> $id,
				'extension_id'		=> $extension_id,
			);
			$db->insertObject('#__update_sites_extensions', $updateSiteExtension);
		}

		// Loop through all update sites
		foreach ($updateSiteIDs as $id)
		{
			$query = $db->getQuery(true)
			            ->select('*')
			            ->from($db->qn('#__update_sites'))
			            ->where($db->qn('update_site_id') . ' = ' . $db->q($id));
			$db->setQuery($query);
			$aSite = $db->loadObject();

			// Does the name and location match?
			if (($aSite->name == $update_site['name']) && ($aSite->location == $update_site['location']))
			{
				continue;
			}

			$update_site['update_site_id'] = $id;
			$newSite = (object)$update_site;
			$db->updateObject('#__update_sites', $newSite, 'update_site_id', true);
		}
	}
}
