<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

class AdministrationOptionsTest extends TestCase
{
    public function testAcceptsSingleAction()
    {
        [$exitCode, $output] = $this->runAdministration('--help');

        $this->assertSame(0, $exitCode, $output);
        $this->assertStringContainsString('Usage:', $output);
    }

    /**
     * @dataProvider invalidArgumentsProvider
     */
    public function testRejectsAmbiguousArguments($arguments)
    {
        [$exitCode, $output] = $this->runAdministration($arguments);

        $this->assertSame(3, $exitCode, $output);
        $this->assertStringContainsString('Error: unsupported arguments given', $output);
    }

    private function runAdministration($arguments)
    {
        $command = escapeshellarg(PHP_BINARY) . ' ' .
            escapeshellarg(realpath(PATH . 'bin' . DIRECTORY_SEPARATOR . 'administration')) .
            ' ' . $arguments . ' 2>&1';
        exec($command, $output, $exitCode);

        return [$exitCode, implode(PHP_EOL, $output)];
    }

    public function invalidArgumentsProvider()
    {
        return [
            'trailing positional argument' => ['--help unexpected'],
            'multiple actions'             => ['--help --delete-all'],
        ];
    }
}
