<?php

namespace spec\Drupal\Driver\Core;

use Drupal\Component\Utility\Random;

use Drupal\Driver\Core\CoreAuthenticationInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class CoreSpec extends ObjectBehavior
{
    function let(Random $random)
    {
        $this->beConstructedWith(__DIR__, 'http://www.example.com', $random);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Drupal\Driver\Core\Core');
    }

    function it_should_return_a_random_generator()
    {
        $this->getRandom()->shouldBeAnInstanceOf('Drupal\Component\Utility\Random');
    }

    function it_is_an_auth_core()
    {
        $this->shouldBeAnInstanceOf(CoreAuthenticationInterface::class);
    }
}
