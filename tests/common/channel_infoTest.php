<?php

namespace datagutten\xmltv\tests\tools\common;

use datagutten\xmltv\tools\common\channel_info;
use datagutten\xmltv\tools\exceptions\ChannelNotFoundException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class channel_infoTest extends TestCase
{
    /**
     * @var channel_info
     */
    public $info;
    public function setUp(): void
    {
        $this->info = new channel_info();
    }

    public function testName_to_id()
    {
        $id = $this->info->name_to_id('NRK1 HD');
        $this->assertEquals('nrk1.nrk.no', $id);
    }

    public function testId_to_name()
    {
        $name = $this->info->id_to_name('nrk1.nrk.no');
        $this->assertEquals('NRK1 HD', $name);
    }

    public function testInvalidName_to_id()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->info->name_to_id('NRK1 HD bad');
    }

    public function testInvalidId_to_name()
    {
        $this->expectException(ChannelNotFoundException::class);
        $this->info->id_to_name('nrk0.nrk.no');
    }
}
