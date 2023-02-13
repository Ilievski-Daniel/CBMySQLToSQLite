<?php

namespace MysqlToSqlite;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class ConvertToSqlite extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:mysql-to-sqlite {
        conversion=default : The conversion configuration to run
    }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Convert a given database connection's database to sqlite";

    /** @var ConversionConfig */
    protected $config;

    /** @var OutputFilter */
    protected $outputFilter;

    /** @var CommandStringBuilder */
    protected $commandStringBuilder;

    public function __construct(
        ConversionConfig $config,
        OutputFilter $outputFilter,
        CommandStringBuilder $commandStringBuilder
    ) {
        $this->config = $config;

        // apply the default conversion configuration to the command line path
        $this->signature = str_replace(
            'conversion=default',
            'conversion='.$config->defaultConversion(),
            $this->signature
        );

        $this->outputFilter = $outputFilter;
        $this->commandStringBuilder = $commandStringBuilder;

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->config->load($this->argument('conversion'));
        $command = $this->commandStringBuilder->build($this->config);
        $outputPath = $this->config->outputPath();

        if (file_exists($outputPath)) {
            @unlink($outputPath);
        }

        if ($this->config->debug()) {
            $this->output->block('[DEBUG] Running command: '.$command);
        }

        //TODO Replace with path to mysqldump variable
//        if (empty(shell_exec("which mysqldump"))) {
//            throw new MissingDependency('mysqldump is not available');
//        }

        if (is_executable($this->config->converterExecutable()) === false) {
            $process = new Process('chmod +x '.$this->config->converterExecutable());
            $process->run();
        }

        $process = new Process($command);

        $outputFilter = $this->outputFilter;
        $process->mustRun(function ($type, $output) use ($outputFilter) {

            // some things are expected, so we'll hide that
            // to avoid misleading warnings/errors
            $output = $outputFilter->filter($output);
            if ($output != null) {
                if (Process::ERR === $type) {
                    echo 'ERR > '.$output;
                } else {
                    echo 'OUT > '.$output;
                }
            }
        });

        $db = new \SQLite3(storage_path(basename($this->config->outputPath())));

        if(!is_null($this->config->tables())){
            foreach ($this->config->tables() as $mobileTable => $serverTable) {
                $db->exec("ALTER TABLE $serverTable RENAME TO $mobileTable;");
            }
        }
        $this->config->executeAfterScriptClosure();

        $this->output->success('Dump created at '.$outputPath);
    }
}
