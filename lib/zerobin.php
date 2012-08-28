<?php
/**
 * ZeroBin
 *
 * a zero-knowledge paste bin
 *
 * @link      http://sebsauvage.net/wiki/doku.php?id=php:zerobin
 * @copyright 2012 Sébastien SAUVAGE (sebsauvage.net)
 * @license   http://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 * @version   0.15
 */

/**
 * zerobin
 *
 * Controller, puts it all together.
 */
class zerobin
{
    /*
     * @const string version
     */
    const VERSION = 'Alpha 0.15';

    /**
     * @access private
     * @var    array
     */
    private $_conf = array(
        'model' => 'zerobin_data',
    );

    /**
     * @access private
     * @var    string
     */
    private $_data = '';

    /**
     * @access private
     * @var    string
     */
    private $_error = '';

    /**
     * @access private
     * @var    zerobin_data
     */
    private $_model;

    /**
     * constructor
     *
     * initializes and runs ZeroBin
     *
     * @access public
     */
    public function __construct()
    {
        if (version_compare(PHP_VERSION, '5.2.6') < 0)
            die('ZeroBin requires php 5.2.6 or above to work. Sorry.');

        // In case stupid admin has left magic_quotes enabled in php.ini.
        if (get_magic_quotes_gpc())
        {
            $_POST   = array_map('filter::stripslashes_deep', $_POST);
            $_GET    = array_map('filter::stripslashes_deep', $_GET);
            $_COOKIE = array_map('filter::stripslashes_deep', $_COOKIE);
        }

        // Load config from ini file.
        $this->_init();

        // Create new paste or comment.
        if (!empty($_POST['data']))
        {
            $this->_create();
        }
        // Display an existing paste.
        elseif (!empty($_SERVER['QUERY_STRING']))
        {
            $this->_read();
        }

        // Display ZeroBin frontend
        $this->_view();
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
            if (!is_file(PATH . $dir . '/.htaccess')) file_put_contents(
                PATH . $dir . '/.htaccess',
                'Allow from none' . PHP_EOL .
                'Deny from all'. PHP_EOL
            );
        }

        $this->_conf = parse_ini_file(PATH . 'cfg/conf.ini', true);
        $this->_model = $this->_conf['model']['class'];
    }

    /**
     * get the model, create one if needed
     *
     * @access private
     * @return zerobin_data
     */
    private function _model()
    {
        // if needed, initialize the model
        if(is_string($this->_model)) {
            $this->_model = forward_static_call(
                array($this->_model, 'getInstance'),
                $this->_conf['model_options']
            );
        }
        return $this->_model;
    }

    /**
     * Store new paste or comment.
     *
     * POST contains:
     * data (mandatory) = json encoded SJCL encrypted text (containing keys: iv,salt,ct)
     *
     * All optional data will go to meta information:
     * expire (optional) = expiration delay (never,5min,10min,1hour,1day,1week,1month,1year,burn) (default:never)
     * opendiscusssion (optional) = is the discussion allowed on this paste ? (0/1) (default:0)
     * nickname (optional) = in discussion, encoded SJCL encrypted text nickname of author of comment (containing keys: iv,salt,ct)
     * parentid (optional) = in discussion, which comment this comment replies to.
     * pasteid (optional) = in discussion, which paste this comment belongs to.
     *
     * @access private
     * @return void
     */
    private function _create()
    {
        header('Content-type: application/json');
        $error = false;

        // Make sure last paste from the IP address was more than X seconds ago.
        trafficlimiter::setLimit($this->_conf['traffic']['limit']);
        trafficlimiter::setPath($this->_conf['traffic']['dir']);
        if (
            !trafficlimiter::canPass($_SERVER['REMOTE_ADDR'])
        ) $this->_return_message(
            1,
            'Please wait ' .
            $this->_conf['traffic']['limit'] .
            ' seconds between each post.'
        );

        // Make sure content is not too big.
        $data = $_POST['data'];
        if (
            strlen($data) > $this->_conf['main']['sizelimit']
        ) $this->_return_message(
            1,
            'Paste is limited to ' .
            $this->_conf['main']['sizelimit'] .
            ' ' .
            filter::size_humanreadable($this->_conf['main']['sizelimit']) .
            ' of encrypted data.'
        );

        // Make sure format is correct.
        if (!sjcl::isValid($data)) $this->_return_message(1, 'Invalid data.');

        // Read additional meta-information.
        $meta=array();

        // Read expiration date
        if (!empty($_POST['expire']))
        {
            switch ($_POST['expire'])
            {
                case 'burn':
                    $meta['burnafterreading'] = true;
                    break;
                case '5min':
                    $meta['expire_date'] = time()+5*60;
                    break;
                case '10min':
                    $meta['expire_date'] = time()+10*60;
                    break;
                case '1hour':
                    $meta['expire_date'] = time()+60*60;
                    break;
                case '1day':
                    $meta['expire_date'] = time()+24*60*60;
                    break;
                case '1week':
                    $meta['expire_date'] = time()+7*24*60*60;
                    break;
                case '1month':
                    $meta['expire_date'] = strtotime('+1 month');
                    break;
                case '1year':
                    $meta['expire_date'] = strtotime('+1 year');
            }
        }

        // Read open discussion flag.
        if ($this->_conf['main']['opendiscussion'] && !empty($_POST['opendiscussion']))
        {
            $opendiscussion = $_POST['opendiscussion'];
            if ($opendiscussion != 0)
            {
                if ($opendiscussion != 1) $error = true;
                $meta['opendiscussion'] = true;
            }
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

        if ($error) $this->_return_message(1, 'Invalid data.');

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
            $pasteid  = $_POST['pasteid'];
            $parentid = $_POST['parentid'];
            if (
                !preg_match('/[a-f\d]{16}/', $pasteid) ||
                !preg_match('/[a-f\d]{16}/', $parentid)
            ) $this->_return_message(1, 'Invalid data.');

            // Comments do not expire (it's the paste that expires)
            unset($storage['expire_date']);
            unset($storage['opendiscussion']);

            // Make sure paste exists.
            if (
                !$this->_model()->exists($pasteid)
            ) $this->_return_message(1, 'Invalid data.');

            // Make sure the discussion is opened in this paste.
            $paste = $this->_model()->read($pasteid);
            if (
                !$paste->meta->opendiscussion
            ) $this->_return_message(1, 'Invalid data.');

            // Check for improbable collision.
            if (
                $this->_model()->existsComment($pasteid, $parentid, $dataid)
            ) $this->_return_message(1, 'You are unlucky. Try again.');

            // New comment
            if (
                $this->_model()->createComment($pasteid, $parentid, $dataid, $storage) === false
            ) $this->_return_message(1, 'Error saving comment. Sorry.');

            // 0 = no error
            $this->_return_message(0, $dataid);
        }
        // The user posts a standard paste.
        else
        {
            // Check for improbable collision.
            if (
                $this->_model()->exists($dataid)
            ) $this->_return_message(1, 'You are unlucky. Try again.');

            // New paste
            if (
                $this->_model()->create($dataid, $storage) === false
            ) $this->_return_message(1, 'Error saving paste. Sorry.');

            // 0 = no error
            $this->_return_message(0, $dataid);
        }

        $this->_return_message(1, 'Server error.');
    }

    /**
     * Read an existing paste or comment.
     *
     * @access private
     * @return void
     */
    private function _read()
    {
        $dataid = $_SERVER['QUERY_STRING'];

        // Is this a valid paste identifier?
        if (preg_match('/[a-f\d]{16}/', $dataid))
        {
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
                    $this->_error = 'Paste does not exist or has expired.';
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
                    $this->_data = json_encode($messages);

                    // If the paste was meant to be read only once, delete it.
                    if (
                        property_exists($paste->meta, 'burnafterreading') &&
                        $paste->meta->burnafterreading
                    ) $this->_model()->delete($dataid);
                }
            }
            else
            {
                $this->_error = 'Paste does not exist or has expired.';
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

        $page = new RainTPL;
        // We escape it here because ENT_NOQUOTES can't be used in RainTPL templates.
        $page->assign('CIPHERDATA', htmlspecialchars($this->_data, ENT_NOQUOTES));
        $page->assign('ERRORMESSAGE', $this->_error);
        $page->assign('OPENDISCUSSION', $this->_conf['main']['opendiscussion']);
        $page->assign('VERSION', self::VERSION);
        $page->draw('page');
    }

    /**
     * return JSON encoded message and exit
     *
     * @access private
     * @param  bool $status
     * @param  string $message
     * @return void
     */
    private function _return_message($status, $message)
    {
        $result = array('status' => $status);
        if ($status)
        {
            $result['message'] = $message;
        }
        else
        {
            $result['id'] = $message;
        }
        exit(json_encode($result));
    }
}
