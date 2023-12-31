<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\TwigBundle\Command;

use Symfony\Bridge\Twig\Command\LintCommand as BaseLintCommand;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Finder\Finder;

/**
 * Command that will validate your template syntax and output encountered errors.
 *
 * @author Marc Weistroff <marc.weistroff@sensiolabs.com>
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 */
final class LintCommand extends BaseLintCommand implements ContainerAwareInterface
{
    // BC to be removed in 4.0
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setHelp(
                $this->getHelp().<<<'EOF'

Or all template files in a bundle:

  <info>php %command.full_name% @AcmeDemoBundle</info>

EOF
            )
        ;
    }

    protected function findFiles($filename)
    {
        if (0 === strpos($filename, '@')) {
            $dir = $this->getApplication()->getKernel()->locateResource($filename);

            return Finder::create()->files()->in($dir)->name('*.twig');
        }

        return parent::findFiles($filename);
    }
}
