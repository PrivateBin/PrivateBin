<?php
class zerobin_dataTest extends PHPUnit_Framework_TestCase
{
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
        $this->_model->delete(helper::getPasteId());

        // storing pastes
        $paste = helper::getPaste(array('expire_date' => 1344803344));
        $this->assertFalse($this->_model->exists(helper::getPasteId()), 'paste does not yet exist');
        $this->assertTrue($this->_model->create(helper::getPasteId(), $paste), 'store new paste');
        $this->assertTrue($this->_model->exists(helper::getPasteId()), 'paste exists after storing it');
        $this->assertFalse($this->_model->create(helper::getPasteId(), $paste), 'unable to store the same paste twice');
        $this->assertEquals(json_decode(json_encode($paste)), $this->_model->read(helper::getPasteId()));

        // storing comments
        $this->assertFalse($this->_model->existsComment(helper::getPasteId(), helper::getPasteId(), helper::getCommentId()), 'comment does not yet exist');
        $this->assertTrue($this->_model->createComment(helper::getPasteId(), helper::getPasteId(), helper::getCommentId(), helper::getComment()) !== false, 'store comment');
        $this->assertTrue($this->_model->existsComment(helper::getPasteId(), helper::getPasteId(), helper::getCommentId()), 'comment exists after storing it');
        $comment = json_decode(json_encode(helper::getComment()));
        $comment->id = helper::getCommentId();
        $comment->parentid = helper::getPasteId();
        $this->assertEquals(
            array($comment->meta->postdate => $comment),
            $this->_model->readComments(helper::getPasteId())
        );

        // deleting pastes
        $this->_model->delete(helper::getPasteId());
        $this->assertFalse($this->_model->exists(helper::getPasteId()), 'paste successfully deleted');
        $this->assertFalse($this->_model->existsComment(helper::getPasteId(), helper::getPasteId(), helper::getCommentId()), 'comment was deleted with paste');
        $this->assertFalse($this->_model->read(helper::getPasteId()), 'paste can no longer be found');
    }

    public function testFileBasedAttachmentStoreWorks()
    {
        $this->_model->delete(helper::getPasteId());
        $original = $paste = helper::getPasteWithAttachment(array('expire_date' => 1344803344));
        $paste['meta']['attachment'] = $paste['attachment'];
        $paste['meta']['attachmentname'] = $paste['attachmentname'];
        unset($paste['attachment'], $paste['attachmentname']);
        $this->assertFalse($this->_model->exists(helper::getPasteId()), 'paste does not yet exist');
        $this->assertTrue($this->_model->create(helper::getPasteId(), $paste), 'store new paste');
        $this->assertTrue($this->_model->exists(helper::getPasteId()), 'paste exists after storing it');
        $this->assertFalse($this->_model->create(helper::getPasteId(), $paste), 'unable to store the same paste twice');
        $this->assertEquals(json_decode(json_encode($original)), $this->_model->read(helper::getPasteId()));
    }

}
