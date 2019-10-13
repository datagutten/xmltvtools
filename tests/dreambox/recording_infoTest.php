<?php

namespace datagutten\dreambox\tests;

use datagutten\dreambox\recording_info;
use PHPUnit\Framework\TestCase;

class recording_infoTest extends TestCase
{
    /**
     * @var recording_info
     */
    public $info;
    public function setUp(): void
    {
        $config = file_get_contents(__DIR__.'/../xmltv/parse/test_config.php');
        $config = str_replace('__DIR__', realpath(__DIR__.'/../xmltv/parse'), $config);
        file_put_contents(__DIR__.'/config.php', $config);
        set_include_path(__DIR__);
        $this->info = new recording_info();
    }

    public function tearDown(): void
    {
        unlink(__DIR__.'/config.php');
    }

    public function testRecording_info()
    {
        $program = $this->info->recording_info('20191004 0055 - Nat Geo HD (N) - Vinterveiens helter.ts');
        $this->assertEquals('Vinterveiens helter', $program->{'title'});
        $this->assertEquals('20191004010000 +0200', $program->attributes()->{'start'});
    }

    public function testParse_file_name()
    {
        $info = recording_info::parse_file_name('20191004 0055 - Nat Geo HD (N) - Vinterveiens helter.ts');
        $this->assertEquals('20191004 0055', $info['datetime']);
        $this->assertEquals('Nat Geo HD (N)', $info['channel']);
    }

    public function testParseEit()
    {
        $info = recording_info::parse_eit(__DIR__.'/test_data/Ice Road Rescue S04E01 - Ekstremvær HD.eit', 'array');
        $this->assertSame('Vinterveiens helter', $info['name']);
        $this->assertSame('Vinterveiens helter', $info['title']);
    }

    public function testEitTime()
    {
        date_default_timezone_set('Europe/Oslo');
        $info = recording_info::parse_eit(__DIR__.'/test_data/Ice Road Rescue S04E01 - Ekstremvær HD.eit', 'array');
        $this->assertSame('2019-10-03T22:00:00+02:00', date('c', $info['start_timestamp']));
    }

}
