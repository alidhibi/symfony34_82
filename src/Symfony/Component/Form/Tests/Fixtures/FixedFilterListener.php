<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Form\Tests\Fixtures;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class FixedFilterListener implements EventSubscriberInterface
{
    private array $mapping;

    public function __construct(array $mapping)
    {
        $this->mapping = ['preSubmit' => [], 'onSubmit' => [], 'preSetData' => [], ...$mapping];
    }

    public function preSubmit(FormEvent $event): void
    {
        $data = $event->getData();

        if (isset($this->mapping['preSubmit'][$data])) {
            $event->setData($this->mapping['preSubmit'][$data]);
        }
    }

    public function onSubmit(FormEvent $event): void
    {
        $data = $event->getData();

        if (isset($this->mapping['onSubmit'][$data])) {
            $event->setData($this->mapping['onSubmit'][$data]);
        }
    }

    public function preSetData(FormEvent $event): void
    {
        $data = $event->getData();

        if (isset($this->mapping['preSetData'][$data])) {
            $event->setData($this->mapping['preSetData'][$data]);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SUBMIT => 'preSubmit',
            FormEvents::SUBMIT => 'onSubmit',
            FormEvents::PRE_SET_DATA => 'preSetData',
        ];
    }
}
