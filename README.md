# README

This is a CodeIgniter project template which I use as a starting point for my contracting
practice.  It is a standard CI project, with a few useful additions.

* The support folder is added to your include path.  This is handy for situations where you don't
have access to your server's global PHP include dirs, and you want to use some third-party 
libraries.  Put them here.
* application/library/Smarty.php gives you a trivially simple way to integrate Smarty into your
application.  See the headerdoc in that file for more information.
* application/library/Fmp.php integrates the FileMaker database libraries with your project.  See
the headerdoc in that file for more information.
* application/library/MY_Session.php overrides the default CI session handling and replaces it with
standard PHP sessions, nicely wrapped up in CI goodness.  See the headerdoc for details.
* public/index.php has been modified to support an ENVIRONMENT constant defined by the server.
* public/index.php includes FileMaker.php (commented out)

# Setup

CodeIgniter 2.x:  You will need to put your CodeIgniter_2.1.2/system folder into this this folder, and call it
"system".

Smarty 3.x: The Smarty folder has to be somewhere in your PHP include path.  If you don't have a convenient place to put it, drop it into the "support" folder.

FileMaker PHP libraries need to be in your PHP include path, if you are going to use FileMaker.

Apache's ModRewrite must be installed and enabled.

## Turning On FileMaker Support

FileMaker has to be included from the `index.php` file.  The line exists, but it is commented out.

## File Permissions

As always, the entire directory needs to be recursively readable by the web server.  Additionally,
the tmp directory needs to be recursively writable by the web server too.  This is where smarty 
will keep its compiled templates and caches, and where session data files will be stored.

I have added a convenient command-line-tool controller that will let you set the file permissions
properly.  You will need sudo access, of course.

From the public directory, enter the following command.  'owner' should be the username or id of the
owner of the files, and 'webgroup' should be the group ID that the web server runs as (_www on 
MacOS X, www-data on many linux systems)

	sudo php index.php tool setFilePermissions owner webgroup

# Apache Configuration

The virtual host should point into the public directory, and include rewrite rules that
map any unknown files to index.php.

You can use APPLICATION_ENV to control which kind of environment you're working in, and thus which
configurations to use.  The usual values are 'production', 'staging', and 'development'.  I often
find it is useful to install with APPLICATION_ENV initially set to 'development' so I can see the
PHP errors while I'm shaking down an installation, and then change it to 'production' after 
everything is running.


	<VirtualHost *:80>
		DocumentRoot "/Users/cj/Sites/playground/public"
		ServerName playground
		
		
		SetEnv APPLICATION_ENV production
		
		<Directory /Users/cj/Sites/playground/public>
			Options FollowSymLinks SymLinksIfOwnerMatch
			DirectoryIndex index.html index.php
			AllowOverride All
			Order allow,deny
			Allow from all
			
			RewriteEngine On
			RewriteBase /
			RewriteCond %{REQUEST_FILENAME} !-f
			RewriteCond %{REQUEST_FILENAME} !-d
			RewriteRule ^(.*)$ index.php?/$1 [L]
		</Directory>
	</VirtualHost>

Don't forget to restart Apache after you've added the vhost configuration.

# Archiving

	git archive --format=zip --prefix=project/ HEAD > project.zip
