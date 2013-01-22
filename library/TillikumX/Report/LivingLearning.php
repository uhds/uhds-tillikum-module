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

class LivingLearning extends AbstractReport
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getDescription()
    {
        return 'Data suitable for a mail merge for the Living and Learning mailing.';
    }

    public function getFormClass()
    {
        return 'TillikumX\Form\Report\LivingLearning';
    }

    public function getName()
    {
        return 'Living and Learning information';
    }

    public function generate()
    {
        $conn = $this->em->getConnection();
        $parameters = $this->getParameters();

        $applicationIds = $parameters['applications'];
        $contractIds = $parameters['contracts'];
        $date = new DateTime($parameters['date']);

        $sth = $conn->prepare(
            "
            SELECT app.id
            FROM tillikum_housing_application_application app
            WHERE SUBSTRING(app.id FROM LOCATE('-', app.id) + 1)
                  IN ( " . implode(',', array_fill(0, count($applicationIds), '?')) . ") AND
                  app.state = ?
            "
        );

        $queryParameters = $applicationIds;
        $queryParameters[] = 'processed';

        $sth->execute($queryParameters);

        $personIds = array();
        while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
            list($personId, $templateId) = explode('-', $row['id']);
            $personIds[$personId][] = $templateId;
        }

        $rows = $this->em->createQuery(
            "
            SELECT p.id, p.family_name, p.given_name, p.osuid, p.onid,
                   b.start booking_start, b.end booking_end,
                   mb.start plan_start, mb.end plan_end,
                   m.name mname,
                   r.id facility_id,
                   rc.name rname, fgc.name fgname,
                   rtype.name roomtype,
                   s.requires_cosigned, s.is_signed, s.is_cancelled, s.is_cosigned,
                   directory_address.street directory_address_street,
                   directory_address.locality directory_address_locality,
                   directory_address.region directory_address_region,
                   directory_address.postal_code directory_address_postal_code,
                   directory_address.country directory_address_country,
                   ec1.updated_at
            FROM TillikumX\Entity\Person\Person p
            JOIN p.bookings b WITH :date BETWEEN b.start AND b.end
            JOIN Tillikum\Entity\Facility\Room\Room r WITH r = b.facility
            JOIN Tillikum\Entity\Facility\Config\Room\Room rc WITH rc.facility = r AND b.start BETWEEN rc.start AND rc.end
            JOIN rc.type rtype
            JOIN r.facility_group fg
            JOIN fg.configs fgc WITH b.start BETWEEN fgc.start AND fgc.end
            LEFT JOIN p.mealplans mb WITH :date BETWEEN mb.start AND mb.end
            LEFT JOIN mb.mealplan m
            LEFT JOIN p.contract_signatures s WITH s.contract IN (:contractIds)
            LEFT JOIN p.addresses directory_address WITH directory_address.type = 'directory'
            LEFT JOIN p.emergency_contacts ec1 WITH ec1.type = 'ec1'
            LEFT JOIN p.tags t
            WHERE t.id IS NULL OR t.id NOT IN ('ra', 'sra')
            GROUP BY p.id
            ORDER BY fgname, rname
            "
        )
            ->setParameter('contractIds', $contractIds)
            ->setParameter('date', $date)
            ->getResult();

        $ret = array(
            array(
                'OSU ID',
                'Last name',
                'First name',
                'Application type',
                'Arriving at',
                'Facility group',
                'Room',
                'Room type',
                'Booking start',
                'Booking end',
                'Meal plan',
                'Meal plan start',
                'Meal plan end',
                'Loft kit?',
                'Has updated emergency contact?',
                'Contract signed?',
                'Email',
                'Address street',
                'Address city',
                'Address region',
                'Address postcode',
                'Address country',
                'Roommate 1 first name',
                'Roommate 1 last name',
                'Roommate 1 email',
                'Roommate 2 first name',
                'Roommate 2 last name',
                'Roommate 2 email',
                'Roommate 3 first name',
                'Roommate 3 last name',
                'Roommate 3 email',
                'Roommate 4 first name',
                'Roommate 4 last name',
                'Roommate 4 email'
            )
        );

        $roommateQuery = $this->em->createQuery(
            "
            SELECT p.family_name, p.given_name, p.onid
            FROM TillikumX\Entity\Person\Person p
            JOIN p.bookings b WITH :date BETWEEN b.start AND b.end
            WHERE p.id != :personId AND b.facility = :facilityId
            "
        )
            ->setParameter('date', $date);

        foreach ($rows as $row) {
            if (!$row['is_signed']) {
                $contractSigned = false;
            } elseif ($row['is_cancelled']) {
                $contractSigned = false;
            } elseif ($row['requires_cosigned']) {
                $contractSigned = $row['is_cosigned'];
            } else {
                $contractSigned = $row['is_signed'];
            }

            $unixAYearAgo = strtotime('-1 year');

            if (isset($personIds[$row['id']])) {
                $templateIds = $personIds[$row['id']];
            } else {
                $templateIds = array();
            }

            $retRow = array(
                $row['osuid'],
                $row['family_name'],
                $row['given_name'],
                implode(', ', $templateIds),
                'Not implemented yet.',
                $row['fgname'],
                $row['rname'],
                $row['roomtype'],
                $row['booking_start']->format('Y-m-d'),
                $row['booking_end']->format('Y-m-d'),
                $row['mname'],
                $row['plan_start'] ? $row['plan_start']->format('Y-m-d') : '',
                $row['plan_end'] ? $row['plan_end']->format('Y-m-d') : '',
                'Not implemented yet.',
                $row['updated_at'] ? ($row['updated_at']->format('U') > $unixAYearAgo ? 'Yes' : 'No') : 'No',
                $contractSigned ? 'Yes' : 'No',
                sprintf('%s@onid.oregonstate.edu', $row['onid']),
                str_replace("\n", ' / ', $row['directory_address_street']),
                $row['directory_address_locality'],
                $row['directory_address_region'],
                $row['directory_address_postal_code'],
                $row['directory_address_country'],
            );

            $roommates = $roommateQuery
                ->setParameter('personId', $row['id'])
                ->setParameter('facilityId', $row['facility_id'])
                ->getResult();

            for ($i = 0; $i < 4; $i++) {
                if (isset($roommates[$i])) {
                    $roommate = $roommates[$i];

                    $retRow[] = $roommate['given_name'];
                    $retRow[] = $roommate['family_name'];
                    $retRow[] = sprintf('%s@onid.oregonstate.edu', $roommate['onid']);
                } else {
                    $retRow[] = '';
                    $retRow[] = '';
                    $retRow[] = '';
                }
            }

            $ret[] = $retRow;
        }

        return $ret;
    }
}
