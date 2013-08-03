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

class OpenSpace extends AbstractReport
{
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getDescription()
    {
        return 'Facilities that have open spaces.';
    }

    public function getFormClass()
    {
        return 'TillikumX\Form\Report\OpenSpace';
    }

    public function getName()
    {
        return 'Open spaces';
    }

    public function generate()
    {
        $parameters = $this->getParameters();

        $date = new DateTime($parameters['date']);
        $facilityGroupIds = $parameters['facility_groups'];

        $spaces = $this->em->createQuery(
            "
            SELECT fgc.name fgname, fc.name fname,
                   fc.capacity, fc.gender,
                   f.id,
                   CASE WHEN COUNT(tag_ra.id) > 0 THEN 'Y' ELSE 'N' END is_ra,
                   COALESCE(COUNT(bc.id), 0) AS booking_count,
                   COALESCE(SUM(hc.space), 0) AS held_space
            FROM Tillikum\Entity\Facility\Facility f
            JOIN f.configs fc
            JOIN f.facility_group fg
            JOIN fg.configs fgc
            LEFT JOIN f.bookings bc WITH bc.start <= :date AND bc.end >= :date
            LEFT JOIN f.holds hc WITH hc.start <= :date AND hc.end >= :date
            LEFT JOIN fc.tags tag_ra WITH tag_ra.id = 'ra'
            WHERE fc.start <= :date AND
                  fc.end >= :date AND
                  fgc.start <= :date AND
                  fgc.end >= :date AND
                  fg.id IN (:facilityGroupIds)
            GROUP BY f.id
            HAVING fc.capacity > booking_count + held_space
            "
        )
            ->setParameter('date', $date)
            ->setParameter('facilityGroupIds', $facilityGroupIds)
            ->getResult();

        $ret = array(
            array(
                'Facility group',
                'Facility',
                'Facility gender',
                'RA facility?',
                'Facility capacity',
                'Available spaces',
                'Bookings',
                'Holds',
            )
        );

        foreach ($spaces as $row) {
            $tempRow = array(
                $row['fgname'],
                $row['fname'],
                $row['gender'],
                $row['is_ra'],
                $row['capacity'],
                $row['capacity'] - ($row['booking_count'] + $row['held_space']),
                '',
                ''
            );

            if ($row['booking_count'] > 0) {
                $result = $this->em->createQuery(
                    "
                    SELECT p.family_name, p.given_name, p.gender, p.birthdate
                    FROM TillikumX\Entity\Person\Person p
                    JOIN p.bookings b
                    JOIN b.facility f
                    WHERE b.start <= :date AND b.end >= :date AND
                          f.id = :facilityId
                    "
                )
                    ->setParameter('date', $date)
                    ->setParameter('facilityId', $row['id'])
                    ->getResult();

                $people = array();
                foreach ($result as $personResult) {
                    $people[] = sprintf(
                        '%s, %s (%s/%s)',
                        $personResult['family_name'],
                        $personResult['given_name'],
                        date_diff($personResult['birthdate'], new \DateTime(date('Y-m-d')))->y,
                        $personResult['gender']
                    );
                }

                $tempRow[6] = implode('; ', $people);
            }

            if ($row['held_space'] > 0) {
                $result = $this->em->createQuery(
                    "
                    SELECT h.space, h.description
                    FROM Tillikum\Entity\Facility\Hold\Hold h
                    JOIN h.facility f
                    WHERE h.start <= :date AND h.end >= :date AND
                          f.id = :facilityId
                    "
                )
                    ->setParameter('date', $date)
                    ->setParameter('facilityId', $row['id'])
                    ->getResult();

                $holds = array();
                foreach ($result as $holdResult) {
                    $holds[] = sprintf(
                        '%s (%s reserved)',
                        $holdResult['description'],
                        $holdResult['space']
                    );
                }

                $tempRow[7] = implode('; ', $holds);
            }

            $ret[] = $tempRow;
        }

        return $ret;
    }
}
