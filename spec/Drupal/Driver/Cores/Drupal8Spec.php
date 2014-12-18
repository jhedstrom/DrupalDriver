<?php

namespace spec\Drupal\Driver\Cores;

use Drupal\Component\Utility\RandomInterface;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class Drupal8Spec extends ObjectBehavior
{
    function let(RandomInterface $random)
    {
        $this->beConstructedWith('path', 'http://www.example.com', $random);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Drupal\Driver\Cores\Drupal8');
    }
}
