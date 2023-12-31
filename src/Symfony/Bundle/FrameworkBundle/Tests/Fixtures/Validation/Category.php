<?php

namespace Symfony\Bundle\FrameworkBundle\Tests\Fixtures\Validation;

use Symfony\Component\Validator\Constraints as Assert;

class Category
{
    final const NAME_PATTERN = '/\w+/';

    public $id;

    /**
     * @Assert\Type("string")
     */
    public $name;
}
