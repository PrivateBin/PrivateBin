<?php

use PrivateBin\Data\Filesystem;

class FilesystemTest extends PHPUnit_Framework_TestCase
{
    private $_model;

    private $_path;

    public function setUp()
    {
        /* Setup Routine */
        $this->_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'privatebin_data';
        $this->_model = Filesystem::getInstance(array('dir' => $this->_path));
    }

    public function tearDown()
    {
        /* Tear Down Routine */
        Helper::rmDir($this->_path);
    }

    public function testFileBasedDataStoreWorks()
    {
        $this->_model->delete(Helper::getPasteId());

        // storing pastes
        $paste = Helper::getPaste(array('expire_date' => 1344803344));
        $this->assertFalse($this->_model->exists(Helper::getPasteId()), 'paste does not yet exist');
        $this->assertTrue($this->_model->create(Helper::getPasteId(), $paste), 'store new paste');
        $this->assertTrue($this->_model->exists(Helper::getPasteId()), 'paste exists after storing it');
        $this->assertFalse($this->_model->create(Helper::getPasteId(), $paste), 'unable to store the same paste twice');
        $this->assertEquals(json_decode(json_encode($paste)), $this->_model->read(Helper::getPasteId()));

        // storing comments
        $this->assertFalse($this->_model->existsComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getCommentId()), 'comment does not yet exist');
        $this->assertTrue($this->_model->createComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getCommentId(), Helper::getComment()), 'store comment');
        $this->assertTrue($this->_model->existsComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getCommentId()), 'comment exists after storing it');
        $comment = json_decode(json_encode(Helper::getComment()));
        $comment->id = Helper::getCommentId();
        $comment->parentid = Helper::getPasteId();
        $this->assertEquals(
            array($comment->meta->postdate => $comment),
            $this->_model->readComments(Helper::getPasteId())
        );

        // deleting pastes
        $this->_model->delete(Helper::getPasteId());
        $this->assertFalse($this->_model->exists(Helper::getPasteId()), 'paste successfully deleted');
        $this->assertFalse($this->_model->existsComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getCommentId()), 'comment was deleted with paste');
        $this->assertFalse($this->_model->read(Helper::getPasteId()), 'paste can no longer be found');
    }

    public function testFileBasedAttachmentStoreWorks()
    {
        $this->_model->delete(Helper::getPasteId());
        $original = $paste = Helper::getPasteWithAttachment(array('expire_date' => 1344803344));
        $paste['meta']['attachment'] = $paste['attachment'];
        $paste['meta']['attachmentname'] = $paste['attachmentname'];
        unset($paste['attachment'], $paste['attachmentname']);
        $this->assertFalse($this->_model->exists(Helper::getPasteId()), 'paste does not yet exist');
        $this->assertTrue($this->_model->create(Helper::getPasteId(), $paste), 'store new paste');
        $this->assertTrue($this->_model->exists(Helper::getPasteId()), 'paste exists after storing it');
        $this->assertFalse($this->_model->create(Helper::getPasteId(), $paste), 'unable to store the same paste twice');
        $this->assertEquals(json_decode(json_encode($original)), $this->_model->read(Helper::getPasteId()));
    }

    public function testPurge()
    {
        mkdir($this->_path . DIRECTORY_SEPARATOR . '00', 0777, true);
        $expired = Helper::getPaste(array('expire_date' => 1344803344));
        $paste = Helper::getPaste(array('expire_date' => time() + 3600));
        $keys = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'x', 'y', 'z');
        $ids = array();
        foreach ($keys as $key) {
            $ids[$key] = substr(md5($key), 0, 16);
            $this->assertFalse($this->_model->exists($ids[$key]), "paste $key does not yet exist");
            if (in_array($key, array('x', 'y', 'z'))) {
                $this->assertTrue($this->_model->create($ids[$key], $paste), "store $key paste");
            } else {
                $this->assertTrue($this->_model->create($ids[$key], $expired), "store $key paste");
            }
            $this->assertTrue($this->_model->exists($ids[$key]), "paste $key exists after storing it");
        }
        $this->_model->purge(10);
        foreach ($ids as $key => $id) {
            if (in_array($key, array('x', 'y', 'z'))) {
                $this->assertTrue($this->_model->exists($id), "paste $key exists after purge");
                $this->_model->delete($id);
            } else {
                $this->assertFalse($this->_model->exists($id), "paste $key was purged");
            }
        }
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 90
     */
    public function testErrorDetection()
    {
        $this->_model->delete(Helper::getPasteId());
        $paste = Helper::getPaste(array('formatter' => "Invalid UTF-8 sequence: \xB1\x31"));
        $this->assertFalse($this->_model->exists(Helper::getPasteId()), 'paste does not yet exist');
        $this->assertFalse($this->_model->create(Helper::getPasteId(), $paste), 'unable to store broken paste');
        $this->assertFalse($this->_model->exists(Helper::getPasteId()), 'paste does still not exist');
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 90
     */
    public function testCommentErrorDetection()
    {
        $this->_model->delete(Helper::getPasteId());
        $comment = Helper::getComment(array('formatter' => "Invalid UTF-8 sequence: \xB1\x31"));
        $this->assertFalse($this->_model->exists(Helper::getPasteId()), 'paste does not yet exist');
        $this->assertTrue($this->_model->create(Helper::getPasteId(), Helper::getPaste()), 'store new paste');
        $this->assertTrue($this->_model->exists(Helper::getPasteId()), 'paste exists after storing it');
        $this->assertFalse($this->_model->existsComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getCommentId()), 'comment does not yet exist');
        $this->assertFalse($this->_model->createComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getCommentId(), $comment), 'unable to store broken comment');
        $this->assertFalse($this->_model->existsComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getCommentId()), 'comment does still not exist');
    }
}
