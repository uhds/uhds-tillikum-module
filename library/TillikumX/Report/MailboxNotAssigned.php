<?php

/**
 * OSU Tillikum extension library
 *
 * @category TillikumX
 * @package TillikumX_Report
 * @subpackage Report
 */

namespace TillikumX\Report;

use DateTime;
use Doctrine\ORM\EntityManager;
use Tillikum\Report\AbstractReport;

class MailboxNotAssigned extends AbstractReport
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getDescription()
    {
        return 'List booked residents not yet assigned to a mailbox.';
    }

    public function getFormClass()
    {
        return 'TillikumX\Form\Report\MailboxNotAssigned';
    }

    public function getName()
    {
        return 'Residents without mailboxes';
    }

    public function generate()
    {
        $parameters = $this->getParameters();

        $date = new DateTime($parameters['date']);
        $facilityGroupIds = $parameters['facility_groups'];


        $mailboxDb = \Uhds_Db::factory('mailbox');
        $commonDb = \Uhds_Db::factory('common');

        $mailboxSql = $mailboxDb->select()
            ->from('person', array('id'));

        $currentlyAssignedPersonIds = $mailboxDb->fetchCol($mailboxSql);

        if (empty($currentlyAssignedPersonIds)) {
            $currentlyAssignedPersonIds = array(null);
        }

        $currentlyBookedPeople = $this->em->createQuery(
            "
            SELECT p.osuid, p.family_name, p.given_name,
                   b.start, b.end,
                   fc.name fname,
                   fgc.name fgname
            FROM TillikumX\Entity\Person\Person p
            JOIN p.bookings b
            JOIN b.facility f
            JOIN f.configs fc
            JOIN f.facility_group fg
            JOIN fg.configs fgc
            WHERE b.start <= :date AND b.end >= :date AND
                  fc.start <= :date AND fc.end >= :date AND
                  fgc.start <= :date AND fgc.end >= :date AND 
                  fg.id IN (:facilityGroupIds) AND
                  p.id NOT IN (:currentlyAssignedPersonIds)
            "
        )
            ->setParameter('date', $date)
            ->setParameter('facilityGroupIds', $facilityGroupIds)
            ->setParameter('currentlyAssignedPersonIds', $currentlyAssignedPersonIds)
            ->getResult();

        $ret = array(
            array(
                'OSU ID',
                'Last name',
                'First name',
                'Building',
                'Room',
                'Booking start',
                'Booking end',
                'INTO?',
            )
        );

        foreach ($currentlyBookedPeople as $row) {
            $intoSql = $commonDb->select()
                ->from(
                    'into_salesforce_person',
                    array('osuid')
                )
                ->join(
                    'into_salesforce_person_reservation',
                    'into_salesforce_person.osuid = into_salesforce_person_reservation.person_osuid',
                    array()
                )
                ->where('into_salesforce_person_reservation.start <= ?', $date->format('Y-m-d'))
                ->where('into_salesforce_person_reservation.end >= ?', $date->format('Y-m-d'))
                ->where('into_salesforce_person.osuid = ?', $row['osuid'])
                ->limit(1);

            $intoRows = $commonDb->fetchAll($intoSql);

            $isInto = count($intoRows) > 0;

            $ret[] = array(
                $row['osuid'],
                $row['family_name'],
                $row['given_name'],
                $row['fgname'],
                $row['fname'],
                $row['start']->format('Y-m-d'),
                $row['end']->format('Y-m-d'),
                $isInto ? 'Y' : 'N',
            );
        }

        return $ret;
    }
}
