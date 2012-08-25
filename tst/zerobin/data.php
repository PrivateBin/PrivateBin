<?php
class zerobin_dataTest extends PHPUnit_Framework_TestCase
{
    private static $pasteid = '501f02e9eeb8bcec';

    private static $paste = array(
        'data' => '{"iv":"EN39/wd5Nk8HAiSG2K5AsQ","salt":"QKN1DBXe5PI","ct":"8hA83xDdXjD7K2qfmw5NdA"}',
        'meta' => array(
            'postdate' => 1344803344,
            'expire_date' => 1344803644,
            'opendiscussion' => true,
        ),
    );

    private static $commentid = 'c47efb4741195f42';

    private static $comment = array(
        'data' => '{"iv":"Pd4pOKWkmDTT9uPwVwd5Ag","salt":"ZIUhFTliVz4","ct":"6nOCU3peNDclDDpFtJEBKA"}',
        'meta' => array(
            'nickname' => '{"iv":"76MkAtOGC4oFogX/aSMxRA","salt":"ZIUhFTliVz4","ct":"b6Ae/U1xJdsX/+lATud4sQ"}',
            'vizhash' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAIAAACQkWg2AAABGUlEQVQokWOsl5/94983CNKQMjnxaOePf98MeKwPfNjkLZ3AgARab6b9+PeNEVnDj3/ff/z7ZiHnzsDA8Pv7H2TVPJw8EAYLAwb48OaVgIgYKycLsrYv378wMDB8//qdCVMDRA9EKSsnCwRBxNsepaLboMFlyMDAICAi9uHNK24GITQ/MDAwoNhgIGMLtwGrzegaLjw5jMz9+vUdnN17uwDCQDhJgk0O07yvX9+teDX1x79v6DYIsIjgcgMaYGFgYOBg4kJx2JejkAiBxAw+PzAwMNz4dp6wDXDw4MdNNOl0rWYsNkD89OLXI/xmo9sgzatJjAYmBgYGDiauD3/ePP18nVgb4MF89+M5ZX6js293wUMpnr8KTQMAxsCJnJ30apMAAAAASUVORK5CYII=',
            'postdate' => 1344803528,
        ),
    );

    private $_model;

    private $_path;

    public function setUp()
    {
        /* Setup Routine */
        $this->_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'zerobin_data';
        $this->_model = zerobin_data::getInstance(array('dir' => $this->_path));
    }

    public function tearDown()
    {
        /* Tear Down Routine */
        helper::rmdir($this->_path);
    }

    public function testFileBasedDataStoreWorks()
    {
        // storing pastes
        $this->assertFalse($this->_model->exists(self::$pasteid), 'paste does not yet exist');
        $this->assertTrue($this->_model->create(self::$pasteid, self::$paste), 'store new paste');
        $this->assertTrue($this->_model->exists(self::$pasteid), 'paste exists after storing it');
        $this->assertFalse($this->_model->create(self::$pasteid, self::$paste), 'unable to store the same paste twice');
        $this->assertEquals(json_decode(json_encode(self::$paste)), $this->_model->read(self::$pasteid));

        // storing comments
        $this->assertFalse($this->_model->existsComment(self::$pasteid, self::$pasteid, self::$commentid), 'comment does not yet exist');
        $this->assertTrue($this->_model->createComment(self::$pasteid, self::$pasteid, self::$commentid, self::$comment) !== false, 'store comment');
        $this->assertTrue($this->_model->existsComment(self::$pasteid, self::$pasteid, self::$commentid), 'comment exists after storing it');
        $comment = json_decode(json_encode(self::$comment));
        $comment->meta->commentid = self::$commentid;
        $comment->meta->parentid = self::$pasteid;
        $this->assertEquals(
            array($comment->meta->postdate => $comment),
            $this->_model->readComments(self::$pasteid)
        );

        // deleting pastes
        $this->_model->delete(self::$pasteid);
        $this->assertFalse($this->_model->exists(self::$pasteid), 'paste successfully deleted');
        $this->assertFalse($this->_model->existsComment(self::$pasteid, self::$pasteid, self::$commentid), 'comment was deleted with paste');
        $this->assertFalse($this->_model->read(self::$pasteid), 'paste can no longer be found');
    }
}
