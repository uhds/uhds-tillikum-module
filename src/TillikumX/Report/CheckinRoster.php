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

class CheckinRoster extends AbstractReport
{
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getDescription()
    {
        return 'Roster containing check-in and check-out data.';
    }

    public function getFormClass()
    {
        return 'TillikumX\Form\Report\CheckinRoster';
    }

    public function getName()
    {
        return 'Check-in roster';
    }

    public function generate()
    {
        $parameters = $this->getParameters();

        $conn = $this->em->getConnection();

        $checkinRangeStart = new DateTime($parameters['range_start']);
        $checkinRangeEnd = new DateTime($parameters['range_end']);

        $rows = $this->em->createQuery(
            "
            SELECT p, t,
                   b.start, b.end, b.checkin_at, b.checkout_at,
                   rc.name rcname, fgc.name fgcname,
                   directory_email.value directory_email_value,
                   user_phone_number.value phone_number
            FROM TillikumX\Entity\Person\Person p
            JOIN p.bookings b WITH :rangeEnd >= b.start AND :rangeStart <= b.end
            JOIN Tillikum\Entity\Facility\Room\Room r WITH r = b.facility
            JOIN Tillikum\Entity\Facility\Config\Room\Room rc WITH rc.facility = r AND b.start BETWEEN rc.start AND rc.end
            JOIN r.facility_group fg
            JOIN fg.configs fgc WITH b.start BETWEEN fgc.start AND fgc.end
            LEFT JOIN p.emails directory_email WITH directory_email.type = 'directory'
            LEFT JOIN p.phone_numbers user_phone_number WITH user_phone_number.type = 'user'
            LEFT JOIN p.tags t
            GROUP BY p.id
            ORDER BY fgcname, rcname
            "
        )
            ->setParameter('rangeStart', $checkinRangeStart)
            ->setParameter('rangeEnd', $checkinRangeEnd)
            ->getResult();

        $ids = [];
        foreach ($rows as $row) {
            $person = $row[0];

            $ids[] = $person->id;
        }

        $applications = array();
        if (!empty($ids)) {
            $sth = $conn->prepare(
                "
                SELECT a.id
                FROM tillikum_housing_application_application a
                JOIN tillikum_housing_application_template t
                     ON SUBSTRING(a.id FROM LOCATE('-', a.id) + 1) = t.id
                JOIN (
                    SELECT ai.id, MAX(ai.completed_at) completed_at
                    FROM tillikum_housing_application_application ai
                    GROUP BY SUBSTRING(ai.id FROM 1 FOR LOCATE('-', ai.id) - 1)
                ) ai
                    ON SUBSTRING(a.id FROM 1 FOR LOCATE('-', a.id) - 1) = SUBSTRING(ai.id FROM 1 FOR LOCATE('-', ai.id) - 1)
                WHERE a.completed_at = ai.completed_at AND
                      t.effective <= '{$checkinRangeStart->format('Y-m-d')}' AND
                      a.cancelled_at IS NULL AND
                      SUBSTRING(a.id FROM 1 FOR LOCATE('-', a.id) - 1) IN (" . implode(',', array_fill(0, count($ids), '?')) . ")
                "
            );
            $sth->execute($ids);

            while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
                list($personId, $applicationId) = explode('-', $row['id']);
                $applications[$personId][] = $applicationId;
            }
        }

        $ret = [
            [
                'OSU ID',
                'Last name',
                'First name',
                'Facility group name',
                'Room name',
                'Gender',
                'Birthdate',
                'Age (as of ' . date('n/j/Y') . ')',
                'Student Type Code',
                'Primary Major 1',
                'Hours Registered',
                'Tags',
                'Primary phone',
                'Directory email',
                'Application type',
                'Booking start',
                'Booking end',
                'Checked in at',
                'Checked out at',
            ]
        ];

        foreach ($rows as $row) {
            $person = $row[0];
            $application = isset($applications[$person->id]) ? $applications[$person->id] : null;

            $tags = [];
            foreach ($person->tags as $tag) {
                $tags[] = $tag->name;
            }

            $tags = implode(', ', $tags);

            $row = [
                $person->osuid,
                $person->family_name,
                $person->given_name,
                $row['fgcname'],
                $row['rcname'],
                $person->gender,
                $person->birthdate ? $person->birthdate->format('Y-m-d') : '',
                $person->age,
                $person->student_type_code,
                $person->primary_major_1,
                $person->hours_registered,
                $tags,
                $row['phone_number'],
                $row['directory_email_value'],
                $application ? implode(', ', $application) : '',
                $row['start'] ? $row['start']->format('Y-m-d') : '',
                $row['end'] ? $row['end']->format('Y-m-d') : '',
                $row['checkin_at'] ? date('Y-m-d H:i:s', $row['checkin_at']->format('U')) : '',
                $row['checkout_at'] ? date('Y-m-d H:i:s', $row['checkout_at']->format('U')) : '',
            ];

            $ret[] = $row;
        }

        return $ret;
    }
}
