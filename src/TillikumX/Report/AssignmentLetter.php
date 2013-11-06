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

class AssignmentLetter extends AbstractReport
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
        return 'Applicants to use in assignment letter notifications.';
    }

    public function getFormClass()
    {
        return 'TillikumX\Form\Report\AssignmentLetter';
    }

    public function getName()
    {
        return 'Assignment letter applicants';
    }

    public function generate()
    {
        $parameters = $this->getParameters();

        $templateIds = $parameters['applications'];
        $contractId = $parameters['contract'];
        $startDate = new DateTime($parameters['start_date']);

        $applicationResult = $this->uhdsEm->createQuery(
            '
            SELECT a.personId person_id, t.slug
            FROM Uhds\Entity\HousingApplication\Application\Application a
            JOIN a.template t
            WHERE a.state IN (:states) AND
                  t.id IN (:templateIds)
            '
        )
            ->setParameter('states', ['processed'])
            ->setParameter('templateIds', $templateIds)
            ->getScalarResult();

        $personIdToApplications = array();
        foreach ($applicationResult as $row) {
            $personIdToApplications[$row['person_id']][] = $row['slug'];
        }

        $rows = $this->tillikumEm->createQuery(
            "
            SELECT p,
                   b.created_at,
                   fc.name fname, fgc.name fgname,
                   directory_email.value directory_email_value,
                   directory_phone.value directory_phone_value,
                   m.name mname,
                   s.requires_cosigned, s.is_signed, s.is_cancelled, s.is_cosigned
            FROM TillikumX\Entity\Person\Person p
            JOIN p.bookings b WITH b.start >= :date
            JOIN b.facility f
            JOIN f.configs fc WITH b.start BETWEEN fc.start AND fc.end
            JOIN f.facility_group fg
            JOIN fg.configs fgc WITH b.start BETWEEN fgc.start AND fgc.end
            LEFT JOIN p.contract_signatures s
            LEFT JOIN s.contract c WITH c.id = :contractId
            LEFT JOIN p.mealplans mb WITH mb.start >= :date
            LEFT JOIN mb.mealplan m
            LEFT JOIN p.emails directory_email WITH directory_email.type = 'directory'
            LEFT JOIN p.phone_numbers directory_phone WITH directory_phone.type = 'directory'
            LEFT JOIN p.tags t
            WHERE p.id IN (:personIds)
            GROUP BY p.id
            ORDER BY fgname, fname
            "
        )
            ->setParameter('date', $startDate)
            ->setParameter('contractId', $contractId)
            ->setParameter('personIds', array_keys($personIdToApplications))
            ->getResult();

        $ret = array(
            array(
                'OSU ID',
                'Last name',
                'First name',
                'Gender',
                'Applications',
                'Contract signed?',
                'Facility group name',
                'Facility name',
                'Directory email',
                'Directory phone number',
                'Booking created',
                'Person notes',
                'Age',
                'Tags',
            )
        );

        foreach ($rows as $row) {
            $person = $row[0];

            if (!$row['is_signed']) {
                $contractSigned = false;
            } elseif ($row['is_cancelled']) {
                $contractSigned = false;
            } elseif ($row['requires_cosigned']) {
                $contractSigned = $row['is_cosigned'];
            } else {
                $contractSigned = $row['is_signed'];
            }

            $tags = array();
            foreach ($person->tags as $tag) {
                $tags[] = $tag->name;
            }

            $tags = implode(', ', $tags);

            $ret[] = array(
                $person->osuid,
                $person->family_name,
                $person->given_name,
                $person->gender,
                implode(', ', $personIdToApplications[$person->id]),
                $contractSigned ? 'Y' : 'N',
                $row['fgname'],
                $row['fname'],
                $row['directory_email_value'],
                $row['directory_phone_value'],
                date('Y-m-d H:i:s', $row['created_at']->format('U')),
                $person->note,
                $person->age,
                $tags,
            );
        }

        return $ret;
    }
}
