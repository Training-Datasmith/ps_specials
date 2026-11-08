<?php

class DirectoryGuardTest extends PsSpecialsTestCase
{
    /**
     * @dataProvider guardPathProvider
     */
    public function testDirectoryGuardExitsWithoutOutput($relativePath)
    {
        $path = dirname(dirname(__DIR__)) . '/' . $relativePath;
        $result = $this->runChildInclude($path);
        $this->assertSame(0, $result['exit_code']);
        $this->assertSame('running', $result['sentinel']);
        $this->assertSame('', $result['stdout']);
        $this->assertSame('', $result['stderr']);
    }

    public function guardPathProvider()
    {
        return array(
            array('index.php'),
            array('tests/index.php'),
            array('tests/php/index.php'),
            array('tests/php/phpstan/index.php'),
            array('translations/index.php'),
            array('upgrade/index.php'),
            array('views/index.php'),
            array('views/templates/hook/index.php'),
            array('views/templates/index.php'),
        );
    }
}
