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
use Doctrine\ORM\EntityManager;
use Tillikum\Report\AbstractReport;

class Checkin extends AbstractReport
{
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getDescription()
    {
        return 'Bookings having a check-in date within a given date range.';
    }

    public function getFormClass()
    {
        return 'TillikumX\Form\Report\Checkin';
    }

    public function getName()
    {
        return 'Check-in';
    }

    public function generate()
    {
        $parameters = $this->getParameters();

        $checkinRangeStart = new DateTime($parameters['range_start']);
        $checkinRangeEnd = new DateTime($parameters['range_end']);
        $checkinRangeEnd->modify('+1 day');

        $rows = $this->em->createQuery(
            "
            SELECT p.osuid, p.family_name, p.given_name,
                   b.start, b.end, b.checkin_at,
                   fc.name fname, fgc.name fgname
            FROM TillikumX\Entity\Person\Person p
            JOIN p.bookings b
            JOIN b.facility f
            JOIN f.configs fc
            JOIN f.facility_group fg
            JOIN fg.configs fgc
            WHERE b.checkin_at >= :rangeStart AND b.checkin_at < :rangeEnd AND
                  fc.start <= b.start AND fc.end >= b.end AND
                  fgc.start <= b.start AND fgc.end >= b.end
            "
        )
            ->setParameter('rangeStart', $checkinRangeStart)
            ->setParameter('rangeEnd', $checkinRangeEnd)
            ->getResult();

        $ret = array(
            array(
                'OSU ID',
                'Last name',
                'First name',
                'Facility group',
                'Facility',
                'Booking start',
                'Booking end',
                'Checked in at'
            )
        );

        foreach ($rows as $row) {
            $ret[] = array(
                $row['osuid'],
                $row['family_name'],
                $row['given_name'],
                $row['fgname'],
                $row['fname'],
                $row['start']->format('Y-m-d'),
                $row['end']->format('Y-m-d'),
                date('Y-m-d H:i:s', $row['checkin_at']->format('U')),
            );
        }

        return $ret;
    }
}
