<?php

/**
 * The Tillikum Project (http://tillikum.org/).
 *
 * @link       http://tillikum.org/websvn/
 *
 * @copyright  Copyright 2009-2012 Oregon State University (http://oregonstate.edu/)
 * @license    http://www.gnu.org/licenses/gpl-2.0-standalone.html GPLv2
 */

namespace TillikumX\Report;

use DateTime;
use Doctrine\ORM\EntityManager;
use Tillikum\Report\AbstractReport;

class OrchardCourtApplicationLifecycle extends AbstractReport
{

    private $tillikumEm;

    public function __construct(EntityManager $tillikumEm)
    {
        $this->tillikumEm = $tillikumEm;
    }

    public function getDescription()
    {
        return 'Roster of all people and their application lifecycle given by date.';
    }

    public function getFormClass()
    {
        return 'TillikumX\Form\Report\OrchardCourtApplicationLifecycle';
    }

    public function getName()
    {
        return 'Orchard Court Application Lifecycle';
    }

    public function generate()
    {
        $parameters = $this->getParameters();

        $date = new DateTime($parameters['date']);

        $rows = $this->tillikumEm->createQuery(
            "
            SELECT p
            FROM TillikumX\Entity\Person\Person p
            JOIN p.bookings b WITH :date BETWEEN b.start AND b.end
            JOIN Tillikum\Entity\Facility\Room\Room r WITH r = b.facility
            JOIN Tillikum\Entity\Facility\Config\Room\Room rc WITH rc.facility = r AND b.start BETWEEN rc.start AND rc.end
            JOIN rc.type rtype
            JOIN r.facility_group fg
            JOIN fg.configs fgc WITH b.start BETWEEN fgc.start AND fgc.end
            WHERE fg.id = :facilityGroupId
            "
        )
            ->setParameter('date', $date)
            ->setParameter('facilityGroupId', 'd7e9b0536c8fec14ea94e94cb0e63eb9')
            ->getResult();

        $ids = array();
        foreach ($rows as $row) {
            $ids[] = $row->id;
        }

        $commonDb = \Uhds_Db::factory('common');

        $applications = [];
        if (!empty($ids)) {
            $applicationSql = $commonDb->select()
            ->from(
                'familyhousing_waitlist_entry',
                array(
                    'person_id',
                    'created_at',
                    'bedrooms',
                )
            )
            ->join('familyhousing_waitlist_offer', 'familyhousing_waitlist_entry.id = familyhousing_waitlist_offer.entry_id', ['status'])
            ->where('familyhousing_waitlist_entry.person_id IN (?)', $ids);

            $applicationRows = $commonDb->fetchAll($applicationSql);
            foreach ($applicationRows as $row) {
                $applications[$row['person_id']] = $row;
            }
        }

        $ret = array(
            array(
                'OSU ID',
                'PIDM',
                'Last name',
                'First name',
                'Gender',
                'Birthdate',
                'Age (as of '.date('n/j/Y').')',
                'Application date',
                'First booking date',
                'Bedroom',
            ),
        );

        foreach ($rows as $person) {
            $application = isset($applications[$person->id]) ? $applications[$person->id] : null;

            $booking = $person->bookings[0];
            $row = array(
                $person->osuid,
                $person->pidm,
                $person->family_name,
                $person->given_name,
                $person->gender,
                $person->birthdate ? $person->birthdate->format('Y-m-d') : '',
                $person->age,
                !empty($application) ? (new \DateTime($application['created_at']))->format('Y-m-d g:i a') : '',
                $booking->start->format('n/j/Y'),
                !empty($application) ? $application['bedrooms'] : '',

            );

            $ret[] = $row;
        }

        return $ret;
    }
}
