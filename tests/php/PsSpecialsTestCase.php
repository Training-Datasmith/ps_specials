<?php

abstract class PsSpecialsTestCase extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        TestState::reset();
    }

    protected function newModule()
    {
        return new TestablePs_Specials();
    }

    protected function moduleTemplateResource()
    {
        return 'module:ps_specials/views/templates/hook/ps_specials.tpl';
    }

    /**
     * @param string $scriptPath Absolute path under repo
     * @return array{exit_code:int,stdout:string,stderr:string,sentinel:string}
     */
    protected function runChildInclude($scriptPath)
    {
        $sentinel = tempnam(sys_get_temp_dir(), 'psspec_');
        $runner = tempnam(sys_get_temp_dir(), 'pssrun_');
        $code = '<?php
error_reporting(-1);
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
$sentinel = ' . var_export($sentinel, true) . ';
file_put_contents($sentinel, "running");
include ' . var_export($scriptPath, true) . ';
file_put_contents($sentinel, "fell-through");
';
        file_put_contents($runner, $code);
        $descriptorSpec = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w'),
        );
        $phpBin = defined('PHP_BINARY') ? PHP_BINARY : 'php';
        $proc = proc_open(
            $phpBin . ' -d error_reporting=-1 -d display_errors=1 -d display_startup_errors=1 ' . escapeshellarg($runner),
            $descriptorSpec,
            $pipes
        );
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($proc);
        $sentinelValue = file_exists($sentinel) ? file_get_contents($sentinel) : '';
        @unlink($runner);
        @unlink($sentinel);
        $this->assertNoPhpDiagnostics($stdout . $stderr);

        return array(
            'exit_code' => $exitCode,
            'stdout' => $stdout,
            'stderr' => $stderr,
            'sentinel' => $sentinelValue,
        );
    }

    protected function assertNoPhpDiagnostics($combinedOutput)
    {
        $patterns = array(
            'Notice:',
            'Warning:',
            'Deprecated:',
            'Strict Standards:',
            'Parse error:',
            'Fatal error:',
            'Catchable fatal error:',
            'Recoverable fatal error:',
        );
        foreach ($patterns as $pattern) {
            $this->assertNotContains($pattern, $combinedOutput, 'Unexpected PHP diagnostic: ' . $pattern);
        }
    }

    protected function configurationWritesForKey($key)
    {
        $writes = array();
        foreach (TestState::$configurationUpdates as $row) {
            if ($row['key'] === $key) {
                $writes[] = $row['value'];
            }
        }

        return $writes;
    }
}
