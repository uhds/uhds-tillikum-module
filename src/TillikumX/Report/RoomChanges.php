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

class RoomChanges extends AbstractReport
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getDescription()
    {
        return 'List people booked to two (2) or more rooms for the same time period.';
    }

    public function getFormClass()
    {
        return 'TillikumX\Form\Report\RoomChanges';
    }

    public function getName()
    {
        return 'Room changes (in progress)';
    }

    public function generate()
    {
        $parameters = $this->getParameters();

        $date = new DateTime($parameters['date']);

        $people = $this->em->createQuery(
            "
            SELECT p.osuid, p.family_name, p.given_name,
                    b.start, b.end
            FROM TillikumX\Entity\Person\Person p
            JOIN p.bookings b
            WHERE b.start <= :date AND b.end >= :date
            GROUP BY p
            HAVING COUNT(b) > 1
            "
        )
            ->setParameter('date', $date)
            ->getResult();

        $ret = array(
            array(
                'OSU ID',
                'Last name',
                'First name',
            )
        );

        foreach ($people as $row) {
            $ret[] = array(
                $row['osuid'],
                $row['family_name'],
                $row['given_name'],
            );
        }

        return $ret;
    }
}
