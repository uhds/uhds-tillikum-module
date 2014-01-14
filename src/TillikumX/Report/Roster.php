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
use Doctrine\ORM\Query\ResultSetMapping;
use Tillikum\Report\AbstractReport;

class Roster extends AbstractReport
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
        return 'Roster of all people in the system on a given date.';
    }

    public function getFormClass()
    {
        return 'TillikumX\Form\Report\Roster';
    }

    public function getName()
    {
        return 'Roster';
    }

    public function generate()
    {
        $parameters = $this->getParameters();

        $date = new DateTime($parameters['date']);
        $facilityGroupIds = $parameters['facility_groups'];

        $rows = $this->tillikumEm->createQuery(
            "
            SELECT p, t,
                   rc.name rcname, fgc.name fgcname,
                   rtype.name roomtype,
                   directory_email.value directory_email_value,
                   campus_address.street campus_address_street,
                   campus_address.postal_code campus_address_postal_code,
                   user_phone_number.value phone_number,
                   ec1.given_name ec_given_name, ec1.family_name ec_family_name,
                   ec1.relationship ec_relationship,
                   ec1.primary_phone_number ec_primary_phone_number,
                   m.name mname
            FROM TillikumX\Entity\Person\Person p
            JOIN p.bookings b WITH :date BETWEEN b.start AND b.end
            JOIN Tillikum\Entity\Facility\Room\Room r WITH r = b.facility
            JOIN Tillikum\Entity\Facility\Config\Room\Room rc WITH rc.facility = r AND b.start BETWEEN rc.start AND rc.end
            JOIN rc.type rtype
            JOIN r.facility_group fg
            JOIN fg.configs fgc WITH b.start BETWEEN fgc.start AND fgc.end
            LEFT JOIN p.mealplans mb WITH :date BETWEEN mb.start AND mb.end
            LEFT JOIN mb.mealplan m
            LEFT JOIN p.addresses campus_address WITH campus_address.type = 'campus'
            LEFT JOIN p.emails directory_email WITH directory_email.type = 'directory'
            LEFT JOIN p.phone_numbers user_phone_number WITH user_phone_number.type = 'user'
            LEFT JOIN p.emergency_contacts ec1 WITH ec1.type = 'ec1'
            LEFT JOIN p.tags t
            WHERE fg.id IN (:facilityGroupIds)
            ORDER BY fgcname, rcname
            "
        )
            ->setParameter('date', $date)
            ->setParameter('facilityGroupIds', $facilityGroupIds)
            ->getResult();

        $ids = array();
        $osuids = array();
        foreach ($rows as $row) {
            $person = $row[0];

            if ($person->osuid) {
                $osuids[] = $person->osuid;
            }

            $ids[] = $person->id;
        }

        $commonDb = \Uhds_Db::factory('common');

        $reservations = array();
        if (!empty($osuids)) {
            $reservationSql = $commonDb->select()
            ->from(
                'into_salesforce_person_reservation',
                array(
                    'person_osuid',
                    'start' => new \Zend_Db_Expr('MIN(into_salesforce_person_reservation.start)'),
                    'end' => new \Zend_Db_Expr('MAX(into_salesforce_person_reservation.end)'),
                )
            )
            ->where('into_salesforce_person_reservation.person_osuid IN (?)', $osuids)
            ->group('into_salesforce_person_reservation.person_osuid');

            $reservationRows = $commonDb->fetchAll($reservationSql);
            foreach ($reservationRows as $row) {
                $reservations[$row['person_osuid']] = $row;
            }
        }

        $applications = [];
        if (!empty($ids)) {
            $rsm = new ResultSetMapping();
            $rsm->addScalarResult('person_id', 'person_id');
            $rsm->addScalarResult('slug', 'slug');

            $result = $this->uhdsEm->createNativeQuery(
                '
                SELECT a.person_id, t1.slug
                FROM housingapplication_application a
                JOIN (
                    SELECT t2.id, t2.slug, t2.effective
                    FROM housingapplication_template AS t2
                    ORDER BY t2.effective DESC
                ) AS t1 ON a.template_id = t1.id
                WHERE a.state IN (:states) AND
                      t1.effective <= :date AND
                      a.person_id IN (:personIds)
                GROUP BY a.person_id
                ',
                $rsm
            )
                ->setParameter('states', ['processed'])
                ->setParameter('date', $date->format('Y-m-d'))
                ->setParameter('personIds', $ids)
                ->getResult();

            foreach ($result as $row) {
                $applications[$row['person_id']][] = $row['slug'];
            }
        }

        $ret = array(
            array(
                'Facility group name',
                'Room name',
                'OSU ID',
                'PIDM',
                'Last name',
                'First name',
                'Gender',
                'Birthdate',
                'Age (as of ' . date('n/j/Y') . ')',
                'Ethnicity Code',
                'Country of Origin',
                'Student Type Code',
                'Primary Major 1',
                'Hours Registered',
                'Tags',
                'Primary phone',
                'Directory email',
                'Medical information',
                'Primary emergency contact first name',
                'Primary emergency contact last name',
                'Primary emergency contact relationship',
                'Primary emergency contact phone',
                'Application type',
                'Room type',
                'Campus address',
                'Campus address (zip)',
                'Meal plan',
                'First INTO reservation start date',
                'Last INTO reservation end date',
            )
        );

        foreach ($rows as $row) {
            $person = $row[0];
            $reservation = isset($reservations[$person->osuid]) ? $reservations[$person->osuid] : null;
            $application = isset($applications[$person->id]) ? $applications[$person->id] : null;

            $tags = array();
            foreach ($person->tags as $tag) {
                $tags[] = $tag->name;
            }

            $tags = implode(', ', $tags);

            $row = array(
                $row['fgcname'],
                $row['rcname'],
                $person->osuid,
                $person->pidm,
                $person->family_name,
                $person->given_name,
                $person->gender,
                $person->birthdate ? $person->birthdate->format('Y-m-d') : '',
                $person->age,
                $person->ethnicity_code,
                $person->origin_country,
                $person->student_type_code,
                $person->primary_major_1,
                $person->hours_registered,
                $tags,
                $row['phone_number'],
                $row['directory_email_value'],
                preg_replace('/\s+/m', ' ', (string) $person->medical),
                $row['ec_given_name'],
                $row['ec_family_name'],
                $row['ec_relationship'],
                $row['ec_primary_phone_number'],
                $application ? implode(', ', $application) : '',
                $row['roomtype'],
                preg_replace('/\n|\r|\r\n/', ', ', $row['campus_address_street']),
                $row['campus_address_postal_code'],
                $row['mname'],
                $reservation ? date_format(date_create($reservation['start']), 'Y-m-d') : '',
                $reservation ? date_format(date_create($reservation['end']), 'Y-m-d') : '',
            );

            $ret[] = $row;
        }

        return $ret;
    }
}
