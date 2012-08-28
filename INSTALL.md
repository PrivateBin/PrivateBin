Installation
============

For Administrators
------------------

In the index.php in the main folder you can define a different PATH. This is 
useful if you want to secure your installation and want to move the 
configuration, data files, templates and PHP libraries (directories cfg, data, 
lib, tpl and tst) outside of your document root. This new location must still 
be accessible to your webserver / PHP process.

> ### PATH Example ###
> Your zerobin installation lives in a subfolder called "paste" inside of your 
> document root. The URL looks like this:
> http://example.com/paste/
> The ZeroBin folder on your webserver is really:
> /home/example.com/htdocs/paste
> 
> When setting the path like this:
> define('PATH', '../../secret/zerobin/');
> ZeroBin will look for your includes here:
> /home/example.com/secret/zerobin

In the file "cfg/conf.ini" you can configure ZeroBin. The config file is 
divided into multiple sections, which are enclosed in square brackets. In the 
"[main]" section you can enable or disable the discussion feature, set the 
limit of stored pastes and comments in bytes. The "[traffic]" section lets you 
set a time limit in seconds. Users may not post more often then this limit to 
your ZeroBin.

Finally the "[model]" and "[model_options]" sections let you configure your 
favourite way of storing the pastes and discussions on your server. 
"zerobin_data" is the default model, which stores everything in files in the 
data folder. This is the recommended setup for low traffic sites. Under high 
load, in distributed setups or if you are not allowed to store files locally, 
you might want to switch to the "zerobin_db" model. This lets you store your 
data in a database. Basically all databases that are supported by PDO (PHP 
data objects) may be used. Automatic table creation is provided for pdo_ibm, 
pdo_informix, pdo_mssql, pdo_mysql, pdo_oci, pdo_pgsql and pdo_sqlite. You may 
want to provide a table prefix, if you have to share the zerobin database with 
another application. The table prefix option is called "tbl".

> ### Note ###
> The "zerobin_db" model has only been tested with SQLite and MySQL, although
> it would not be recommended to use SQLite in a production environment. If you
> gain any experience running ZeroBin on other RDBMS, please let us know.

For reference or if you want to create the table schema for yourself:

    CREATE TABLE prefix_paste (
        dataid CHAR(16),
        data TEXT,
        postdate INT,
        expiredate INT,
        opendiscussion INT,
        burnafterreading INT
    );
    
    CREATE TABLE prefix_comment (
        dataid CHAR(16),
        pasteid CHAR(16),
        parentid CHAR(16),
        data TEXT,
        nickname VARCHAR(255),
        vizhash TEXT,
        postdate INT
    );

For Developers 
--------------
If you want to create your own data models, you might want to know how the 
arrays, that you have to store, look like:

    public function create($pasteid, $paste)
    {
        $pasteid = substr(hash('md5', $paste['data']), 0, 16);
        
        $paste['data']                      // text
        $paste['meta']['postdate']          // int UNIX timestamp
        $paste['meta']['expire_date']       // int UNIX timestamp
        $paste['meta']['opendiscussion']    // true (if false it is unset)
        $paste['meta']['burnafterreading']  // true (if false it is unset; if true, then opendiscussion is unset)
    }
    
    public function createComment($pasteid, $parentid, $commentid, $comment)
    {
        $pasteid  // the id of the paste this comment belongs to
        $parentid // the id of the parent of this comment, may be the paste id itself
        $commentid = substr(hash('md5', $paste['data']), 0, 16);
        
        $comment['data']                    // text
        $comment['meta']['nickname']        // text or null (if anonymous)
        $comment['meta']['vizhash']         // text or null (if anonymous)
        $comment['meta']['postdate']        // int UNIX timestamp
    }

