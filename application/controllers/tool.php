<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 *	This is the command-line tool, which provides the occasional useful script.  They range from
 *	the innocuous string-hash function to sudo-required permission changes.
 *
 *	Most of these functions have to be run from the command line.
 */
class Tool extends CI_Controller {

	/**
	 *	Test function
	 *
	 *	Just says hello.
	 *
	 *	@param string $to To whom shall we say hello?  Defaults to "World"
	 */
	public function index($to = 'World')
	{
		if ($this->input->is_cli_request()) {
			echo "Hello {$to}!".PHP_EOL;
		} else {
			exit ("\nYou can only run this from the command line\n");
		}
	}
	
	/**
	 *	Hash some text.  I'm always needing to hash some text for some reason or other.
	 *
	 *	@input string $text The text to hash
	 *	@input string $alg The algorithm to use.  Defaults to 'sha1', but anything returned by hash_algos() will work.
	 *	@return A base64-encoded hash of the string.
	 */
	public function hash($text, $alg='sha1') {
		echo base64_encode(hash($alg, urldecode($text), true));
	}

	/**
	 *	Use this as the root user to set the ownship and permissions of the various files in the
	 *	application.
	 *
	 *	If the 'production' configuration isn't the one you want, you may need to execute it like
	 *	so:
	 *
	 *	<pre>
	 *	APPLICATION_ENV=development; php index.php tool setFilePermission deploy _www
	 *	</pre>
	 *
	 *	All the files (in application, system, tmp, and public) are set to be owned by the
	 *	user and group you specify.  The mode is 0550 for everything except tmp, which receives
	 *	a 0770.
	 *
	 *	@param string $user The user who should own all the files
	 *	@param string $group The group that shold have access to all of the files
	 *	
	 */
	public function setFilePermissions($user, $group) {
		$readDirs = array( 'application', 'system', 'public', 'support' );
		$writeDirs = array( 'tmp' );
		
		if (!$this->input->is_cli_request())
			exit("\nYou have to run this from the command line.\n");
		
		if (! ($user && $group))
			exit("\nYou have to provide a user and group.\n");
		
		$topdir = realpath(APPPATH . '..');

		system("chown -R $user:$group $topdir");
		system("chmod u=rwx,g=rx,o= $topdir");
		
		// readable directories need to be rwx by user, and rx by group
		// readable files need to be rw by user, and r by group
		foreach($readDirs as $oneDir) {
			system("find $topdir/$oneDir -type d -exec chmod u=rwx,g=rx,o= {} \;");
			system("find $topdir/$oneDir -type f -exec chmod u=rw,g=r,o= {} \;");
		}
		
		// writable directories need to be rwx by user, and rwx by group
		// writable files need to be rw by user, and rw by group
		foreach($writeDirs as $oneDir) {
			system("find $topdir/$oneDir -type d -exec chmod u=rwx,g=rwx,o= {} \;");
			system("find $topdir/$oneDir -type f -exec chmod u=rw,g=rw,o= {} \;");
		}
	}
	
	/**
	 *	Print the PHP info.  You probably want to just remove this entire function from production
	 *	systems.  But it is just too useful in early development not to have this lying around.
	 */
	public function info() {
		phpinfo();
	}
	
}
