=== Backup ===
Contributors: Migrate
Tags: Backup, Migration, Migrate, Backups, Restore, Duplicate
Requires at least: 4.6
Tested up to: 5.6.1
Stable tag: 1.0.9
License: GPLv3
Requires PHP: 5.6

Backup Migrate Restore

== Description ==

Creating a backup of your site has never been easier!

Simply install the plugin, click on "Create backup now" - done.

You can also schedule backups, e.g. define that a backup should be taken automatically every week (or every day/month).

Use a wide choice of configuration options:
- Define exactly which files / databases should be in the backup, which not
- Define where the backup will be stored (as of now, only local option is available, but we'll expand this soon)
- Define what name your backup should have, in which instances you should receive a notification email, and much more

This plugin is also ideal if you want to migrate your site to another host.

If any questions come up, please ask us in the [Support Forum](https://wordpress.org/support/plugin/backup-backup) - we're always happy to help!

== Frequently Asked Questions ==

= How do I create my first backup? =

Click on “Create backup now” on the settings page of the Backup Migration plugin.

Backup Migration will by default create a backup that contains everything from your site, except the Backup Migration plugin’s own backups and WordPress installation - if you want to include the WordPress installation as well, tick the checkbox in the section “What will be backed up?”.

You can download backup or migrate your backup (use the plugin as a WordPress duplicator) immediately after the backup has been created.

= How do I restore a backup? =

- If your backup is **located on your site**: Go to the Backup Migration plugin screen, then to the Manage & Restore Backup(s) tab where you have your backups list, click on the Restore button next to the backup you would like to restore.

- If your backup is **located on another site**: Go to the Backup Migration plugin screen on site #1, then to the Manage & Restore Backup(s) tab where you have the backups list, click on the “Copy Link”-button in the “Actions”-column. Go to the Backup Migration plugin screen on site #2, then to the Manage & Restore Backup(s) tab, click on “Super-quick migration”, paste the copied link, and hit the “Restore now!” button. This process will first import backup then restore it, i.e. Backup Migrate also serves as backup importer.

- If your backup is *located on another device*: Go to the Backup Migration plugin screen, then to the Manage & Restore Backup(s) tab, and click on the “Upload backup files” button. After the upload, click on the Restore button next to the backup you would like to restore.

= How do I migrate or clone my site? =

Migrate (or clone) a WordPress site by creating a full backup on the site that you want to migrate (clone) - site #1.

- To transfer website **directly from site #1 to site #2**: Go to the Backup Migration plugin screen on site #1, then to the Manage & Restore Backup(s) tab where you have the backups list, click on the Copy Link button in the Actions column. Go to the Backup Migration plugin screen on site #1, then to the Manage & Restore Backup(s) tab, click on “Super-quick migration”, paste the copied link, and hit the “Restore now!” button. Make sure that the backup file on site #1 is accessible by setting “Accessible via direct link?” to “Yes” in the plugin section “Where shall the backup(s) be stored?”

- To migrate the website **indirectly**: Go to the Backup Migration plugin screen, then to the Manage & Restore Backup(s) tab, and click on the “Upload backup files” button. After the upload, click on the Restore button next to the backup you would like to restore.

= Where can I find my backups? =

Backup Migration allows you to download backups, migrate backups, or delete backups directly from the plugin screen Manage & Restore Backup(s). By default, the migrator plugin will store a backup to /wordpress/wp-content/backup-migration but you can change the backup location to anywhere you please.

= How to run automatic backups? =

Enabling automatic backups is done on the Backup Migration plugin’s home screen, just next to the “Create backup now!” button. Auto backup can run on a monthly, weekly, or daily basis. You can set the exact time (and day) and how many automatic backups would you like to keep in the same Backup Migration plugin section. We recommend that you optimize the number of backups that will be kept according to available space.

= How big are backup files? =

Backup file size depends on the criteria you select on the “What will be backed up?” section of the Backup Migration plugin. There you can see file/folder size calculations as you Save your settings. Usually, WordPress’ Uploads folder is the heaviest, while Databases are the lightest. If you are looking to save up space, you might want to deselect Plugins and WordPress installation folders, as you can usually download those anytime from WP sources.

= Is the backup creation and site migration free? =

Yes. You can create full site backups, automatic backups, and migrate your site (duplicate site) free of charge. [Backup Migration Pro](https://sellcodes.com/oZxnXtc2) provides more sophisticated filters and selections of files that will be included/excluded from backups (affecting backup size), faster backup creation times, number of external backup storage locations, backup encryption, backup file compression methods, advanced backup triggers, additional backup notifications by email, priority support, and more.

= Is cloud backup available?  =
Backup to cloud are some of the upcoming features, that will be available,
In Free version: Google Drive, FTP, Amazon S3, Rackspace, DreamObjects, Openstack and
In Premium version: Google cloud, SFTP/SCP, Microsoft Azure, OneDrive, Backblaze, and more.

= How are you better than other backup/migration plugins?  =
Besides having the most intuitive interface and smoothest user experience, Backup Migration plugin will always strive to give you more than any competitor:
- Updraftplus: They charge for migration, with our plugin it's free;
- All-in-One WP Migration: In the free version, compared to our plugin - they don’t have selective/partial backups; they lack advanced options and each external storage is on a separate extension plugin; they have no automatic backups;
- Duplicator: In the free version, compared to our plugin - they have no selective backups, exclusion rules, no automatic backups and no migration;
- WPvivid: In the free version, compared to our plugin - they don’t have selective/partial backups, exclusion rules, or automatic backups;
- BackWPup: In the free version, compared to our plugin - they lack restore options, backups are slower, automatic backups are dependant on wp cron;
- Backup Guard:  In the free version, compared to our plugin - they have no selective backups, exclusion rules; no direct migration;
- XCloner: Automatic backups are dependant on wp cron; full restore not available on a local server;
- Total Upkeep: They lack the advanced selective backups and exclusion rules, lacks a monthly backup schedule



== Screenshots ==
1. Backup Migration plugin front
2. What will be backed up
3. Backup in progress
4. Backup finished
5. Manage & Restore backups
6. Restoring in progress
7. Restore finished

== Installation ==

= Admin Installer via search =
1. Visit the Add New plugin screen and select "Author" from the dropdown near search input
2. Search for "Migrate"
3. Find "Backup Migration" and click the "Install Now" button.
4. Activate the plugin.
5. The plugin should be shown below settings menu.

= Admin Installer via zip =
1. Visit the Add New plugin screen and click the "Upload Plugin" button.
2. Click the "Browse..." button and select the zip file of our plugin.
3. Click "Install Now" button.
4. Once uploading is done, activate Backup Migration.
5. The plugin should be shown below the settings menu.


== Changelog ==

= 1.0.9 =
* Fixed issue of v1.0.8 [Automatic backups does not work]
* Fixed issue of v1.0.8 [Download of backup does not work]

= 1.0.8 =
* Added warnings for huge files (above 60 MB)
* Increased timeout limit
* Now plugin will ignore server abort command (should help in some cases)
* Progress bar won't show the counter if file count isn't known
* Increased default memory to (minimum: 384 MB)
* Added smart chunker for bigger sites (now it's possible to have chunks of 2500 files)
* Improved performance of the backups (should be much faster for sites 3000+ files)
* Database import is now memory friendly (database size can be really big, without error)
* Database export is also chunked for better stability of the backup
* Support for page builders – now cloned site should be perfect mirror
* Quick Migration: Removed timeout for huge files (should download full file now)
* Added better memory calculator, mostly improvement for shared hostings.
* Fixed issues on SunOS with free space calculator.
* Added support for installations not inside root (e.g. domain.tld, domain.tld/wordpress)
* Fixed issue when your database contains '-' character (fetch() function)
* Fixed PclZip issue i.e. "requires at least 18 bytes"
* Fixed BMI\Plugin\Zipper\finfo_open() error
* Premium: Replaced PclZip with more stable & dedicated edition of this module
* Premium: Improved performance of the core overall (smaller size to decrypt)
* Since now only site Administrator can manage backups by default
* Added permission "do_backups" – users with this permissions
* Added new option "Bypass server limits"
* Added support for ZipArchive (only when some bypass function is enabled)
* Added support for PHP Cli - it will be preferred option now

= 1.0.7 =
* Hot fix - Restore process fixed when premium plugin is activated

= 1.0.6 =
* Backup Support for WordPress 5.6
* Backup Support for PHP 8.0
* Fixed issue with completely empty backup files (0 bytes)
* Fixed back up progress (NaN shouldn't display anymore)
* For better backup & network performance decreased amount of calls
* Admin can bypass backup logs protection (File won't expire for them)
* Added update information to downloaded backup and restore logs
* Added some server infos to backup / migration logs
* Support for backup "front-end" errors – for easier debugging
* Server back up errors should be also logged in (limited on LSWS)
* Better back up error logging – global log will contain all errors
* Added back up troubleshooting option: send test email
* Added back up troubleshooting option: fix php_uname warning/error
* Removed back up PHP Errors reports from log files

= 1.0.5 =
* Premium relation
* Translations adjustment
* Load priority change (for better performance of entire website)

= 1.0.4 =
* Removed included PclZip
* Added support for WordPress 4.6
* Support for PHP 5.6
* Rephrased some tooltips to be more clear
* Added support for custom wp-content folder
* Changed excluded files by default

= 1.0.3 =
* Created special htaccess for litespeed
* Added dynamic counter of current file
* Added more info in backup logs

= 1.0.2 =
* Dedicated space checking with dummy file
* Added smart memory manager
* Fixed migration issues (database)
* Fixed backup issues (litespeed users)
* Progress won't hide on front-end error (e.g. lost connection)
* Added more error messages (backup)

= 1.0.1 =
* Changed tooltips background color for better contrast
* Updated some translation strings

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.9 =
What's new in 1.0.9?
* Fixed issue of v1.0.8 [Automatic backups does not work]
* Fixed issue of v1.0.8 [Download of backup does not work]
