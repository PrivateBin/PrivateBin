<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PrivateBin\Configuration;
use PrivateBin\Controller;
use PrivateBin\Data\Filesystem;
use PrivateBin\FormatV2;
use PrivateBin\Persistence\ServerSalt;
use PrivateBin\Persistence\TrafficLimiter;
use PrivateBin\Request;

class TicketTest extends TestCase
{
    protected $_data;

    protected $_path;

    public function setUp(): void
    {
        $this->_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'privatebin_data';
        $this->_data = new Filesystem(['dir' => $this->_path]);
        ServerSalt::setStore($this->_data);
        TrafficLimiter::setStore($this->_data);
        $_POST = [];
        $_GET = [];
        $_SERVER = [];
        $options = parse_ini_file(CONF_SAMPLE, true);
        $options['model_options']['dir'] = $this->_path;
        $options['main']['onetimeticket'] = true;
        $options['traffic']['limit'] = 0;
        Helper::createIniFile(CONF, $options);
    }

    public function tearDown(): void
    {
        unlink(CONF);
        Helper::confRestore();
        Helper::rmDir($this->_path);
    }

    /**
     * enable or disable the feature in the configuration
     */
    private function setFeature(bool $enabled): void
    {
        $options = parse_ini_file(CONF, true);
        $options['main']['onetimeticket'] = $enabled;
        Helper::createIniFile(CONF, $options);
    }

    /**
     * build a POST body for a paste with the given meta
     */
    private function body(array $meta, array $adataOverride = []): string
    {
        $paste = Helper::getPastePost();
        $paste['meta'] = array_merge(['expire' => '1day'], $meta);
        // the paste ID derives from the ciphertext, so make each body unique
        $paste['ct'] = base64_encode(random_bytes(32));
        $paste['adata'][2] = 0; // discussion off by default, tickets require it
        foreach ($adataOverride as $index => $value) {
            $paste['adata'][$index] = $value;
        }
        return json_encode($paste);
    }

    /**
     * send a create request, optionally redeeming a ticket, returns the decoded response
     */
    private function post(string $body, ?string $ticket = null): array
    {
        $file = Helper::createTempFile();
        file_put_contents($file, $body);
        Request::setInputStream($file);
        $_GET = $ticket === null ? [] : ['ticket' => $ticket];
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '::1';
        ob_start();
        new Controller;
        $content = ob_get_contents();
        ob_end_clean();
        return json_decode($content, true);
    }

    private function issueTicket(): string
    {
        $response = $this->post($this->body(['ticket' => 1]));
        $this->assertEquals(0, $response['status'], 'ticket issued: ' . json_encode($response));
        return $response['id'];
    }

    public function testFormatAcceptsTicketMarkerOnly()
    {
        $paste = Helper::getPastePost();
        $paste['meta']['ticket'] = 1;
        $this->assertTrue(FormatV2::isValid($paste), 'ticket marker is valid');
        foreach ([0, 2, true, '1', null] as $value) {
            $paste['meta']['ticket'] = $value;
            $this->assertFalse(FormatV2::isValid($paste), 'invalid marker value ' . json_encode($value));
        }
        $paste['meta'] = ['expire' => '1day', 'other' => 1];
        $this->assertFalse(FormatV2::isValid($paste), 'other meta keys still rejected');
        $paste['meta'] = ['ticket' => 1];
        $this->assertFalse(FormatV2::isValid($paste), 'expire is still required');
    }

    public function testConfigurationDefaultsToDisabled()
    {
        unlink(CONF);
        $options = parse_ini_file(CONF_SAMPLE, true);
        $options['model_options']['dir'] = $this->_path;
        Helper::createIniFile(CONF, $options);
        $conf = new Configuration;
        $this->assertFalse($conf->getKey('onetimeticket'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testTicketCanBeRedeemedExactlyOnce()
    {
        $ticket = $this->issueTicket();
        $this->assertTrue($this->_data->exists($ticket), 'ticket stored');

        $first = $this->post($this->body([]), $ticket);
        $this->assertEquals(0, $first['status'], 'first use accepted: ' . json_encode($first));
        $this->assertFalse($this->_data->exists($ticket), 'ticket consumed');
        $this->assertTrue($this->_data->exists($first['id']), 'new paste stored');
        $stored = $this->_data->read($first['id']);
        $this->assertArrayNotHasKey('ticket', $stored['meta'], 'result is an ordinary paste');
    }

    /**
     * @runInSeparateProcess
     */
    public function testSecondUseOfTicketFails()
    {
        $ticket = $this->issueTicket();
        $first = $this->post($this->body([]), $ticket);
        $this->assertEquals(0, $first['status']);
        $second = $this->post($this->body([], [2 => 'plaintext']), $ticket);
        $this->assertEquals(1, $second['status'], 'second use refused');
    }

    /**
     * @runInSeparateProcess
     */
    public function testUnknownTicketIsRejected()
    {
        $response = $this->post($this->body([]), Helper::getRandomId());
        $this->assertEquals(1, $response['status']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testMalformedTicketIdIsRejected()
    {
        foreach (['../x', 'zz', 'abc', ''] as $id) {
            $response = $this->post($this->body([]), $id);
            $this->assertEquals(1, $response['status'], 'rejects ' . $id);
        }
    }

    /**
     * @runInSeparateProcess
     */
    public function testOrdinaryPasteIsNotATicket()
    {
        $normal = $this->post($this->body([]));
        $this->assertEquals(0, $normal['status']);
        $response = $this->post($this->body([]), $normal['id']);
        $this->assertEquals(1, $response['status'], 'normal paste cannot be redeemed');
        $this->assertTrue($this->_data->exists($normal['id']), 'normal paste survives the attempt');
    }

    /**
     * @runInSeparateProcess
     */
    public function testTicketCannotMintTickets()
    {
        $ticket = $this->issueTicket();
        $response = $this->post($this->body(['ticket' => 1]), $ticket);
        $this->assertEquals(1, $response['status']);
        $this->assertTrue($this->_data->exists($ticket), 'ticket not consumed by a refused request');
    }

    /**
     * @runInSeparateProcess
     */
    public function testInvalidPasteDoesNotConsumeTicket()
    {
        $ticket = $this->issueTicket();
        $response = $this->post('{"broken":', $ticket);
        $this->assertEquals(1, $response['status']);
        $this->assertTrue($this->_data->exists($ticket), 'ticket still there');
    }

    /**
     * @runInSeparateProcess
     */
    public function testTicketNeedsExpiration()
    {
        $paste = Helper::getPastePost();
        $paste['meta'] = ['expire' => 'never', 'ticket' => 1];
        $response = $this->post(json_encode($paste));
        $this->assertEquals(1, $response['status'], 'never-expiring ticket refused');
    }

    /**
     * @runInSeparateProcess
     */
    public function testTicketRejectsBurnAndDiscussion()
    {
        $this->assertEquals(1, $this->post($this->body(['ticket' => 1], [2 => 1]))['status'], 'discussion');
        $this->assertEquals(1, $this->post($this->body(['ticket' => 1], [3 => 1]))['status'], 'burn');
    }

    /**
     * @runInSeparateProcess
     */
    public function testExpiredTicketIsRejectedAndRemoved()
    {
        $ticket = $this->issueTicket();
        $stored = $this->_data->read($ticket);
        $stored['meta']['expire_date'] = time() - 10;
        $this->_data->delete($ticket);
        $this->_data->create($ticket, $stored);
        $response = $this->post($this->body([]), $ticket);
        $this->assertEquals(1, $response['status']);
        $this->assertFalse($this->_data->exists($ticket), 'expired ticket removed');
    }

    /**
     * @runInSeparateProcess
     */
    public function testCommentsCannotUseTicket()
    {
        $ticket = $this->issueTicket();
        $this->_data->create(Helper::getPasteId(), Helper::getPaste());
        $file = Helper::createTempFile();
        file_put_contents($file, Helper::getCommentJson());
        Request::setInputStream($file);
        $_GET = ['ticket' => $ticket];
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '::1';
        ob_start();
        new Controller;
        $response = json_decode(ob_get_clean(), true);
        $this->assertEquals(1, $response['status']);
        $this->assertTrue($this->_data->exists($ticket));
    }

    /**
     * @runInSeparateProcess
     */
    public function testFeatureDisabledRejectsEverything()
    {
        $ticket = $this->issueTicket();
        $this->setFeature(false);
        $this->assertEquals(1, $this->post($this->body(['ticket' => 1]))['status'], 'cannot issue');
        $this->assertEquals(1, $this->post($this->body([]), $ticket)['status'], 'cannot redeem');
        $this->assertTrue($this->_data->exists($ticket));
    }

    /**
     * @runInSeparateProcess
     */
    public function testTicketCannotCreateDiscussion()
    {
        $ticket = $this->issueTicket();
        $response = $this->post($this->body([], [2 => 1]), $ticket);
        $this->assertEquals(1, $response['status'], 'discussion refused');
        $this->assertTrue($this->_data->exists($ticket), 'ticket not consumed');
        $response = $this->post($this->body([]), $ticket);
        $this->assertEquals(0, $response['status'], 'ticket still usable without discussion');
    }
}
