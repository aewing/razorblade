<?php

namespace AEwing\RazorBlade\Bundle;
use AEwing\RazorBlade\Bundle\Command\ScrapeCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class RazorBladeBundle extends Bundle
{
  public function registerCommands(Application $application)
  {
    $application->add(new ScrapeCommand());
  }
}
