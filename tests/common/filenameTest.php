<?php

namespace datagutten\xmltv\tests\tools\common;

use datagutten\xmltv\tools\common\filename;
use PHPUnit\Framework\TestCase;

class filenameTest extends TestCase
{
    public function testFile_path()
    {
        $path = filename::file_path('test.no', 'xmltv', 1570125820, 'xml');
        $this->assertEquals('test.no/xmltv/2019/test.no_2019-10-03.xml', $path);
    }

    public function testFolder()
    {
        $folder = filename::folder('test.no', 'xmltv', 1570125820);
        $this->assertEquals('test.no/xmltv/2019', $folder);
    }

    public function testFilename()
    {
        $file_name = filename::filename('test.no', 1570125820, 'xml');
        $this->assertEquals('test.no_2019-10-03.xml', $file_name);
    }
}
