<?php
/**
 * The Tillikum Project (http://tillikum.org/)
 *
 * @link       http://tillikum.org/websvn/
 * @copyright  Copyright 2009-2012 Oregon State University (http://oregonstate.edu/)
 * @license    http://www.gnu.org/licenses/gpl-2.0-standalone.html GPLv2
 */

namespace TillikumX\Report;

use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManager;
use Tillikum\Report\AbstractReport;

class Checkout extends AbstractReport
{
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getDescription()
    {
        return 'List booking information by a check-out date range.';
    }

    public function getFormClass()
    {
        return 'TillikumX\Form\Report\Checkout';
    }

    public function getName()
    {
        return 'Check-out audit';
    }

    public function generate()
    {
        $parameters = $this->getParameters();

        $rangeStart = new DateTime($parameters['range_start']);
        $rangeEnd = new DateTime($parameters['range_end']);
        $rangeEnd->modify('+1 day');

        $rows = $this->em->createQuery(
            "
            SELECT p.osuid, p.family_name, p.given_name, p.gender,
                   fgc.name fgname,
                   rc.name rname, rc.capacity,
                   t.name roomtype,
                   b.start, b.end, b.checkout_at, b.checkout_by
            FROM Tillikum\Entity\Booking\Facility\Facility b
            JOIN TillikumX\Entity\Person\Person p WITH b.person = p
            JOIN Tillikum\Entity\Facility\Room\Room r WITH b.facility = r
            JOIN Tillikum\Entity\Facility\Config\Room\Room rc WITH r = rc.facility
            JOIN rc.type t
            JOIN r.facility_group fg
            JOIN fg.configs fgc
            WHERE b.checkout_at >= :rangeStart AND
                  b.checkout_at < :rangeEnd AND
                  rc.start <= b.start AND
                  rc.end >= b.start AND
                  fgc.start <= b.start AND
                  fgc.end >= b.start
            "
        )
            ->setParameter('rangeStart', $rangeStart)
            ->setParameter('rangeEnd', $rangeEnd)
            ->getResult();

        $ret = [
            [
                'OSU ID',
                'Last name',
                'First name',
                'Gender',
                'Room group',
                'Room',
                'Room capacity',
                'Room type',
                'Booking start',
                'Booking end',
                'Check-out timestamp',
            ]
        ];

        foreach ($rows as $row) {
            $ret[] = [
                $row['osuid'],
                $row['family_name'],
                $row['given_name'],
                $row['gender'],
                $row['fgname'],
                $row['rname'],
                $row['capacity'],
                $row['roomtype'],
                $row['start']->format('Y-m-d'),
                $row['end']->format('Y-m-d'),
                date('Y-m-d H:i:s', $row['checkout_at']->format('U')),
            ];
        }

        return $ret;
    }
}
