<?php
/**
 * slince yark
 * @author Tao <taosikai@yeah.net>
 */
namespace Slince\Yark;

use Symfony\Component\Console\Application;

class CommandUI
{
    /**
     * 创建command
     * @return array
     */
    static function createCommands()
    {
        return [
            new YarkCommand(),
        ];
    }

    /**
     * command应用主入口
     * @throws \Exception
     */
    static function main()
    {
        $application = new Application();
        $application->addCommands(self::createCommands());
        $application->setDefaultCommand(YarkCommand::NAME);
        $application->setAutoExit(true);
        $application->run();
    }
}