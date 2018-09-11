<?php

/*
 * This file is part of the slince/yark package.
 *
 * (c) Slince <taosikai@yeah.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Slince\Yark;

use Symfony\Component\Console\Application as BaseApplication;

class Application extends BaseApplication
{
    /**
     * @var string
     */
    const LOGO = <<<EOT
__   __ _    ____  _  __
\ \ / // \  |  _ \| |/ /
 \ V // _ \ | |_) | ' /
  | |/ ___ \|  _ <| . \
  |_/_/   \_\_| \_\_|\_\
EOT;

    public function __construct()
    {
        parent::__construct('Yark', '0.0.1');
    }

    /**
     * {@inheritdoc}
     */
    public function getHelp()
    {
        return parent::getHelp() . static::LOGO;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultCommands()
    {
        return array_merge([
            new Command\CSVSplitCommand()
        ], parent::getDefaultCommands());
    }
}