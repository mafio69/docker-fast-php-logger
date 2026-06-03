<?php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class MonitorCommand extends Command
{
    protected static $defaultName = 'monitor';
    protected static $defaultDescription = 'Monitoruje Time Doctor i pokazuje alerty';

    private string $logFile;
    private string $mode = 'work';
    private bool $wasLocked = false;
    private int $lockStart = 0;
    private bool $alertTriggered = false;

    protected function configure(): void
    {
        $this->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logFile = __DIR__ . '/../../var/agent.log';
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir) && !mkdir($logDir, 0777, true)) {
            $output->writeln('<error>Failed to create log directory: ' . $logDir . '</error>');
            return Command::FAILURE;
        }
        
        $this->log('=== Time Agent START (Symfony) ===');
        
        // Sprawdź/pytaj o tryb
        $this->initializeMode();
        
        // Główna pętla
        while (true) {
            if (file_exists('/tmp/timedoctor-bypass')) {
                sleep(30);
                continue;
            }
            
            $this->checkScreenLock();
            $this->checkTimeDoctor();
            
            sleep(5);
        }
        
        return Command::SUCCESS;
    }
    
    private function initializeMode(): void
    {
        if (!file_exists('/tmp/timedoctor-session-mode')) {
            $process = new Process([
                'zenity', '--list', '--radiolist',
                '--title=🕐 Time Agent',
                '--text=Czy jesteś w godzinach pracy?',
                '--column=', '--column=Opcja', '--column=Opis',
                'TRUE', 'work', 'Tak, pracuję (monitorowanie aktywne)',
                'FALSE', 'private', 'Nie, prywatnie (alarmy wyłączone)',
                '--width=400', '--height=250'
            ]);
            $process->run();

            if (!$process->isSuccessful()) {
                $this->log('zenity dialog failed: ' . $process->getErrorOutput());
            }

            $result = trim($process->getOutput());
            
            $this->mode = $result === 'private' ? 'private' : 'work';
            if (file_put_contents('/tmp/timedoctor-session-mode', $this->mode) === false) {
                $this->log('Failed to persist session mode');
            }
            
            if ($this->mode === 'private') {
                touch('/tmp/timedoctor-bypass');
            }
            
            $this->log("Tryb: {$this->mode}");
        } else {
            $content = file_get_contents('/tmp/timedoctor-session-mode');
            if ($content === false) {
                $this->log('Failed to read session mode file, defaulting to work');
                $this->mode = 'work';
            } else {
                $this->mode = trim($content);
            }
        }
    }
    
    private function checkScreenLock(): void
    {
        $process = new Process(['loginctl', 'show-session', '--property=IdleHint', '--value']);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->log('loginctl failed: ' . trim($process->getErrorOutput()));
            return;
        }

        $isLocked = trim($process->getOutput()) === 'yes';
        
        if ($isLocked && !$this->wasLocked) {
            $this->log('Przerwa rozpoczęta');
            $this->lockStart = time();
            $this->wasLocked = true;
            $this->alertTriggered = false;
        } elseif (!$isLocked && $this->wasLocked) {
            $duration = time() - $this->lockStart;
            
            if ($duration > 180) {
                $this->log("Powrót z przerwy ({$duration}s)");
                
                if (!$this->isTdRunning() && $this->mode === 'work') {
                    $this->showAlert('return');
                    $this->alertTriggered = true;
                }
            }
            
            $this->wasLocked = false;
        }
    }
    
    private function checkTimeDoctor(): void
    {
        $tdRunning = $this->isTdRunning();
        
        if (!$tdRunning && $this->mode === 'work' && !$this->alertTriggered) {
            $this->log('Praca bez TD - alert');
            $this->showAlert('working');
            $this->alertTriggered = true;
        }
        
        if ($tdRunning && $this->alertTriggered) {
            $this->log('TD uruchomiony');
            $this->alertTriggered = false;
        }
    }
    
    private function isTdRunning(): bool
    {
        $process = new Process(['pgrep', '-i', 'timedoctor']);
        $process->run();
        return $process->isSuccessful();
    }
    
    private function showAlert(string $reason): void
    {
        $isWorkHours = $this->isWorkingHours();
        
        if ($isWorkHours) {
            $style = 'error';
            $title = '⏱️ UWAGA - Time Doctor';
            $header = '⚠️ Time Doctor nie działa!';
            $subheader = 'Jesteś w godzinach pracy (7-17)';
        } else {
            $style = 'warning';
            $title = '🌙 Time Doctor - Po godzinach';
            $header = '🌙 Pracujesz po godzinach';
            $subheader = 'To nie jest standardowy czas pracy';
        }
        
        $tomorrow6am = strtotime('tomorrow 06:00');
        
        $process = new Process([
            'zenity', "--{$style}",
            '--title', $title,
            '--text', "<span size='x-large' weight='bold'>{$header}</span>\n\n<span size='large'>{$subheader}</span>",
            '--ok-label', '✅ Włączyłem Time Doctor',
            '--extra-button', '🔕 Wyłącz do jutra 6:00',
            '--width', '520', '--height', '320'
        ]);

        try {
            $process->start();
        } catch (\Throwable $e) {
            $this->log('Failed to show alert dialog: ' . $e->getMessage());
            return;
        }
        
        $this->log('Pokazano okno alertu');
    }
    
    private function isWorkingHours(): bool
    {
        $hour = (int) date('H');
        $weekday = (int) date('N');
        return $weekday <= 5 && $hour >= 7 && $hour < 17;
    }
    
    private function log(string $message): void
    {
        $line = date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL;
        file_put_contents($this->logFile, $line, FILE_APPEND | LOCK_EX);
    }
}
