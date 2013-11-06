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
use Vo\DateRange;

class NewAssignments extends AbstractReport
{
    private $tillikumEm;
    private $uhdsEm;

    public function __construct(EntityManager $tillikumEm, EntityManager $uhdsEm)
    {
        $this->tillikumEm = $tillikumEm;
        $this->uhdsEm = $uhdsEm;
    }

    public function getDescription()
    {
        return 'Assignments that have been created on or after a specified date.';
    }

    public function getFormClass()
    {
        return 'TillikumX\Form\Report\NewAssignments';
    }

    public function getName()
    {
        return 'New assignments';
    }

    public function generate()
    {
        $parameters = $this->getParameters();

        $earliestBookingCreationDate = new DateTime($parameters['earliest_booking_creation_date']);
        $templateIds = $parameters['applications'];

        $applicationResult = $this->uhdsEm->createQuery(
            '
            SELECT a.personId person_id, t.slug
            FROM Uhds\Entity\HousingApplication\Application\Application a
            JOIN a.template t
            WHERE a.state IN (:states) AND
                  t.id IN (:templateIds)
            '
        )
            ->setParameter('states', ['completed', 'processed'])
            ->setParameter('templateIds', $templateIds)
            ->getResult();

        $personIds = array();
        foreach ($applicationResult as $row) {
            $personIds[$row['person_id']][] = $row['slug'];
        }

        $rows = $this->tillikumEm->createQuery(
            "
            SELECT p.id, p.osuid, p.family_name, p.given_name, e.value email,
                   fc.name fname, fgc.name fgname,
                   b.created_at, b.start, b.end
            FROM TillikumX\Entity\Person\Person p
            JOIN p.emails e
            JOIN e.type et
            JOIN p.bookings b
            JOIN b.facility f
            JOIN f.configs fc
            JOIN f.facility_group fg
            JOIN fg.configs fgc
            WHERE p.id IN (:personIds)
            AND b.start BETWEEN fc.start AND fc.end
            AND b.start BETWEEN fgc.start AND fgc.end
            AND b.created_at >= :createdAt
            AND et.id = 'directory'
            ORDER BY b.start
            "
        )
            ->setParameter('createdAt', $earliestBookingCreationDate)
            ->setParameter('personIds', array_keys($personIds))
            ->getResult();

        $ret = array(
            array(
                'Last name',
                'First name',
                'Email',
                'OSU ID',
                'Application type',
                'Facility group',
                'Facility',
                'Booking start',
                'Booking end',
                'Booking created at',
            )
        );

        foreach ($rows as $row) {
            $ret[] = array(
                $row['family_name'],
                $row['given_name'],
                $row['email'],
                $row['osuid'],
                implode(', ', $personIds[$row['id']]),
                $row['fgname'],
                $row['fname'],
                $row['start']->format('Y-m-d'),
                $row['end']->format('Y-m-d'),
                date('Y-m-d H:i:s', $row['created_at']->format('U')),
            );
        }

        return $ret;
    }
}
