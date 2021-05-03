# Installation

**TL;DR:** Download the
[latest release archive](https://github.com/PrivateBin/PrivateBin/releases/latest)
and extract it in your web hosts folder where you want to install your PrivateBin
instance. We try to provide a mostly safe default configuration, but we urge you to
check the [security section](#hardening-and-security) below and the [configuration
options](#configuration) to adjust as you see fit.

**NOTE:** See [our FAQ](https://github.com/PrivateBin/PrivateBin/wiki/FAQ#how-can-i-securely-clonedownload-your-project) for information how to securely download the PrivateBin release files.

**NOTE:** There is a [ansible](https://ansible.com) role by @e1mo available to install and configure PrivateBin on your server. It's available on [ansible galaxy](https://galaxy.ansible.com/e1mo/privatebin) ([source code](https://git.sr.ht/~e1mo/ansible-role-privatebin)).

### Minimal requirements

- PHP version 7.0 or above
  - Or PHP version 5.6 AND _one_ of the following sources of cryptographically safe randomness:
    - [Libsodium](https://download.libsodium.org/libsodium/content/installation/) and it's [PHP extension](https://paragonie.com/book/pecl-libsodium/read/00-intro.md#installing-libsodium)
    - open_basedir access to `/dev/urandom`
    - mcrypt extension (mcrypt needs to be able to access `/dev/urandom`. This means if `open_basedir` is set, it must include this file.)
    - com_dotnet extension
- GD extension
- zlib extension
- some disk space or (optionally) a database supported by [PDO](https://php.net/manual/book.pdo.php)
- ability to create files and folders in the installation directory and the PATH defined in index.php
- A web browser with JavaScript support

## Hardening and security

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

### Changing the config path only

In situations where you want to keep the PrivateBin static files separate from the
rest of your data, or you want to reuse the installation files on multiple vhosts,
you may only want to change the `conf.php`. In this instance, you can set the
`CONFIG_PATH` environment variable to the absolute path to the `conf.php` file.
This can be done in your web server's virtual host config, the PHP config, or in
the index.php if you choose to customize it.

Note that your PHP process will need read access to the config wherever it may be.

> #### CONFIG_PATH example
> Setting the value in an Apache Vhost:
> SetEnv CONFIG_PATH /var/lib/privatebin/conf.php
>
> In a php-fpm pool config:
> env[CONFIG_PATH] = /var/lib/privatebin/conf.php
>
> In the index.php, near the top:
> putenv('CONFIG_PATH=/var/lib/privatebin/conf.php');

### Transport security

When setting up PrivateBin, also set up HTTPS, if you haven't already. Without HTTPS
PrivateBin is not secure, as the JavaScript files could be manipulated during transmission.
For more information on this, see our [FAQ entry on HTTPS setup](https://github.com/PrivateBin/PrivateBin/wiki/FAQ#how-should-i-setup-https).

### File-level permissions

After completing the installation, you should make sure, other users on the system cannot read the config file or the `data/` directory, as – depending on your configuration – potential secret information are saved there.

See [this FAQ item](https://github.com/PrivateBin/PrivateBin/wiki/FAQ#what-are-the-recommended-file-and-folder-permissions-for-privatebin) for a detailed guide on how to "harden" the permissions of files and folders.

## Configuration

In the file `cfg/conf.php` you can configure PrivateBin. A `cfg/conf.sample.php`
is provided containing all options and default values. You can copy it to
`cfg/conf.php` and adapt it as needed. Alternatively you can copy it anywhere and
set the `CONFIG_PATH` environment variable (see above notes). The config file is
divided into multiple sections, which are enclosed in square brackets.

In the `[main]` section you can enable or disable the discussion feature, set
the limit of stored pastes and comments in bytes. The `[traffic]` section lets
you set a time limit in seconds. Users may not post more often then this limit
to your PrivateBin installation.

More details can be found in the
[configuration documentation](https://github.com/PrivateBin/PrivateBin/wiki/Configuration).

## Advanced installation

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

### When using Cloudflare

If you want to use PrivateBin behind Cloudflare, make sure you have disabled the Rocket
loader and unchecked "Javascript" for Auto Minify, found in your domain settings,
under "Speed". (More information
[in this FAQ entry](https://github.com/PrivateBin/PrivateBin/wiki/FAQ#user-content-how-to-make-privatebin-work-when-using-cloudflare-for-ddos-protection))

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

The following GRANTs (privileges) are required for the PrivateBin user in **MySQL**. In normal operation:
- INSERT, SELECT, DELETE on the paste and comment tables
- SELECT on the config table

If you want PrivateBin to handle table creation (when you create the first paste) and updates (after you update PrivateBin to a new release), you need to give the user these additional privileges:
- CREATE, INDEX and ALTER on the database
- INSERT and UPDATE on the config table

For reference or if you want to create the table schema for yourself to avoid having to give PrivateBin too many permissions (replace
`prefix_` with your own table prefix and create the table schema with your favourite MySQL console):

```sql
CREATE TABLE prefix_paste (
    dataid CHAR(16) NOT NULL,
    data MEDIUMBLOB,
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
INSERT INTO prefix_config VALUES('VERSION', '1.3.5');
```

In **PostgreSQL**, the data, attachment, nickname and vizhash columns needs to be TEXT and not BLOB or MEDIUMBLOB.
