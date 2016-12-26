# Installation

**TL;DR:** Download the
[latest release archive](https://github.com/PrivateBin/PrivateBin/releases/latest)
and extract it in your web hosts folder where you want to install your PrivateBin
instance. We try to provide a safe default configuration, but we advise you to
check the options and adjust them as you see fit.

## Basic installation

### Requirements

- PHP version 5.3 or above
- _one_ of the following sources of cryptographically safe randomness is required:
  - PHP 7 or higher
  - [Libsodium](https://download.libsodium.org/libsodium/content/installation/) and it's [PHP extension](https://paragonie.com/book/pecl-libsodium/read/00-intro.md#installing-libsodium)
  - open_basedir access to `/dev/urandom`
  - mcrypt extension
  - com_dotnet extension
  
  Mcrypt needs to be able to access `/dev/urandom`. This means if `open_basedir` is set, it must include this file.
- GD extension
- some disk space or (optional) a database supported by [PDO](https://secure.php.net/manual/book.pdo.php)
- ability to create files and folders in the installation directory and the PATH
- A web browser with javascript support

### Configuration

In the file `cfg/conf.ini` you can configure PrivateBin. A `cfg/conf.ini.sample`
is provided containing all options and default values. You can copy it to
`cfg/conf.ini` and adapt it as needed. The config file is divided into multiple
sections, which are enclosed in square brackets.

In the `[main]` section you can enable or disable the discussion feature, set
the limit of stored pastes and comments in bytes. The `[traffic]` section lets
you set a time limit in seconds. Users may not post more often then this limit
to your PrivateBin installation.

More details can be found in the
[configuration documentation](https://github.com/PrivateBin/PrivateBin/wiki/Configuration).

## Further configuration

After (or before) setting up PrivateBin, also set up HTTPS, as without HTTPS
PrivateBin is not secure. (
[More information](https://github.com/PrivateBin/PrivateBin/wiki/FAQ#how-should-i-setup-https))

If you want to use PrivateBin behind Cloudflare, make sure you disabled Rocket
loader and unchecked "Javascript" for Auto Minify, found in your domain settings,
under "Speed". (More information
[in this FAQ entry](https://github.com/PrivateBin/PrivateBin/wiki/FAQ#user-content-how-to-make-privatebin-work-when-using-cloudflare-for-ddos-protection))

## Advanced installation

### Changing the path

In the index.php you can define a different `PATH`. This is useful to secure your
installation. You can move the configuration, data files, templates and PHP
libraries (directories cfg, doc, data, lib, tpl, tst and vendor) outside of your
document root. This new location must still be accessible to your webserver / PHP
process (see also
[open_basedir setting](https://secure.php.net/manual/en/ini.core.php#ini.open-basedir)).

> #### PATH Example
> Your PrivateBin installation lives in a subfolder called "paste" inside of
> your document root. The URL looks like this:
> http://example.com/paste/
>
> The full path of PrivateBin on your webserver is:
> /home/example.com/htdocs/paste
> 
> When setting the path like this:
> define('PATH', '../../secret/privatebin/');
>
> PrivateBin will look for your includes / data here:
> /home/example.com/secret/privatebin

### Web server configuration

A `robots.txt` file is provided in the root dir of PrivateBin. It disallows all
robots from accessing your pastes. It is recommend to place it into the root of
your web directory if you have installed PrivateBin in a subdirectory. Make sure
to adjust it, so that the file paths match your installation. Of course also
adjust the file if you already use a `robots.txt`.

A `.htaccess.disabled` file is provided in the root dir of PrivateBin. It blocks
some known robots and link-scanning bots. If you use Apache, you can rename the
file to `.htaccess` to enable this feature. If you use another webserver, you
have to configure it manually to do the same.

### Using a database instead of flat files

In the configuration file the `[model]` and `[model_options]` sections let you
configure your favourite way of storing the pastes and discussions on your
server.

`Filesystem` is the default model, which stores everything in files in the
data folder. This is the recommended setup for most sites.

Under high load, in distributed setups or if you are not allowed to store files
locally, you might want to switch to the `Database` model. This lets you
store your data in a database. Basically all databases that are supported by
[PDO](https://secure.php.net/manual/en/book.pdo.php) may be used. Automatic table
creation is provided for `pdo_ibm`, `pdo_informix`, `pdo_mssql`, `pdo_mysql`,
`pdo_oci`, `pdo_pgsql` and `pdo_sqlite`. You may want to provide a table prefix,
if you have to share the PrivateBin database with another application or you want
to use a prefix for
[security reasons](https://security.stackexchange.com/questions/119510/is-using-a-db-prefix-for-tables-more-secure).
The table prefix option is called `tbl`.

> #### Note
> The `Database` model has only been tested with SQLite, MySQL and PostgreSQL,
> although it would not be recommended to use SQLite in a production environment.
> If you gain any experience running PrivateBin on other RDBMS, please let us
> know.

For reference or if you want to create the table schema for yourself (replace
`prefix_` with your own table prefix):

    CREATE TABLE prefix_paste (
        dataid CHAR(16) NOT NULL,
        data BLOB,
        postdate INT,
        expiredate INT,
        opendiscussion INT,
        burnafterreading INT,
        meta TEXT,
        attachment MEDIUMBLOB,
        attachmentname BLOB,
        PRIMARY KEY (dataid)
    );
    
    CREATE TABLE prefix_comment (
        dataid CHAR(16),
        pasteid CHAR(16),
        parentid CHAR(16),
        data BLOB,
        nickname BLOB,
        vizhash BLOB,
        postdate INT,
        PRIMARY KEY (dataid)
    );
    CREATE INDEX parent ON prefix_comment(pasteid);
    
    CREATE TABLE prefix_config (
        id CHAR(16) NOT NULL, value TEXT, PRIMARY KEY (id)
    );
    INSERT INTO prefix_config VALUES('VERSION', '1.1');
