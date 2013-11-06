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

class IntoPreferences extends AbstractReport
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getDescription()
    {
        return 'List preferences for INTO reservations that intersect a given date.';
    }

    public function getFormClass()
    {
        return 'TillikumX\Form\Report\IntoPreferences';
    }

    public function getName()
    {
        return 'INTO reservation preferences';
    }

    public function generate()
    {
        $commonDb = \Uhds_Db::factory('common');

        $parameters = $this->getParameters();

        $date = new DateTime($parameters['date']);

        $reservationSql = $commonDb->select()
            ->from(
                'into_salesforce_person',
                array('osuid', 'current_program', 'admit_program')
            )
            ->join(
                'into_salesforce_person_reservation',
                'into_salesforce_person.osuid = into_salesforce_person_reservation.person_osuid',
                array('created_at', 'start', 'end', 'housing_code', 'notes')
            )
            ->where('into_salesforce_person_reservation.start <= ?', $date->format('Y-m-d'))
            ->where('into_salesforce_person_reservation.end >= ?', $date->format('Y-m-d'));

        $ret = array(
            array(
                'OSU ID',
                'Last name',
                'First name',
                'Gender',
                'Age',
                'Reservation created at',
                'Country of Origin',
                'Current program',
                'Admit program',
                'Start',
                'End',
                'Housing preference',
                'Notes'
            )
        );

        foreach ($commonDb->fetchAll($reservationSql) as $row) {
            $person = $this->em->getRepository('TillikumX\Entity\Person\Person')
                ->findOneByOsuid($row['osuid']);

            $reservationRange = new DateRange(
                new DateTime($row['start']),
                new DateTime($row['end'])
            );

            if (null === $person) {
                $ret[] = array(
                    $row['osuid'],
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    $reservationRange->getStart()->format('Y-m-d'),
                    $reservationRange->getEnd()->format('Y-m-d'),
                    '',
                    '',
                );
            } else {
                $ret[] = array(
                    $person->osuid,
                    $person->family_name,
                    $person->given_name,
                    $person->gender,
                    $person->age,
                    date('Y-m-d H:i:s', strtotime($row['created_at'])),
                    $person->origin_country,
                    $row['current_program'],
                    $row['admit_program'],
                    $reservationRange->getStart()->format('Y-m-d'),
                    $reservationRange->getEnd()->format('Y-m-d'),
                    $row['housing_code'],
                    $row['notes'],
                );
            }
        }

        return $ret;
    }
}
