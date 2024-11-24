# Installation

## TL;DR

Download the
[latest release archive](https://github.com/PrivateBin/PrivateBin/releases/latest)
(with the link labelled as "Source code (…)") and extract it in your web hosts
folder where you want to install your PrivateBin instance. We try to provide a
mostly safe default configuration, but we urge you to check the
[security section](#hardening-and-security) below and the
[configuration options](#configuration) to adjust as you see fit.

**NOTE:** See our [FAQ entry on securely downloading release files](https://github.com/PrivateBin/PrivateBin/wiki/FAQ#how-can-i-securely-clonedownload-your-project)
for more information.

**NOTE:** There are Ansible roles available for installing and configuring PrivateBin on your server. You can choose from the following options:

- [Podman Rootless - PrivateBin by @voidquark](https://galaxy.ansible.com/ui/standalone/roles/voidquark/privatebin/)  ([Github source code](https://github.com/voidquark/privatebin)): Simplifies the deployment and management of a secure PrivateBin service using a rootless Podman container. Key features include root-less deployment, ensuring security within a user namespace, idempotent deployment for consistent state, out-of-the-box setup for Red Hat systems, and the flexibility to customize PrivateBin configurations. It has been tested on EL9.

- [Config Configuration - PrivateBin by @e1mo](https://galaxy.ansible.com/ui/standalone/roles/e1mo/privatebin/) ([Github source code](https://git.sr.ht/~e1mo/ansible-role-privatebin)): Deploy PrivateBin configuration to disk with a customized configuration.

### Minimal Requirements

- PHP version 7.3 or above
- ctype extension
- GD extension (when using identicon or vizhash icons, jdenticon works without it)
- zlib extension
- some disk space or a database supported by [PDO](https://php.net/manual/book.pdo.php)
- ability to create files and folders in the installation directory and the PATH
  defined in index.php
- A web browser with JavaScript and (optional) WebAssembly support

## Hardening and Security

### Changing the Path

In the index.php you can define a different `PATH`. This is useful to secure
your installation. You can move the utilities, configuration, data files,
templates and PHP libraries (directories bin, cfg, doc, data, lib, tpl, tst and
vendor) outside of your document root. This new location must still be
accessible to your webserver and PHP process (see also
[open_basedir setting](https://secure.php.net/manual/en/ini.core.php#ini.open-basedir)).

> #### PATH Example
> Your PrivateBin installation lives in a subfolder called "paste" inside of
> your document root. The URL looks like this:
> http://example.com/paste/
>
> The full path of PrivateBin on your webserver is:
> /srv/example.com/htdocs/paste
>
> When setting the path like this:
> define('PATH', '../../secret/privatebin/');
>
> PrivateBin will look for your includes and data here:
> /srv/example.com/secret/privatebin

### Changing the config path only

In situations where you want to keep the PrivateBin static files separate from the
rest of your data, or you want to reuse the installation files on multiple vhosts,
you may only want to change the `conf.php`. In this case, you can set the
`CONFIG_PATH` environment variable to the absolute path to the directory containing the `conf.php` file.
This can be done in your web server's virtual host config, the PHP config, or in
the index.php, if you choose to customize it.

Note that your PHP process will need read access to the configuration file,
wherever it may be.

> #### CONFIG_PATH example
> Setting the value in an Apache Vhost:
> SetEnv CONFIG_PATH /var/lib/privatebin/
>
> In a php-fpm pool config:
> env[CONFIG_PATH] = /var/lib/privatebin/
>
> In the index.php, near the top:
> putenv('CONFIG_PATH=/var/lib/privatebin/');

### Transport security

When setting up PrivateBin, also set up HTTPS, if you haven't already. Without
HTTPS PrivateBin is not secure, as the JavaScript or WebAssembly files could be
manipulated during transmission. For more information on this, see our
[FAQ entry on HTTPS setup recommendations](https://github.com/PrivateBin/PrivateBin/wiki/FAQ#how-should-i-setup-https).

### File-level permissions

After completing the installation, you should make sure, that other users on the
system cannot read the config file or the `data/` directory, as – depending on
your configuration – potentially sensitive information may be stored in there.

See our [FAQ entry on permissions](https://github.com/PrivateBin/PrivateBin/wiki/FAQ#what-are-the-recommended-file-and-folder-permissions-for-privatebin)
for a detailed guide on how to "harden" access to files and folders.

## Configuration

In the file `cfg/conf.php` you can configure PrivateBin. A `cfg/conf.sample.php`
is provided containing all options and their default values. You can copy it to
`cfg/conf.php` and change it as needed. Alternatively you can copy it anywhere
and set the `CONFIG_PATH` environment variable (see above notes). The config
file is divided into multiple sections, which are enclosed in square brackets.

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
adjust the file, if you already use a `robots.txt`.

A `.htaccess.disabled` file is provided in the root dir of PrivateBin. It blocks
some known robots and link-scanning bots. If you use Apache, you can rename the
file to `.htaccess` to enable this feature. If you use another webserver, you
have to configure it manually to do the same.

### On using Cloudflare

If you want to use PrivateBin behind Cloudflare, make sure you have disabled the
Rocket loader and unchecked "Javascript" for Auto Minify, found in your domain
settings, under "Speed". More information can be found in our
[FAQ entry on Cloudflare related issues](https://github.com/PrivateBin/PrivateBin/wiki/FAQ#user-content-how-to-make-privatebin-work-when-using-cloudflare-for-ddos-protection).

### Using a Database Instead of Flat Files

In the configuration file the `[model]` and `[model_options]` sections let you
configure your favourite way of storing the pastes and discussions on your
server.

`Filesystem` is the default model, which stores everything in files in the
data folder. This is the recommended setup for most sites on single hosts.

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
> The `Database` model has only been tested with SQLite, MariaDB/MySQL and
> PostgreSQL, although it would not be recommended to use SQLite in a production
> environment. If you gain any experience running PrivateBin on other RDBMS,
> please let us know.

The following GRANTs (privileges) are required for the PrivateBin user in
**MariaDB/MySQL**. In normal operation:
- INSERT, SELECT, DELETE on the paste and comment tables
- SELECT on the config table

If you want PrivateBin to handle table creation (when you create the first paste)
and updates (after you update PrivateBin to a new release), you need to give the
user these additional privileges:
- CREATE, INDEX and ALTER on the database
- INSERT and UPDATE on the config table

For reference or if you want to create the table schema for yourself to avoid
having to give PrivateBin too many permissions (replace `prefix_` with your own
table prefix and create the table schema with your favorite MariaDB/MySQL
client):

```sql
CREATE TABLE prefix_paste (
    dataid CHAR(16) NOT NULL,
    data MEDIUMBLOB,
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
INSERT INTO prefix_config VALUES('VERSION', '1.7.5');
```

In **PostgreSQL**, the `data`, `attachment`, `nickname` and `vizhash` columns
need to be `TEXT` and not `BLOB` or `MEDIUMBLOB`. The key names in brackets,
after `PRIMARY KEY`, need to be removed.

In **Oracle**, the `data`, `attachment`, `nickname` and `vizhash` columns need
to be `CLOB` and not `BLOB` or `MEDIUMBLOB`, the `id` column in the `config`
table needs to be `VARCHAR2(16)` and the `meta` column in the `paste` table
and the `value` column in the `config` table need to be `VARCHAR2(4000)`.

### Cloud Storage Backends

Due to the large size of the respective cloud SDKs required for these, we didn't
include these in the `vendor` directory shipped in our release archives. To use
these in your manual installation, you will need [composer installed](https://getcomposer.org/)
and require the used library (see instructions below).

This is not required if using the dedicated container images that have these SDKs
preinstalled.

#### Using Google Cloud Storage
If you want to deploy PrivateBin in a serverless manner in the Google Cloud, you
can choose the `GoogleCloudStorage` as backend.

To use this backend, you first have to install the SDK from the installation
directory of PrivateBin:

```console
composer require --no-update google/cloud-storage
composer update --no-dev --optimize-autoloader
```

You have to create a GCS bucket and specify the name as the model option `bucket`.
Alternatively, you can set the name through the environment variable `PRIVATEBIN_GCS_BUCKET`.

The default prefix for pastes stored in the bucket is `pastes`. To change the
prefix, specify the option `prefix`.

Google Cloud Storage buckets may be significantly slower than a `FileSystem` or
`Database` backend. The big advantage is that the deployment on Google Cloud
Platform using Google Cloud Run is easy and cheap.

#### Using S3 Storage
Similar to Google Cloud Storage, you can choose S3 as storage backend. It uses
the AWS SDK for PHP, but can also talk to a Rados gateway as part of a Ceph
cluster.

To use this backend, you first have to install the SDK from the installation
directory of PrivateBin:

```console
composer require --no-update aws/aws-sdk-php
composer update --no-dev --optimize-autoloader
```

You have to create an S3 bucket on the Ceph cluster before using the S3 backend.

In the `[model]` section of cfg/conf.php, set `class` to `S3Storage`.

You can set any combination of the following options in the `[model_options]`
section:

  * region
  * version
  * endpoint
  * bucket
  * prefix
  * accesskey
  * secretkey
  * use_path_style_endpoint

By default, prefix is empty. If set, the S3 backend will place all PrivateBin
data beneath this prefix.

For AWS, you have to provide at least `region`, `bucket`, `accesskey`, and
`secretkey`.

For Ceph, follow this example:

```
region = ""
version = "2006-03-01"
endpoint = "https://s3.my-ceph.invalid"
use_path_style_endpoint = true
bucket = "my-bucket"
accesskey = "my-rados-user"
secretkey = "my-rados-pass"
```
