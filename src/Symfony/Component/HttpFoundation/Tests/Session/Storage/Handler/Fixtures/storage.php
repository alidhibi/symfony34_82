<?php

require __DIR__.'/common.inc';

use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;

$storage = new NativeSessionStorage();
$storage->setSaveHandler(new TestSessionHandler());
$flash = new FlashBag();
$storage->registerBag($flash);
$storage->start();

$flash->add('foo', 'bar');

print_r($flash->get('foo'));
echo $_SESSION === [] ? '$_SESSION is empty' : '$_SESSION is not empty';
echo "\n";

$storage->save();

echo $_SESSION === [] ? '$_SESSION is empty' : '$_SESSION is not empty';

ob_start(static fn($buffer): array|string => str_replace(session_id(), 'random_session_id', $buffer));
