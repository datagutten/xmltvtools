<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace datagutten\dreambox\tests;

use datagutten\dreambox\recording_info;
use PHPUnit\Framework\TestCase;

class recording_infoTest extends TestCase
{
    /**
     * @var recording_info
     */
    public recording_info $info;

    public function setUp(): void
    {
        $this->info = new recording_info();
    }

    public function testParse_file_name()
    {
        $info = recording_info::parse_file_name('20191004 0055 - Nat Geo HD (N) - Vinterveiens helter.ts');
        $this->assertEquals('20191004 0055', $info['datetime']);
        $this->assertEquals('Nat Geo HD (N)', $info['channel']);
    }

    public function testParseEit()
    {
        $info = recording_info::parse_eit(__DIR__ . '/test_data/Ice Road Rescue S04E01 - Ekstremvær HD.eit', 'array');
        $this->assertSame('Vinterveiens helter', $info['name']);
        $this->assertSame('Vinterveiens helter', $info['title']);
    }

    public function testParseEitTitle()
    {
        $info = recording_info::parse_eit(__DIR__ . '/test_data/Ice Road Rescue S04E01 - Ekstremvær HD.eit');
        $this->assertSame('Vinterveiens helter', $info);
    }

    public function testEitTime()
    {
        date_default_timezone_set('Europe/Oslo');
        $info = recording_info::parse_eit(__DIR__ . '/test_data/Ice Road Rescue S04E01 - Ekstremvær HD.eit', 'array');
        $this->assertSame('2019-10-03T22:00:00+02:00', date('c', $info['start_timestamp']));
    }
}
