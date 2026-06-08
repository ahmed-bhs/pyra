<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Tests\Infrastructure\Console;

use AhmedBhs\Pyra\Infrastructure\Console\InitCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class InitCommandTest extends TestCase
{
    private string $configPath;

    protected function setUp(): void
    {
        $this->configPath = sys_get_temp_dir().'/pyra-init-'.uniqid().'.yaml';
    }

    protected function tearDown(): void
    {
        if (is_file($this->configPath)) {
            unlink($this->configPath);
        }
    }

    public function testWritesAConfigFile(): void
    {
        $tester = new CommandTester(new InitCommand());

        $exitCode = $tester->execute(['--config' => $this->configPath]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertFileExists($this->configPath);
        self::assertStringContainsString('pyra:', (string) file_get_contents($this->configPath));
    }

    public function testRefusesToOverwriteWithoutForce(): void
    {
        file_put_contents($this->configPath, "existing\n");
        $tester = new CommandTester(new InitCommand());

        $exitCode = $tester->execute(['--config' => $this->configPath]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertSame("existing\n", (string) file_get_contents($this->configPath));
    }

    public function testOverwritesWithForce(): void
    {
        file_put_contents($this->configPath, "existing\n");
        $tester = new CommandTester(new InitCommand());

        $exitCode = $tester->execute(['--config' => $this->configPath, '--force' => true]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('pyra:', (string) file_get_contents($this->configPath));
    }
}
