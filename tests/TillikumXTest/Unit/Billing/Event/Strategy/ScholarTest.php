<?php
/**
 * The Tillikum Project (http://tillikum.org/)
 *
 * @link       http://tillikum.org/websvn/
 * @copyright  Copyright 2009-2012 Oregon State University (http://oregonstate.edu/)
 * @license    http://www.gnu.org/licenses/gpl-2.0-standalone.html GPLv2
 */

namespace TillikumXTest\Unit\Billing\Event\Strategy;

use DateTime;
use Tillikum\Entity\Billing\Event\FacilityBooking as FacilityBookingEvent;
use Tillikum\Entity\Billing\Rule\Config\FacilityBooking as FacilityBookingRuleConfig;
use TillikumX\Billing\Event\Strategy\Scholar;

class ScholarTest extends \PHPUnit_Framework_TestCase
{
    protected $ruleConfig;
    protected $strategy;

    public function setUp()
    {
        $this->ruleConfig = new FacilityBookingRuleConfig();
        $this->ruleConfig->amount = 10.00;
        $this->ruleConfig->currency = 'USD';
        $this->ruleConfig->code = 'test';

        $this->strategy = new Scholar();
    }

    public function testOneNight()
    {
        $event = new FacilityBookingEvent();
        $event->start = new DateTime('2010-07-01');
        $event->end = new DateTime('2010-07-02');

        $entries = $this->strategy->process(
            $event,
            $this->ruleConfig
        );

        $this->assertEquals(1, count($entries));

        $this->assertEquals('test', $entries[0]->code);
        $this->assertEquals(10.00, $entries[0]->amount);
        $this->assertEquals('USD', $entries[0]->currency);
    }

    public function testFourThroughSixNightDiscount()
    {
        $event = new FacilityBookingEvent();
        $event->start = new DateTime('2010-07-01');
        $event->end = new DateTime('2010-07-05');

        $entries = $this->strategy->process(
            $event,
            $this->ruleConfig
        );

        $this->assertEquals(1, count($entries));

        $this->assertEquals('test', $entries[0]->code);
        $this->assertEquals(9.50 * 4, $entries[0]->amount);
        $this->assertEquals('USD', $entries[0]->currency);
    }

    public function testSevenThroughTenNightDiscount()
    {
        $event = new FacilityBookingEvent();
        $event->start = new DateTime('2010-07-01');
        $event->end = new DateTime('2010-07-08');

        $entries = $this->strategy->process(
            $event,
            $this->ruleConfig
        );

        $this->assertEquals(1, count($entries));

        $this->assertEquals('test', $entries[0]->code);
        $this->assertEquals(9.00 * 7, $entries[0]->amount);
        $this->assertEquals('USD', $entries[0]->currency);
    }

    public function testElevenThroughThirteenNightDiscount()
    {
        $event = new FacilityBookingEvent();
        $event->start = new DateTime('2010-07-01');
        $event->end = new DateTime('2010-07-12');

        $entries = $this->strategy->process(
            $event,
            $this->ruleConfig
        );

        $this->assertEquals(1, count($entries));

        $this->assertEquals('test', $entries[0]->code);
        $this->assertEquals(7.50 * 11, $entries[0]->amount);
        $this->assertEquals('USD', $entries[0]->currency);
    }

    public function testFourteenPlusNightDiscount()
    {
        $event = new FacilityBookingEvent();
        $event->start = new DateTime('2010-07-01');
        $event->end = new DateTime('2010-07-15');

        $entries = $this->strategy->process(
            $event,
            $this->ruleConfig
        );

        $this->assertEquals(1, count($entries));

        $this->assertEquals('test', $entries[0]->code);
        $this->assertEquals(5 * 14, $entries[0]->amount);
        $this->assertEquals('USD', $entries[0]->currency);
    }
}
