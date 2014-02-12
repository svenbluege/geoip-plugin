<?php
/**
 * @package		akgeoip
 * @copyright	Copyright (c)2014 Nicholas K. Dionysopoulos
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
		if (!function_exists('bcadd') || !function_exists('bcmul') || !function_exists('bcpow'))
		{
			require_once __DIR__ . '/fakebcmath.php';
		}

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
            // GeoIp2 could throw several different types of exceptions. Let's be sure that we're going to catch them all
            catch (Exception $e)
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
		elseif (is_null($record))
		{
			return false;
		}
		else
		{
			return $record->continent->code;
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
	public function getContinentName($ip, $locale = null)
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

	/**
	 * Downloads and installs a fresh copy of the GeoLite2 Country database
	 *
	 * @return  mixed  True on success, error string on failure
	 */
	public function updateDatabase()
	{
		$datFile = JPATH_PLUGINS . '/system/akgeoip/db/GeoLite2-Country.mmdb';

		// Sanity check
		if(!function_exists('gzinflate')) {
			return JText::_('PLG_SYSTEM_AKGEOIP_ERR_NOGZSUPPORT');
		}

		// Download the latest MaxMind GeoCountry Lite database
		$url = 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.mmdb.gz';
		$http = JHttpFactory::getHttp();
		$response = $http->get($url);

		try
		{
			$compressed = $response->body;
		}
		catch (Exception $e)
		{
			return $e->getMessage();
		}

		if (empty($compressed))
		{
			return JText::_('PLG_SYSTEM_AKGEOIP_ERR_EMPTYDOWNLOAD');
		}

		// Write the downloaded file to a temporary location
		$jreg = JFactory::getConfig();
		$tmpdir = $jreg->get('tmp_path');

		JLoader::import('joomla.filesystem.folder');

		// Make sure the user doesn't use the system-wide tmp directory. You know, the one that's
		// being erased periodically and will cause a real mess while installing extensions (Grrr!)
		if(realpath($tmpdir) == '/tmp')
		{
			// Someone inform the user that what he's doing is insecure and stupid, please. In the
			// meantime, I will fix what is broken.
			$tmpdir = JPATH_SITE . '/tmp';
		}
		// Make sure that folder exists (users do stupid things too often; you'd be surprised)
		elseif(!JFolder::exists($tmpdir))
		{
			// Darn it, user! WTF where you thinking? OK, let's use a directory I know it's there...
			$tmpdir = JPATH_SITE . '/tmp';
		}

		$target = $tmpdir.'/GeoLite2-Country.mmdb.gz';

		$ret = JFile::write($target, $compressed);

		if ($ret === false)
		{
			return JText::_('PLG_SYSTEM_AKGEOIP_ERR_WRITEFAILED');
		}

		unset($compressed);

		// Decompress the file
		$uncompressed = '';
		$zp = @gzopen($target, 'rb');
		if($zp !== false)
		{
			while(!gzeof($zp))
			{
				$uncompressed .= @gzread($zp, 102400);
			}

			@gzclose($zp);

			if (!@unlink($target))
			{
				JFile::delete($target);
			}
		}
		else
		{
			return JText::_('PLG_SYSTEM_AKGEOIP_ERR_CANTUNCOMPRESS');
		}

		// Remove old file
		JLoader::import('joomla.filesystem.file');

		if (JFile::exists($datFile))
		{
			if(!JFile::delete($datFile))
			{
				return JText::_('PLG_SYSTEM_AKGEOIP_ERR_CANTDELETEOLD');
			}
		}

		// Write the update file
		if (!JFile::write($datFile, $uncompressed))
		{
			return JText::_('PLG_SYSTEM_AKGEOIP_ERR_CANTWRITE');
		}

		return true;
	}
}