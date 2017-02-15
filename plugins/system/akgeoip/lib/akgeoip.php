<?php
/**
 * @package		akgeoip
 * @copyright	Copyright (c)2013-2017 Nicholas K. Dionysopoulos
 * @license		GNU General Public License version 3, or later
 *
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
	 * Downloads and installs a fresh copy of the GeoLite2 Country database
	 *
	 * @param   bool  $forceCity  Should I force the download of the city-level library?
	 *
	 * @return  mixed  True on success, error string on failure
	 */
	public function updateDatabase($forceCity = false)
	{
		$outputFile = JPATH_PLUGINS . '/system/akgeoip/db/GeoLite2-Country.mmdb';
		$deleteFile = JPATH_PLUGINS . '/system/akgeoip/db/GeoLite2-City.mmdb';

		if ($this->hasCity || $forceCity)
		{
			$temp = $outputFile;
			$outputFile = $deleteFile;
			$outputFile = $temp;
			unset($temp);
		}

		// Sanity check
		if (!function_exists('gzinflate'))
		{
			return JText::_('PLG_SYSTEM_AKGEOIP_ERR_NOGZSUPPORT');
		}

		// Try to download the package, if I get any exception I'll simply stop here and display the error
		try
		{
			$compressed = $this->downloadDatabase($forceCity);
		}
		catch (\Exception $e)
		{
			return $e->getMessage();
		}

		// Write the downloaded file to a temporary location
		$tmpdir = $this->getTempFolder();
		$target = $tmpdir . '/GeoLite2-Country.mmdb.gz';
		$ret = JFile::write($target, $compressed);

		if ($ret === false)
		{
			return JText::_('PLG_SYSTEM_AKGEOIP_ERR_WRITEFAILED');
		}

		unset($compressed);

		// Decompress the file
		$uncompressed = '';

		$zp = @gzopen($target, 'rb');

		if ($zp === false)
		{
			return JText::_('PLG_SYSTEM_AKGEOIP_ERR_CANTUNCOMPRESS');
		}

		if ($zp !== false)
		{
			while (!gzeof($zp))
			{
				$uncompressed .= @gzread($zp, 102400);
			}

			@gzclose($zp);

			if (!@unlink($target))
			{
				JFile::delete($target);
			}
		}

		// Double check if MaxMind can actually read and validate the downloaded database
		try
		{
			// The Reader want a file, so let me write again the file in the temp directory
			JFile::write($target, $uncompressed);
			$reader = new Reader($target);
		}
		catch (\Exception $e)
		{
			JFile::delete($target);

			// MaxMind could not validate the database, let's inform the user
			return JText::_('PLG_SYSTEM_AKGEOIP_ERR_INVALIDDB');
		}

		JFile::delete($target);

		// Check the size of the uncompressed data. When MaxMind goes into overload, we get crap data in return.
		if (strlen($uncompressed) < 1048576)
		{
			return JText::_('PLG_SYSTEM_AKGEOIP_ERR_MAXMINDRATELIMIT');
		}

		// Check the contents of the uncompressed data. When MaxMind goes into overload, we get crap data in return.
		if (stristr($uncompressed, 'Rate limited exceeded') !== false)
		{
			return JText::_('PLG_SYSTEM_AKGEOIP_ERR_MAXMINDRATELIMIT');
		}

		// Remove old file
		JLoader::import('joomla.filesystem.file');

		if (JFile::exists($outputFile))
		{
			if (!JFile::delete($outputFile))
			{
				return JText::_('PLG_SYSTEM_AKGEOIP_ERR_CANTDELETEOLD');
			}
		}

		// Write the update file
		if (!JFile::write($outputFile, $uncompressed))
		{
			return JText::_('PLG_SYSTEM_AKGEOIP_ERR_CANTWRITE');
		}

		// Finally, delete the other database file, if present
		if (JFile::exists($deleteFile))
		{
			if (!JFile::delete($deleteFile))
			{
				@unlink($deleteFile);
			}
		}

		// Piggyback on this method to also refresh the update site to this plugin
		$this->refreshUpdateSite();

		return true;
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

	/**
	 * Download the compressed database for the provider
	 *
	 * @param   bool  $forceCity  Should I force the download of the city-level database
	 *
	 * @return  string  The compressed data
	 *
	 * @since   1.0.1
	 *
	 * @throws  Exception
	 */
	private function downloadDatabase($forceCity = false)
	{
		// Download the latest MaxMind GeoCountry Lite database
		$url = 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.mmdb.gz';

		if ($this->hasCity || $forceCity)
		{
			$url = 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-City.mmdb.gz';
		}

		// I should have F0F installed, but let's double check in order to avoid errors
		if (file_exists(JPATH_LIBRARIES . '/f0f/include.php'))
		{
			require_once JPATH_LIBRARIES . '/f0f/include.php';
		}

		$http = JHttpFactory::getHttp();

		// Let's bubble up the exception, we will take care in the caller
		$response   = $http->get($url);
		$compressed = $response->body;

		// Generic check on valid HTTP code
		if ($response->code > 299)
		{
			throw new \Exception(JText::_('PLG_SYSTEM_AKGEOIP_ERR_MAXMIND_GENERIC'));
		}

		// An empty file indicates a problem with MaxMind's servers
		if (empty($compressed))
		{
			throw new \Exception(JText::_('PLG_SYSTEM_AKGEOIP_ERR_EMPTYDOWNLOAD'));
		}

		// Sometimes you get a rate limit exceeded
		if (stristr($compressed, 'Rate limited exceeded') !== false)
		{
			throw new \Exception(JText::_('PLG_SYSTEM_AKGEOIP_ERR_MAXMINDRATELIMIT'));
		}

		return $compressed;
	}

	/**
	 * Reads (and checks) the temp Joomla folder
	 *
	 * @return string
	 */
	private function getTempFolder()
	{
		$jRegistry = JFactory::getConfig();
		$tmpdir    = $jRegistry->get('tmp_path');

		JLoader::import('joomla.filesystem.folder');

		// Make sure the user doesn't use the system-wide tmp directory. You know, the one that's
		// being erased periodically and will cause a real mess while installing extensions (Grrr!)
		if (realpath($tmpdir) == '/tmp')
		{
			// Someone inform the user that what he's doing is insecure and stupid, please. In the
			// meantime, I will fix what is broken.
			$tmpdir = JPATH_SITE . '/tmp';
		}
		// Make sure that folder exists (users do stupid things too often; you'd be surprised)
		elseif (!JFolder::exists($tmpdir))
		{
			// Darn it, user! WTF where you thinking? OK, let's use a directory I know it's there...
			$tmpdir = JPATH_SITE . '/tmp';
		}

		return $tmpdir;
	}
}