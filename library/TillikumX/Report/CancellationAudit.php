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
use DateTimeZone;
use Doctrine\ORM\EntityManager;
use Tillikum\Report\AbstractReport;

class CancellationAudit extends AbstractReport
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getDescription()
    {
        return 'List people with applications cancelled within a given date range.';
    }

    public function getFormClass()
    {
        return 'TillikumX\Form\Report\CancellationAudit';
    }

    public function getName()
    {
        return 'Cancellation audit';
    }

    public function generate()
    {
        $parameters = $this->getParameters();

        $rangeStart = new DateTime($parameters['range_start']);
        $rangeEnd = new DateTime($parameters['range_end']);

        $appStatement = $this->em
            ->getConnection()
            ->executeQuery(
                "
                SELECT a.id, a.cancelled_code, a.cancelled_at
                FROM tillikum_housing_application_application a
                WHERE a.state = 'cancelled' AND
                      a.cancelled_at >= ? AND a.cancelled_at <= ?
                ",
                array(
                    gmdate('Y-m-d H:i:s', $rangeStart->format('U')),
                    gmdate('Y-m-d H:i:s', $rangeEnd->format('U')),
                )
            );

        $personIdToApplicationMap = array();
        foreach ($appStatement->fetchAll() as $row) {
            list($personId, $templateId) = explode('-', $row['id'], 2);

            $personIdToApplicationMap[$personId] = $row;
        }

        $people = $this->em->createQuery(
            "
            SELECT p.id, p.osuid, p.family_name, p.given_name,
                   MAX(b.end) b_end,
                   MAX(m.end) m_end
            FROM TillikumX\Entity\Person\Person p
            LEFT JOIN p.bookings b
            LEFT JOIN p.mealplans m
            WHERE p.id IN (:personIds)
            GROUP BY p.id
            "
        )
            ->setParameter('personIds', array_keys($personIdToApplicationMap))
            ->getResult();

        $ret = array(
            array(
                'OSU ID',
                'Last name',
                'First name',
                'Cancellation date',
                'Cancellation code',
                'Booking end',
                'Meal plan end'
            )
        );

        $utc = new DateTimeZone('UTC');

        foreach ($people as $row) {
            $app = $personIdToApplicationMap[$row['id']];

            $ret[] = array(
                $row['osuid'],
                $row['family_name'],
                $row['given_name'],
                date('Y-m-d g:i:s a', date_create($app['cancelled_at'], $utc)->format('U')),
                $app['cancelled_code'],
                $row['b_end'],
                $row['m_end'],
            );
        }

        return $ret;
    }
}
