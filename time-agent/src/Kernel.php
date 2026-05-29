<?php
namespace App;

use Symfony\Component\Console\Application;
use App\Command\MonitorCommand;

class Kernel
{
    public function run(): void
    {
        $app = new Application('Time Agent', '1.0.0');
        $app->add(new MonitorCommand());
        $app->setDefaultCommand('monitor', true);
        $app->run();
    }
}
