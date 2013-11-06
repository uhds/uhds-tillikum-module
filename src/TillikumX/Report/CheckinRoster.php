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
    private $tillikumEm;
    private $uhdsEm;

    public function __construct(EntityManager $tillikumEm, EntityManager $uhdsEm)
    {
        $this->tillikumEm = $tillikumEm;
        $this->uhdsEm = $uhdsEm;
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

        $checkinRangeStart = new DateTime($parameters['range_start']);
        $checkinRangeEnd = new DateTime($parameters['range_end']);

        $rows = $this->tillikumEm->createQuery(
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

        $applications = [];
        if (!empty($ids)) {
            $result = $this->uhdsEm->createQuery(
                '
                SELECT a.personId person_id, t.effective, t.slug
                FROM Uhds\Entity\HousingApplication\Application\Application a
                JOIN a.template t
                JOIN Uhds\Entity\HousingApplication\Application\Completion c WITH c.application = a
                WHERE a.state NOT IN (:states) AND
                    c.createdAt > :aYearAgo AND
                    t.effective <= :checkinRangeStart AND
                    a.personId IN (:personIds)
                GROUP BY a.personId
                HAVING t.effective = MAX(t.effective)
                '
            )
                ->setParameter('states', ['canceled'])
                ->setParameter('aYearAgo', new DateTime('-1 year'))
                ->setParameter('checkinRangeStart', $checkinRangeStart)
                ->setParameter('personIds', $ids)
                ->getResult();

            foreach ($result as $row) {
                $applications[$row['person_id']][] = $row['slug'];
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
