# Akeeba GeoIP provider plugin

This plugin provides country-level geographic IP services using MaxMind's GeoLite2 database.

> This product includes GeoLite2 data created by MaxMind, available from
[http://www.maxmind.com](http://www.maxmind.com).

# Prerequisites

In order to build the installation packages of this plugin you need to have
the following tools:

- A command line environment. bash under Linux / Mac OS X works best. On Windows
  you will need to run most tools using an elevated privileges (administrator)
  command prompt.

- The PHP CLI binary in your path

- Command line Git binaries(*)

- PEAR and Phing installed, with the Net_FTP and VersionControl_SVN PEAR
  packages installed

You will also need the following path structure on your system

- **maxmindprovider**	This repository, a.k.a. MAIN directory
- **buildfiles**		[Akeeba Build Tools](https://github.com/akeeba/buildfiles)
- **translations**      [Akeeba Translations](https://github.com/akeeba/translations)


You will need to use the exact folder names specified here.

# Initialising the repository

All of the following commands are to be run from the MAIN directory. Lines
starting with $ indicate a Mac OS X / Linux / other *NIX system commands. Lines
starting without this character indicate Windows commands. The starting character ($) MUST NOT be typed!

1. You will first need to do the initial link with Akeeba Build Tools, running
   the following command (Mac OS X, Linux, other *NIX systems):

		$ php ../buildfiles/tools/link.php `pwd`

   or, on Windows:

		php ../buildfiles/tools/link.php %CD%

2. After the initial linking takes place, go inside the build directory:

		$ cd build

   and run the link phing task:

		$ phing link

# Useful Phing tasks

All of the following commands are to be run from the MAIN directory. Lines
starting with $ indicate a Mac OS X / Linux / other *NIX system commands. Lines
starting without this character indicate Windows commands. The starting character ($) MUST NOT be typed!

1. Symlinking to a Joomla! installation

   This will create symlinks and hardlinks from your working directory to a
   locally installed Joomla! site. Any changes you perform to the repository
   files will be instantly reflected to the site, without the need to deploy
   your changes.

		$ phing relink -Dsite=/path/to/site/root
		phing relink -Dsite=c:\path\to\site\root

	Examples

		$ phing relink -Dsite=/var/www/html/joomla
		phing relink -Dsite=c:\xampp\htdocs\joomla

2. Relinking internal files

   This is required after every major upgrade in the component and/or when new
   plugins and modules are installed. It will create symlinks from the
   various external repositories to the MAIN directory.

		$ phing link
		phing link

3. Creating a dev release installation package

   This creates the installable ZIP packages of the component inside the
   MAIN/release directory.

		$ phing git
		phing git
