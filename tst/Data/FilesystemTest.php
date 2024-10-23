<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PrivateBin\Data\Filesystem;

class FilesystemTest extends TestCase
{
    private $_model;

    private $_path;

    private $_invalidPath;

    public function setUp(): void
    {
        /* Setup Routine */
        $this->_path        = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'privatebin_data';
        $this->_invalidPath = $this->_path . DIRECTORY_SEPARATOR . 'bar';
        $this->_model       = new Filesystem(array('dir' => $this->_path));
        if (!is_dir($this->_path)) {
            mkdir($this->_path);
        }
        if (!is_dir($this->_invalidPath)) {
            mkdir($this->_invalidPath);
        }
    }

    public function tearDown(): void
    {
        /* Tear Down Routine */
        chmod($this->_invalidPath, 0700);
        Helper::rmDir($this->_path);
    }

    public function testFileBasedDataStoreWorks()
    {
        $this->_model->delete(Helper::getPasteId());

        // storing pastes
        $paste = Helper::getPaste(2, array('expire_date' => 1344803344));
        $this->assertFalse($this->_model->exists(Helper::getPasteId()), 'paste does not yet exist');
        $this->assertTrue($this->_model->create(Helper::getPasteId(), $paste), 'store new paste');
        $this->assertTrue($this->_model->exists(Helper::getPasteId()), 'paste exists after storing it');
        $this->assertFalse($this->_model->create(Helper::getPasteId(), $paste), 'unable to store the same paste twice');
        $this->assertEquals($paste, $this->_model->read(Helper::getPasteId()));

        // storing comments
        $this->assertFalse($this->_model->existsComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getCommentId()), 'comment does not yet exist');
        $this->assertTrue($this->_model->createComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getCommentId(), Helper::getComment()), 'store comment');
        $this->assertTrue($this->_model->existsComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getCommentId()), 'comment exists after storing it');
        $this->assertFalse($this->_model->createComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getCommentId(), Helper::getComment()), 'unable to store the same comment twice');
        $comment             = Helper::getComment();
        $comment['id']       = Helper::getCommentId();
        $comment['parentid'] = Helper::getPasteId();
        $this->assertEquals(
            array($comment['meta']['created'] => $comment),
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
        $original                        = $paste = Helper::getPasteWithAttachment(1, array('expire_date' => 1344803344));
        $paste['meta']['attachment']     = $paste['attachment'];
        $paste['meta']['attachmentname'] = $paste['attachmentname'];
        unset($paste['attachment'], $paste['attachmentname']);
        $this->assertFalse($this->_model->exists(Helper::getPasteId()), 'paste does not yet exist');
        $this->assertTrue($this->_model->create(Helper::getPasteId(), $paste), 'store new paste');
        $this->assertTrue($this->_model->exists(Helper::getPasteId()), 'paste exists after storing it');
        $this->assertFalse($this->_model->create(Helper::getPasteId(), $paste), 'unable to store the same paste twice');
        $this->assertEquals($original, $this->_model->read(Helper::getPasteId()));
    }

    /**
     * pastes a-g are expired and should get deleted, x never expires and y-z expire in an hour
     */
    public function testPurge()
    {
        mkdir($this->_path . DIRECTORY_SEPARATOR . '00', 0777, true);
        $expired = Helper::getPaste(2, array('expire_date' => 1344803344));
        $paste   = Helper::getPaste(2, array('expire_date' => time() + 3600));
        $keys    = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'x', 'y', 'z');
        $ids     = array();
        foreach ($keys as $key) {
            $ids[$key] = hash('fnv164', $key);
            $this->assertFalse($this->_model->exists($ids[$key]), "paste $key does not yet exist");
            if (in_array($key, array('x', 'y', 'z'))) {
                $this->assertTrue($this->_model->create($ids[$key], $paste), "store $key paste");
            } elseif ($key === 'x') {
                $this->assertTrue($this->_model->create($ids[$key], Helper::getPaste()), "store $key paste");
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

    public function testErrorDetection()
    {
        $this->_model->delete(Helper::getPasteId());
        $paste = Helper::getPaste(2, array('expire' => "Invalid UTF-8 sequence: \xB1\x31"));
        $this->assertFalse($this->_model->exists(Helper::getPasteId()), 'paste does not yet exist');
        $this->assertFalse($this->_model->create(Helper::getPasteId(), $paste), 'unable to store broken paste');
        $this->assertFalse($this->_model->exists(Helper::getPasteId()), 'paste does still not exist');
        $this->assertFalse($this->_model->setValue('foo', 'non existing namespace'), 'rejects setting value in non existing namespace');
    }

    public function testCommentErrorDetection()
    {
        $this->_model->delete(Helper::getPasteId());
        $comment = Helper::getComment(1, array('nickname' => "Invalid UTF-8 sequence: \xB1\x31"));
        $this->assertFalse($this->_model->exists(Helper::getPasteId()), 'paste does not yet exist');
        $this->assertTrue($this->_model->create(Helper::getPasteId(), Helper::getPaste()), 'store new paste');
        $this->assertTrue($this->_model->exists(Helper::getPasteId()), 'paste exists after storing it');
        $this->assertFalse($this->_model->existsComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getCommentId()), 'comment does not yet exist');
        $this->assertFalse($this->_model->createComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getCommentId(), $comment), 'unable to store broken comment');
        $this->assertFalse($this->_model->existsComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getCommentId()), 'comment does still not exist');
    }

    public function testOldFilesGetConverted()
    {
        // generate 10 (default purge batch size) pastes in the old format
        $paste     = Helper::getPaste();
        $comment   = Helper::getComment();
        $commentid = Helper::getCommentId();
        $ids       = array();
        for ($i = 0, $max = 10; $i < $max; ++$i) {
            // PHPs mt_rand only supports 32 bit or up 0x7fffffff on 64 bit systems to be precise :-/
            $dataid = str_pad(dechex(mt_rand(0, mt_getrandmax())), 8, '0', STR_PAD_LEFT) .
                str_pad(dechex(mt_rand(0, mt_getrandmax())), 8, '0', STR_PAD_LEFT);
            $storagedir = $this->_path . DIRECTORY_SEPARATOR . substr($dataid, 0, 2) .
                DIRECTORY_SEPARATOR . substr($dataid, 2, 2) . DIRECTORY_SEPARATOR;
            $ids[$dataid] = $storagedir;

            if (!is_dir($storagedir)) {
                mkdir($storagedir, 0700, true);
            }
            file_put_contents($storagedir . $dataid, json_encode($paste));

            $storagedir .= $dataid . '.discussion' . DIRECTORY_SEPARATOR;
            if (!is_dir($storagedir)) {
                mkdir($storagedir, 0700, true);
            }
            file_put_contents($storagedir . $dataid . '.' . $commentid . '.' . $dataid, json_encode($comment));
        }
        // check that all 10 pastes were converted after the purge
        $this->_model->purge(10);
        foreach ($ids as $dataid => $storagedir) {
            $dataid = (string) $dataid; // undue potential key cast, see https://www.php.net/manual/en/language.types.array.php
            $this->assertFileExists($storagedir . $dataid . '.php', "paste $dataid exists in new format");
            $this->assertFileDoesNotExist($storagedir . $dataid, "old format paste $dataid got removed");
            $this->assertTrue($this->_model->exists($dataid), "paste $dataid exists");
            $this->assertEquals($this->_model->read($dataid), $paste, "paste $dataid wasn't modified in the conversion");

            $storagedir .= $dataid . '.discussion' . DIRECTORY_SEPARATOR;
            $this->assertFileExists($storagedir . $dataid . '.' . $commentid . '.' . $dataid . '.php', "comment of $dataid exists in new format");
            $this->assertFileDoesNotExist($storagedir . $dataid . '.' . $commentid . '.' . $dataid, "old format comment of $dataid got removed");
            $this->assertTrue($this->_model->existsComment($dataid, $dataid, $commentid), "comment in paste $dataid exists");
            $comment             = $comment;
            $comment['id']       = $commentid;
            $comment['parentid'] = $dataid;
            $this->assertEquals($this->_model->readComments($dataid), array($comment['meta']['created'] => $comment), "comment of $dataid wasn't modified in the conversion");
        }
    }
}
