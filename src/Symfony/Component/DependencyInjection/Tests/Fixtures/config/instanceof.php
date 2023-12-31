<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\FooService;
use Symfony\Component\DependencyInjection\Tests\Fixtures\Prototype;

return static function (ContainerConfigurator $c) : void {
    $s = $c->services();
    $s->instanceof(Prototype\Foo::class)
        ->property('p', 0)
        ->call('setFoo', [ref('foo')])
        ->tag('tag', ['k' => 'v'])
        ->share(false)
        ->lazy()
        ->configurator('c')
        ->property('p', 1);
    $s->load(Prototype::class.'\\', '../Prototype')->exclude('../Prototype/*/*');
    $s->set('foo', FooService::class);
};
