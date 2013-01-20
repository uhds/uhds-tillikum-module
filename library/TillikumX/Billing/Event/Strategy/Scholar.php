<?php
/**
 * The Tillikum Project (http://tillikum.org/)
 *
 * @link       http://tillikum.org/websvn/
 * @copyright  Copyright 2009-2012 Oregon State University (http://oregonstate.edu/)
 * @license    http://www.gnu.org/licenses/gpl-2.0-standalone.html GPLv2
 */

namespace TillikumX\Billing\Event\Strategy;

use Doctrine\Common\Collections\ArrayCollection;
use Tillikum\Billing\Event\Strategy\StrategyInterface;
use Tillikum\Entity\Billing\Entry\Entry;
use Tillikum\Entity\Billing\Event\Event;
use Tillikum\Entity\Billing\Rule\Config\Config as RuleConfig;
use Vo\Money;

class Scholar implements StrategyInterface
{
    public function getDescription()
    {
        return 'Nightly billing with discounted scholar rates applied.';
    }

    public function getName()
    {
        return 'Scholar';
    }

    public function process(Event $event, RuleConfig $config)
    {
        $entries = new ArrayCollection();

        // Nights: Day difference
        $nights = (int) $event->start
            ->diff($event->end)
            ->format('%R%a');

        // Nothing to do.
        if ($nights <= 0) {
            return $entries;
        }

        // Percentage to discount as a fraction of 1
        $discountModifier = 0;

        if ($nights >= 14) {
            $discountModifier = 0.5;
        } elseif (11 <= $nights && $nights <= 13) {
            $discountModifier = 0.25;
        } elseif (7 <= $nights && $nights <= 10) {
            $discountModifier = 0.1;
        } elseif (4 <= $nights && $nights <= 6) {
            $discountModifier = 0.05;
        }

        $amountPerNight = new Money(
            $config->amount,
            $config->currency
        );

        // Start discount amount at the configured amount
        $discountAmount = new Money(
            $config->amount,
            $config->currency
        );

        // Multiply by modifier configured above
        $discountAmount = $discountAmount->mul($discountModifier);

        // Subtract out the discount
        $amountPerNight = $amountPerNight->sub($discountAmount);

        // Calculate nightly amount
        $total = $amountPerNight->mul($nights);

        $entry = new Entry();
        $entry->amount = $total->round(2);
        $entry->currency = $total->getCurrency();
        $entry->code = $config->code;
        $entry->description = sprintf(
            '%s to %s (%s %s) @ %s per night',
            $event->start->format('Y-m-d'),
            $event->end->format('Y-m-d'),
            $nights,
            $nights === 1 ? 'night' : 'nights',
            $amountPerNight->format()
        );

        $entries->add($entry);

        return $entries;
    }
}
