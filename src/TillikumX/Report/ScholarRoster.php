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

class ScholarRoster extends AbstractReport
{
    /**
     * How far into the future to advance the queried template effective date?
     *
     * Should be an argument that can be passed directory to DateTime#modify.
     *
     * @var string
     */

    private $tillikumEm;

    public function __construct(EntityManager $tillikumEm)
    {
        $this->tillikumEm = $tillikumEm;
    }

    public function getDescription()
    {
        return 'Roster of all scholars in the system on a given date.';
    }

    public function getFormClass()
    {
        return 'TillikumX\Form\Report\ScholarRoster';
    }

    public function getName()
    {
        return 'Scholar Roster';
    }

    public function generate()
    {
        $parameters = $this->getParameters();

        $date = new DateTime($parameters['date']);

        $rows = $this->tillikumEm->createQuery(
            "
            SELECT p, t,
                   rc.name rcname, fgc.name fgcname,
                   rtype.name roomtype,
                   directory_email.value directory_email_value,
                   campus_email.value campus_email_value,
                   user_email.value user_email_value,
                   campus_address.street campus_address_street,
                   campus_address.postal_code campus_address_postal_code,
                   user_phone_number.value phone_number,
                   ec1.given_name ec_given_name, ec1.family_name ec_family_name,
                   ec1.relationship ec_relationship,
                   ec1.primary_phone_number ec_primary_phone_number,
                   ec2.given_name ec2_given_name, ec2.family_name ec2_family_name,
                   ec2.relationship ec2_relationship,
                   ec2.primary_phone_number ec2_primary_phone_number,
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
            LEFT JOIN p.emails campus_email WITH campus_email.type = 'campus'
            LEFT JOIN p.emails user_email WITH user_email.type = 'user'
            LEFT JOIN p.phone_numbers user_phone_number WITH user_phone_number.type = 'user'
            LEFT JOIN p.emergency_contacts ec1 WITH ec1.type = 'ec1' 
            LEFT JOIN p.emergency_contacts ec2 WITH ec2.type = 'ec2'
            LEFT JOIN p.tags t
            WHERE t.id = 'scholar'
            ORDER BY fgcname, rcname
            "
        )
            ->setParameter('date', $date)
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
                'Relation of',
                'Ethnicity Code',
                'Country of Origin',
                'Student Type Code',
                'Primary Major 1',
                'Hours Registered',
                'Tags',
                'Primary phone',
                'Directory email',
                'Campus email',
                'User input email',
                'Medical information',
                'Missing person contact first name',
                'Missing person contact last name',
                'Missing person contact relationship',
                'Missing person contact phone',
                'Emergency contact first name',
                'Emergency contact last name',
                'Emergency contact relationship',
                'Emergency contact phone',
                'Room type',
                'Campus address',
                'Campus address (zip)',
                'Meal plan',
            )
        );

        foreach ($rows as $row) {
            $person = $row[0];

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
                '',
                $person->ethnicity_code,
                $person->origin_country,
                $person->student_type_code,
                $person->primary_major_1,
                $person->hours_registered,
                $tags,
                preg_replace('/[^0-9]/', '', $row['phone_number']),
                $row['directory_email_value'],
                $row['campus_email_value'],
                $row['user_email_value'],
                preg_replace('/\s+/m', ' ', (string) $person->medical),
                $row['ec_given_name'],
                $row['ec_family_name'],
                $row['ec_relationship'],
                $row['ec_primary_phone_number'],
                $row['ec2_given_name'],
                $row['ec2_family_name'],
                $row['ec2_relationship'],
                $row['ec2_primary_phone_number'],
                $row['roomtype'],
                preg_replace('/\n|\r|\r\n/', ', ', $row['campus_address_street']),
                $row['campus_address_postal_code'],
                $row['mname'],
            );

            $ret[] = $row;

            foreach ($person->relations as $relation) {

                $ret[] = [
                    '',
                    '',
                    '',
                    '',
                    $relation->tail->family_name,
                    $relation->tail->given_name,
                    $relation->tail->gender,
                    $relation->tail->birthdate ? $relation->tail->birthdate->format('Y-m-d') : '',
                    $relation->tail->age,
                    sprintf('%s, %s (%s)', $person->family_name, $person->given_name, $relation->type->name),
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                ];
            }

        }

        return $ret;
    }
}

