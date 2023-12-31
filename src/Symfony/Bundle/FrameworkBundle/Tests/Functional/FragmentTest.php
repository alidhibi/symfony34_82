<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Tests\Functional;

class FragmentTest extends AbstractWebTestCase
{
    /**
     * @dataProvider getConfigs
     */
    public function testFragment(bool $insulate): void
    {
        $client = $this->createClient(['test_case' => 'Fragment', 'root_config' => 'config.yml']);
        if ($insulate) {
            $client->insulate();
        }

        $client->request('GET', '/fragment_home');

        $this->assertEquals('bar txt--html--es--fr', $client->getResponse()->getContent());
    }

    public function getConfigs(): array
    {
        return [
            [false],
            [true],
        ];
    }
}
