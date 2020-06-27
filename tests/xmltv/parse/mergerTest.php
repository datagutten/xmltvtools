<?php

namespace datagutten\xmltv\tests\tools\xmltv\parse;

use datagutten\xmltv\tools\parse\merger;
use PHPUnit\Framework\TestCase;

class mergerTest extends TestCase
{
    public function testFind_program()
    {
        $merger = new merger(__DIR__.'/test_data', ['xmltv', 'xmltv_php']);
        $program = $merger->find_program(strtotime('2020-05-10 16:55 +02:00'), 'xd.disneychannel.no');
        $this->assertEquals('Phineas og Ferb', $program->{'title'});
        $this->assertEquals('Klimpaloonultimatumet', $program->{'sub-title'});
        $this->assertEquals('3.119.', $program->{'episode-num'});
    }
}
