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
     * Factory of instance models
     *
     * @access private
     * @var    model
     */
    private $_model;

    /**
     * request
     *
     * @access private
     * @var    request
     */
    private $_request;

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

        // load config from ini file
        $this->_init();

        switch ($this->_request->getOperation())
        {
            case 'create':
                $this->_create();
                break;
            case 'delete':
                $this->_delete(
                    $this->_request->getParam('pasteid'),
                    $this->_request->getParam('deletetoken')
                );
                break;
            case 'read':
                $this->_read($this->_request->getParam('pasteid'));
                break;
        }

        // output JSON or HTML
        if ($this->_request->isJsonApiCall())
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
        $this->_model = new model($this->_conf);
        $this->_request = new request;
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

        // Ensure last paste from visitors IP address was more than configured amount of seconds ago.
        trafficlimiter::setConfiguration($this->_conf);
        if (!trafficlimiter::canPass()) return $this->_return_message(
            1, i18n::_(
                'Please wait %d seconds between each post.',
                $this->_conf->getKey('limit', 'traffic')
            )
        );

        $data = $this->_request->getParam('data');
        $attachment = $this->_request->getParam('attachment');
        $attachmentname = $this->_request->getParam('attachmentname');

        // Ensure content is not too big.
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

        // The user posts a comment.
        $pasteid = $this->_request->getParam('pasteid');
        $parentid = $this->_request->getParam('parentid');
        if (!empty($pasteid) && !empty($parentid))
        {
            $paste = $this->_model->getPaste($pasteid);
            if ($paste->exists()) {
                try {
                    $comment = $paste->getComment($parentid);

                    $nickname = $this->_request->getParam('nickname');
                    if (!empty($nickname)) $comment->setNickname($nickname);

                    $comment->setData($data);
                    $comment->store();
                } catch(Exception $e) {
                    return $this->_return_message(1, $e->getMessage());
                }
                $this->_return_message(0, $comment->getId());
            }
            else
            {
                $this->_return_message(1, 'Invalid data.');
            }
        }
        // The user posts a standard paste.
        else
        {
            $paste = $this->_model->getPaste();
            try {
                $paste->setData($data);

                if (!empty($attachment))
                {
                    $paste->setAttachment($attachment);
                    if (!empty($attachmentname))
                        $paste->setAttachmentName($attachmentname);
                }

                $expire = $this->_request->getParam('expire');
                if (!empty($expire)) $paste->setExpiration($expire);

                $burnafterreading = $this->_request->getParam('burnafterreading');
                if (!empty($burnafterreading)) $paste->setBurnafterreading($burnafterreading);

                $opendiscussion = $this->_request->getParam('opendiscussion');
                if (!empty($opendiscussion)) $paste->setOpendiscussion($opendiscussion);

                $formatter = $this->_request->getParam('formatter');
                if (!empty($formatter)) $paste->setFormatter($formatter);

                $paste->store();
            } catch (Exception $e) {
                return $this->_return_message(1, $e->getMessage());
            }
            $this->_return_message(0, $paste->getId(), array('deletetoken' => $paste->getDeleteToken()));
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
        try {
            $paste = $this->_model->getPaste($dataid);
            if ($paste->exists())
            {
                // accessing this property ensures that the paste would be
                // deleted if it has already expired
                $burnafterreading = $paste->isBurnafterreading();
                if ($deletetoken == 'burnafterreading')
                {
                    if ($burnafterreading)
                    {
                        $paste->delete();
                        $this->_return_message(0, $dataid);
                    }
                    else
                    {
                        $this->_return_message(1, 'Paste is not of burn-after-reading type.');
                    }
                }
                else
                {
                    // Make sure the token is valid.
                    serversalt::setPath($this->_conf->getKey('dir', 'traffic'));
                    if (filter::slow_equals($deletetoken, $paste->getDeleteToken()))
                    {
                        // Paste exists and deletion token is valid: Delete the paste.
                        $paste->delete();
                        $this->_status = 'Paste was properly deleted.';
                    }
                    else
                    {
                        $this->_error = 'Wrong deletion token. Paste was not deleted.';
                    }
                }
            }
            else
            {
                $this->_error = self::GENERIC_ERROR;
            }
        } catch (Exception $e) {
            $this->_error = $e->getMessage();
        }
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
        try {
            $paste = $this->_model->getPaste($dataid);
            if ($paste->exists())
            {
                // The paste itself is the first in the list of encrypted messages.
                $messages = array_merge(
                    array($paste->get()),
                    $paste->getComments()
                );
                $this->_data = json_encode($messages);
            }
            else
            {
                $this->_error = self::GENERIC_ERROR;
            }
        } catch (Exception $e) {
            $this->_error = $e->getMessage();
        }

        if ($this->_request->isJsonApiCall())
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
            $result['url'] = (
                array_key_exists('REQUEST_URI', $_SERVER) ? $_SERVER['REQUEST_URI'] : '/'
            ) . '?' . $message;
            $result['@context'] = 'js/paste.jsonld';
        }
        $result += $other;
        $this->_json = json_encode($result);
    }
}
