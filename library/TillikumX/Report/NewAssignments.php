<?php
/**
 * The Tillikum Project (http://tillikum.org/)
 *
 * @link       http://tillikum.org/websvn/
 * @copyright  Copyright 2009-2012 Oregon State University (http://oregonstate.edu/)
 * @license    http://www.gnu.org/licenses/gpl-2.0-standalone.html GPLv2
 */

namespace TillikumX\Report;

use ArrayIterator;
use DateTime;
use Doctrine\ORM\EntityManager;
use LimitIterator;
use Tillikum\Report\AbstractReport;
use Vo\DateRange;

class NewAssignments extends AbstractReport
{
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getDescription()
    {
        return 'Assignments starting between a range of dates where the user has an application that has been completed after a specific date.';
    }

    public function getFormClass()
    {
        return 'TillikumX\Form\Report\NewAssignments';
    }

    public function getName()
    {
        return 'New assignments';
    }

    public function generate()
    {
        $parameters = $this->getParameters();

        $conn = $this->em->getConnection();

        $earliestAppDate = new DateTime($parameters['earliest_app_date']);
        $startDate = new DateTime($parameters['start_date']);
        $endDate = new DateTime($parameters['end_date']);
        $applications = $parameters['applications'];

        $sth = $conn->prepare(
            "
            SELECT app.id
            FROM tillikum_housing_application_application app
            WHERE SUBSTRING(app.id FROM LOCATE('-', app.id) + 1)
                  IN ( " . implode(',', array_fill(0, count($applications), '?')) . ") AND
                  app.state IN (?, ?) AND
                  app.completed_at >= ?
            "
        );

        $queryParameters = $applications;
        $queryParameters[] = 'completed';
        $queryParameters[] = 'processed';
        $queryParameters[] = gmdate('Y-m-d H:i:s', $earliestAppDate->format('U'));

        $sth->execute($queryParameters);

        $personIds = array();
        while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
            list($personId, $templateId) = explode('-', $row['id']);
            $personIds[$personId][] = $templateId;
        }

        $rows = $this->em->createQuery(
            "
            SELECT p.id, p.osuid,
                   b.start, b.end
            FROM TillikumX\Entity\Person\Person p
            JOIN p.bookings b WITH b.start BETWEEN :startDate AND :endDate
            WHERE p.id IN (:personIds)
            ORDER BY b.start
            "
        )
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('personIds', array_keys($personIds))
            ->getResult();

        $ret = array(
            array(
                'OSU ID',
                'Application Type',
                'Booking start',
                'Booking end',
            )
        );

        foreach ($rows as $row) {
            $ret[] = array(
                $row['osuid'],
                implode(', ', $personIds[$row['id']]),
                $row['start']->format('Y-m-d'),
                $row['end']->format('Y-m-d'),
            );
        }

        return $ret;
    }
}
