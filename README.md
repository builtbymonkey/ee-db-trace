ExpressionEngine DB Trace Module
===========

The general concept is to trace your local db changes and store them into a release file. Release files can be deployed through FTP/GIT/SVN/Etc, after deploy you can do an install of the release, pushing all your db changes.

##Installation

- Copy database driver mysql_trace folder to system\codeigniter\system\database\drivers
- Copy other folders to appropriate EE folders
- Add config values (see below).
- Change your in db driver in config/database.php to mysql_trace
- Make thrid_party/trace/files writeable (chmod 777)

##Configuration

Add the following keys to your config.php, example for a local dev environment.

```
$config['trace_live_site'] = FALSE;
$config['trace_url'] = 'http://local-dev.dwise.nl/';
$config['trace_path'] = '/var/www/sites/dwise.nl/';

$config['trace_developer'] = "fccotech";
$config['trace_include'] = array('update','insert','delete','alter','drop','create');
$config['trace_exclude'] = array('exp_cp_log','exp_channel_entries_autosave','exp_stats','exp_sessions','exp_sites','exp_captcha','exp_online_users', 'exp_security_hashes','last_activity');
```

**$config['trace_live_site']**

True/false -> set to false on your dev server, true on your staging / production server.

**$config['trace_url']**

The url of you current site, this url is copied to the release file. Afterwards we can automaticly correct urls on the live site.

**$config['trace_path']**

The path on your current site, see above

**$config['trace_developer']**

Tag that identifies the release files which yout developer info.

**$config['trace_include']**

SQL actions to be traced, SELECT is excluded by default because it doesn't change anything in you DB (duh..)

**$config['trace_exclude']**

Tables not to be traced (non critical stuff).

##Example live site config

```
$config['trace_live_site'] = TRUE;
$config['trace_url'] = 'http://www.dwise.nl/';
$config['trace_path'] = '/var/www/sites/dwise.nl/';
```
