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

class BookingEndAudit extends AbstractReport
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getDescription()
    {
        return 'List bookings ending on a date within a given range.';
    }

    public function getFormClass()
    {
        return 'TillikumX\Form\Report\BookingEndAudit';
    }

    public function getName()
    {
        return 'Booking end audit';
    }

    public function generate()
    {
        $parameters = $this->getParameters();

        $rangeStart = new DateTime($parameters['range_start']);
        $rangeEnd = new DateTime($parameters['range_end']);

        $bookings = $this->em->createQuery(
            "
            SELECT b.start, b.end, b.checkout_at, fc.name fname, fgc.name fgname,
                   p.osuid, p.family_name, p.given_name, p.gender
            FROM TillikumX\Entity\Person\Person p
            JOIN p.bookings b
            JOIN b.facility f
            JOIN f.configs fc
            JOIN f.facility_group fg
            JOIN fg.configs fgc
            WHERE b.end >= :rangeStart AND b.end <= :rangeEnd AND
                  fc.start <= b.start AND fc.end >= b.end AND
                  fgc.start <= b.start AND fgc.end >= b.end
            "
        )
            ->setParameter('rangeStart', $rangeStart)
            ->setParameter('rangeEnd', $rangeEnd)
            ->getResult();

        $ret = array(
            array(
                'OSU ID',
                'Last name',
                'First name',
                'Gender',
                'Booking start',
                'Booking end',
                'Check-out time',
                'Facility group',
                'Facility',
            )
        );

        foreach ($bookings as $row) {
            $ret[] = array(
                $row['osuid'],
                $row['family_name'],
                $row['given_name'],
                $row['gender'],
                $row['start']->format('Y-m-d'),
                $row['end']->format('Y-m-d'),
                $row['checkout_at'] ? $row['checkout_at']->format('Y-m-d g:i:s a') : '',
                $row['fgname'],
                $row['fname'],
            );
        }

        return $ret;
    }
}
