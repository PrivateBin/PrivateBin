<?php
/**
 * ZeroBin
 *
 * a zero-knowledge paste bin
 *
 * @link      http://sebsauvage.net/wiki/doku.php?id=php:zerobin
 * @copyright 2012 Sébastien SAUVAGE (sebsauvage.net)
 * @license   http://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 * @version   0.21.1
 */

/**
 * zerobin
 *
 * Controller, puts it all together.
 */
class zerobin
{
    /**
     * version
     *
     * @const string
     */
    const VERSION = '0.21.1';

    /**
     * show the same error message if the paste expired or does not exist
     *
     * @const string
     */
    const GENERIC_ERROR = 'Paste does not exist, has expired or has been deleted.';

    /**
     * configuration
     *
     * @access private
     * @var    configuration
     */
    private $_conf;

    /**
     * data
     *
     * @access private
     * @var    string
     */
    private $_data = '';

    /**
     * formatter
     *
     * @access private
     * @var    string
     */
    private $_formatter = 'plaintext';

    /**
     * error message
     *
     * @access private
     * @var    string
     */
    private $_error = '';

    /**
     * status message
     *
     * @access private
     * @var    string
     */
    private $_status = '';

    /**
     * JSON message
     *
     * @access private
     * @var    string
     */
    private $_json = '';

    /**
     * data storage model
     *
     * @access private
     * @var    zerobin_abstract
     */
    private $_model;

    /**
     * constructor
     *
     * initializes and runs ZeroBin
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
        if (version_compare(PHP_VERSION, '5.2.6') < 0)
        {
            throw new Exception(i18n::_('ZeroBin requires php 5.2.6 or above to work. Sorry.'), 1);
        }

        // in case stupid admin has left magic_quotes enabled in php.ini
        if (get_magic_quotes_gpc())
        {
            $_POST   = array_map('filter::stripslashes_deep', $_POST);
            $_GET    = array_map('filter::stripslashes_deep', $_GET);
            $_COOKIE = array_map('filter::stripslashes_deep', $_COOKIE);
        }

        // load config from ini file
        $this->_init();

        // create new paste or comment
        if (
            (array_key_exists('data', $_POST) && !empty($_POST['data'])) ||
            (array_key_exists('attachment', $_POST) && !empty($_POST['attachment']))
        )
        {
            $this->_create();
        }
        // delete an existing paste
        elseif (!empty($_GET['deletetoken']) && !empty($_GET['pasteid']))
        {
            $this->_delete($_GET['pasteid'], $_GET['deletetoken']);
        }
        // display an existing paste
        elseif (!empty($_SERVER['QUERY_STRING']))
        {
            $this->_read($_SERVER['QUERY_STRING']);
        }

        // output JSON or HTML
        if (strlen($this->_json))
        {
            header('Content-type: application/json');
            echo $this->_json;
        }
        else
        {
            $this->_view();
        }
    }

    /**
     * initialize zerobin
     *
     * @access private
     * @return void
     */
    private function _init()
    {
        foreach (array('cfg', 'lib') as $dir)
        {
            if (!is_file(PATH . $dir . DIRECTORY_SEPARATOR . '.htaccess')) file_put_contents(
                PATH . $dir . DIRECTORY_SEPARATOR . '.htaccess',
                'Allow from none' . PHP_EOL .
                'Deny from all'. PHP_EOL,
                LOCK_EX
            );
        }

        $this->_conf = new configuration;
        $this->_model = $this->_conf->getKey('class', 'model');
    }

    /**
     * get the model, create one if needed
     *
     * @access private
     * @return zerobin_abstract
     */
    private function _model()
    {
        // if needed, initialize the model
        if(is_string($this->_model)) {
            $this->_model = forward_static_call(
                array($this->_model, 'getInstance'),
                $this->_conf->getSection('model_options')
            );
        }
        return $this->_model;
    }

    /**
     * Store new paste or comment
     *
     * POST contains one or both:
     * data = json encoded SJCL encrypted text (containing keys: iv,v,iter,ks,ts,mode,adata,cipher,salt,ct)
     * attachment = json encoded SJCL encrypted text (containing keys: iv,v,iter,ks,ts,mode,adata,cipher,salt,ct)
     *
     * All optional data will go to meta information:
     * expire (optional) = expiration delay (never,5min,10min,1hour,1day,1week,1month,1year,burn) (default:never)
     * formatter (optional) = format to display the paste as (plaintext,syntaxhighlighting,markdown) (default:syntaxhighlighting)
     * burnafterreading (optional) = if this paste may only viewed once ? (0/1) (default:0)
     * opendiscusssion (optional) = is the discussion allowed on this paste ? (0/1) (default:0)
     * attachmentname = json encoded SJCL encrypted text (containing keys: iv,v,iter,ks,ts,mode,adata,cipher,salt,ct)
     * nickname (optional) = in discussion, encoded SJCL encrypted text nickname of author of comment (containing keys: iv,v,iter,ks,ts,mode,adata,cipher,salt,ct)
     * parentid (optional) = in discussion, which comment this comment replies to.
     * pasteid (optional) = in discussion, which paste this comment belongs to.
     *
     * @access private
     * @return string
     */
    private function _create()
    {
        $error = false;

        $has_attachment = array_key_exists('attachment', $_POST);
        $has_attachmentname = $has_attachment && array_key_exists('attachmentname', $_POST) && !empty($_POST['attachmentname']);
        $data = array_key_exists('data', $_POST) ? $_POST['data'] : '';
        $attachment = $has_attachment ? $_POST['attachment'] : '';
        $attachmentname = $has_attachmentname ? $_POST['attachmentname'] : '';

        // Make sure last paste from the IP address was more than X seconds ago.
        trafficlimiter::setLimit($this->_conf->getKey('limit', 'traffic'));
        trafficlimiter::setPath($this->_conf->getKey('dir', 'traffic'));
        $ipKey = 'REMOTE_ADDR';
        if (($option = $this->_conf->getKey('header', 'traffic')) !== null)
        {
            $header = 'HTTP_' . $option;
            if (array_key_exists($header, $_SERVER) && !empty($_SERVER[$header]))
            {
                $ipKey = $header;
            }
        }
        if (!trafficlimiter::canPass($_SERVER[$ipKey])) return $this->_return_message(
            1,
            i18n::_(
                'Please wait %d seconds between each post.',
                $this->_conf->getKey('limit', 'traffic')
            )
        );

        // Make sure content is not too big.
        $sizelimit = $this->_conf->getKey('sizelimit');
        if (
            strlen($data) + strlen($attachment) + strlen($attachmentname) > $sizelimit
        ) return $this->_return_message(
            1,
            i18n::_(
                'Paste is limited to %s of encrypted data.',
                filter::size_humanreadable($sizelimit)
            )
        );

        // Make sure format is correct.
        if (!sjcl::isValid($data)) return $this->_return_message(1, 'Invalid data.');

        // Make sure attachments are enabled and format is correct.
        if($has_attachment)
        {
            if (
                !$this->_conf->getKey('fileupload') ||
                !sjcl::isValid($attachment) ||
                !($has_attachmentname && sjcl::isValid($attachmentname))
            ) return $this->_return_message(1, 'Invalid attachment.');
        }

        // Read additional meta-information.
        $meta = array();

        // Read expiration date
        if (array_key_exists('expire', $_POST) && !empty($_POST['expire']))
        {
            $selected_expire = (string) $_POST['expire'];
            $expire_options = $this->_conf->getSection('expire_options');
            if (array_key_exists($selected_expire, $expire_options))
            {
                $expire = $expire_options[$selected_expire];
            }
            else
            {
                $expire = $this->_conf->getKey($this->_conf->getKey('default', 'expire'), 'expire_options');
            }
            if ($expire > 0) $meta['expire_date'] = time() + $expire;
        }

        // Destroy the paste when it is read.
        if (array_key_exists('burnafterreading', $_POST) && !empty($_POST['burnafterreading']))
        {
            $burnafterreading = $_POST['burnafterreading'];
            if ($burnafterreading !== '0')
            {
                if ($burnafterreading !== '1') $error = true;
                $meta['burnafterreading'] = true;
            }
        }

        // Read open discussion flag.
        if (
            $this->_conf->getKey('discussion') &&
            array_key_exists('opendiscussion', $_POST) &&
            !empty($_POST['opendiscussion'])
        )
        {
            $opendiscussion = $_POST['opendiscussion'];
            if ($opendiscussion !== '0')
            {
                if ($opendiscussion !== '1') $error = true;
                $meta['opendiscussion'] = true;
            }
        }

        // Read formatter flag.
        if (array_key_exists('formatter', $_POST) && !empty($_POST['formatter']))
        {
            $formatter = $_POST['formatter'];
            if (!array_key_exists($formatter, $this->_conf->getSection('formatter_options')))
            {
                $formatter = $this->_conf->getKey('defaultformatter');
            }
            $meta['formatter'] = $formatter;
        }

        // You can't have an open discussion on a "Burn after reading" paste:
        if (isset($meta['burnafterreading'])) unset($meta['opendiscussion']);

        // Optional nickname for comments
        if (!empty($_POST['nickname']))
        {
            // Generation of the anonymous avatar (Vizhash):
            // If a nickname is provided, we generate a Vizhash.
            // (We assume that if the user did not enter a nickname, he/she wants
            // to be anonymous and we will not generate the vizhash.)
            $nick = $_POST['nickname'];
            if (!sjcl::isValid($nick))
            {
                $error = true;
            }
            else
            {
                $meta['nickname'] = $nick;
                $vz = new vizhash16x16();
                $pngdata = $vz->generate($_SERVER['REMOTE_ADDR']);
                if ($pngdata != '')
                {
                    $meta['vizhash'] = 'data:image/png;base64,' . base64_encode($pngdata);
                }
                // Once the avatar is generated, we do not keep the IP address, nor its hash.
            }
        }

        if ($error) return $this->_return_message(1, 'Invalid data.');

        // Add post date to meta.
        $meta['postdate'] = time();

        // We just want a small hash to avoid collisions:
        // Half-MD5 (64 bits) will do the trick
        $dataid = substr(hash('md5', $data), 0, 16);

        $storage = array('data' => $data);

        // Add meta-information only if necessary.
        if (count($meta)) $storage['meta'] = $meta;

        // The user posts a comment.
        if (
            !empty($_POST['parentid']) &&
            !empty($_POST['pasteid'])
        )
        {
            $pasteid  = (string) $_POST['pasteid'];
            $parentid = (string) $_POST['parentid'];
            if (
                !filter::is_valid_paste_id($pasteid) ||
                !filter::is_valid_paste_id($parentid)
            ) return $this->_return_message(1, 'Invalid data.');

            // Comments do not expire (it's the paste that expires)
            unset($storage['expire_date']);
            unset($storage['opendiscussion']);

            // Make sure paste exists.
            if (
                !$this->_model()->exists($pasteid)
            ) return $this->_return_message(1, 'Invalid data.');

            // Make sure the discussion is opened in this paste.
            $paste = $this->_model()->read($pasteid);
            if (
                !$paste->meta->opendiscussion
            ) return $this->_return_message(1, 'Invalid data.');

            // Check for improbable collision.
            if (
                $this->_model()->existsComment($pasteid, $parentid, $dataid)
            ) return $this->_return_message(1, 'You are unlucky. Try again.');

            // New comment
            if (
                $this->_model()->createComment($pasteid, $parentid, $dataid, $storage) === false
            ) return $this->_return_message(1, 'Error saving comment. Sorry.');

            // 0 = no error
            return $this->_return_message(0, $dataid);
        }
        // The user posts a standard paste.
        else
        {
            // Check for improbable collision.
            if (
                $this->_model()->exists($dataid)
            ) return $this->_return_message(1, 'You are unlucky. Try again.');

            // Add attachment and its name, if one was sent
            if ($has_attachment) $storage['meta']['attachment'] = $attachment;
            if ($has_attachmentname) $storage['meta']['attachmentname'] = $attachmentname;

            // New paste
            if (
                $this->_model()->create($dataid, $storage) === false
            ) return $this->_return_message(1, 'Error saving paste. Sorry.');

            // Generate the "delete" token.
            // The token is the hmac of the pasteid signed with the server salt.
            // The paste can be delete by calling http://example.com/zerobin/?pasteid=<pasteid>&deletetoken=<deletetoken>
            $deletetoken = hash_hmac('sha1', $dataid, serversalt::get());

            // 0 = no error
            return $this->_return_message(0, $dataid, array('deletetoken' => $deletetoken));
        }
    }

    /**
     * Delete an existing paste
     *
     * @access private
     * @param  string $dataid
     * @param  string $deletetoken
     * @return void
     */
    private function _delete($dataid, $deletetoken)
    {
        // Is this a valid paste identifier?
        if (!filter::is_valid_paste_id($dataid))
        {
            $this->_error = 'Invalid paste ID.';
            return;
        }

        // Check that paste exists.
        if (!$this->_model()->exists($dataid))
        {
            $this->_error = self::GENERIC_ERROR;
            return;
        }

        // Get the paste itself.
        $paste = $this->_model()->read($dataid);

        // See if paste has expired.
        if (
            isset($paste->meta->expire_date) &&
            $paste->meta->expire_date < time()
        )
        {
            // Delete the paste
            $this->_model()->delete($dataid);
            $this->_error = self::GENERIC_ERROR;
            return;
        }

        if ($deletetoken == 'burnafterreading') {
            if (
                isset($paste->meta->burnafterreading) &&
                $paste->meta->burnafterreading
            )
            {
                // Delete the paste
                $this->_model()->delete($dataid);
                $this->_return_message(0, $dataid);
            }
            else
            {
                $this->_return_message(1, 'Paste is not of burn-after-reading type.');
            }
            return;
        }

        // Make sure token is valid.
        serversalt::setPath($this->_conf->getKey('dir', 'traffic'));
        if (!filter::slow_equals($deletetoken, hash_hmac('sha1', $dataid, serversalt::get())))
        {
            $this->_error = 'Wrong deletion token. Paste was not deleted.';
            return;
        }

        // Paste exists and deletion token is valid: Delete the paste.
        $this->_model()->delete($dataid);
        $this->_status = 'Paste was properly deleted.';
    }

    /**
     * Read an existing paste or comment
     *
     * @access private
     * @param  string $dataid
     * @return void
     */
    private function _read($dataid)
    {
        $isJson = false;
        if (($pos = strpos($dataid, '&json')) !== false) {
            $isJson = true;
            $dataid = substr($dataid, 0, $pos);
        }

        // Is this a valid paste identifier?
        if (!filter::is_valid_paste_id($dataid))
        {
            $this->_error = 'Invalid paste ID.';
            return;
        }

        // Check that paste exists.
        if ($this->_model()->exists($dataid))
        {
            // Get the paste itself.
            $paste = $this->_model()->read($dataid);

            // See if paste has expired.
            if (
                isset($paste->meta->expire_date) &&
                $paste->meta->expire_date < time()
            )
            {
                // Delete the paste
                $this->_model()->delete($dataid);
                $this->_error = self::GENERIC_ERROR;
            }
            // If no error, return the paste.
            else
            {
                // We kindly provide the remaining time before expiration (in seconds)
                if (
                    property_exists($paste->meta, 'expire_date')
                ) $paste->meta->remaining_time = $paste->meta->expire_date - time();

                // The paste itself is the first in the list of encrypted messages.
                $messages = array($paste);

                // If it's a discussion, get all comments.
                if (
                    property_exists($paste->meta, 'opendiscussion') &&
                    $paste->meta->opendiscussion
                )
                {
                    $messages = array_merge(
                        $messages,
                        $this->_model()->readComments($dataid)
                    );
                }

                // set formatter for for the view.
                if (!property_exists($paste->meta, 'formatter'))
                {
                    // support < 0.21 syntax highlighting
                    if (property_exists($paste->meta, 'syntaxcoloring') && $paste->meta->syntaxcoloring === true)
                    {
                        $paste->meta->formatter = 'syntaxhighlighting';
                    }
                    else
                    {
                        $paste->meta->formatter = $this->_conf->getKey('defaultformatter');
                    }
                }

                $this->_data = json_encode($messages);
            }
        }
        else
        {
            $this->_error = self::GENERIC_ERROR;
        }
        if ($isJson)
        {
            if (strlen($this->_error))
            {
                $this->_return_message(1, $this->_error);
            }
            else
            {
                $this->_return_message(0, $dataid, array('messages' => $messages));
            }
        }
    }

    /**
     * Display ZeroBin frontend.
     *
     * @access private
     * @return void
     */
    private function _view()
    {
        // set headers to disable caching
        $time = gmdate('D, d M Y H:i:s \G\M\T');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: ' . $time);
        header('Last-Modified: ' . $time);
        header('Vary: Accept');

        // label all the expiration options
        $expire = array();
        foreach ($this->_conf->getSection('expire_options') as $time => $seconds)
        {
            $expire[$time] = ($seconds == 0) ? i18n::_(ucfirst($time)): filter::time_humanreadable($time);
        }

        // translate all the formatter options
        $formatters = array_map(array('i18n', 'translate'), $this->_conf->getSection('formatter_options'));

        // set language cookie if that functionality was enabled
        $languageselection = '';
        if ($this->_conf->getKey('languageselection'))
        {
            $languageselection = i18n::getLanguage();
            setcookie('lang', $languageselection);
        }

        $page = new RainTPL;
        $page::$path_replace = false;
        // we escape it here because ENT_NOQUOTES can't be used in RainTPL templates
        $page->assign('CIPHERDATA', htmlspecialchars($this->_data, ENT_NOQUOTES));
        $page->assign('ERROR', i18n::_($this->_error));
        $page->assign('STATUS', i18n::_($this->_status));
        $page->assign('VERSION', self::VERSION);
        $page->assign('DISCUSSION', $this->_conf->getKey('discussion'));
        $page->assign('OPENDISCUSSION', $this->_conf->getKey('opendiscussion'));
        $page->assign('MARKDOWN', array_key_exists('markdown', $formatters));
        $page->assign('SYNTAXHIGHLIGHTING', array_key_exists('syntaxhighlighting', $formatters));
        $page->assign('SYNTAXHIGHLIGHTINGTHEME', $this->_conf->getKey('syntaxhighlightingtheme'));
        $page->assign('FORMATTER', $formatters);
        $page->assign('FORMATTERDEFAULT', $this->_conf->getKey('defaultformatter'));
        $page->assign('NOTICE', i18n::_($this->_conf->getKey('notice')));
        $page->assign('BURNAFTERREADINGSELECTED', $this->_conf->getKey('burnafterreadingselected'));
        $page->assign('PASSWORD', $this->_conf->getKey('password'));
        $page->assign('FILEUPLOAD', $this->_conf->getKey('fileupload'));
        $page->assign('BASE64JSVERSION', $this->_conf->getKey('base64version'));
        $page->assign('LANGUAGESELECTION', $languageselection);
        $page->assign('LANGUAGES', i18n::getLanguageLabels(i18n::getAvailableLanguages()));
        $page->assign('EXPIRE', $expire);
        $page->assign('EXPIREDEFAULT', $this->_conf->getKey('default', 'expire'));
        $page->draw($this->_conf->getKey('template'));
    }

    /**
     * return JSON encoded message and exit
     *
     * @access private
     * @param  bool $status
     * @param  string $message
     * @param  array $other
     * @return void
     */
    private function _return_message($status, $message, $other = array())
    {
        $result = array('status' => $status);
        if ($status)
        {
            $result['message'] = i18n::_($message);
        }
        else
        {
            $result['id'] = $message;
        }
        $result += $other;
        $this->_json = json_encode($result);
    }
}
