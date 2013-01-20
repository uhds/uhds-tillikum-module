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

class Unassigned extends AbstractReport
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getDescription()
    {
        return 'Applicants that have not been assigned to rooms.';
    }

    public function getFormClass()
    {
        return 'TillikumX\Form\Report\Unassigned';
    }

    public function getName()
    {
        return 'Unassigned applicants';
    }

    public function generate()
    {
        $parameters = $this->getParameters();

        $conn = $this->em->getConnection();

        $applications = $parameters['applications'];
        $date = new DateTime($parameters['date']);

        $sth = $conn->prepare(
            "
            SELECT app.id
            FROM tillikum_housing_application_application app
            WHERE SUBSTRING(app.id FROM LOCATE('-', app.id) + 1)
                  IN ( " . implode(',', array_fill(0, count($applications), '?')) . ") AND
                  app.state = ?
            "
        );

        $queryParameters = $applications;
        $queryParameters[] = 'processed';

        $sth->execute($queryParameters);

        $personIds = array();
        while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
            list($personId, $templateId) = explode('-', $row['id']);
            $personIds[$personId][] = $templateId;
        }

        $ret = array(
            array(
                'OSU ID',
                'Application types',
                'Last name',
                'First name',
                'Gender'
            )
        );

        if (count($personIds) === 0) {
            return $ret;
        }

        $rows = $this->em->createQuery(
            "
            SELECT p.id, p.family_name, p.given_name, p.gender, p.osuid
            FROM TillikumX\Entity\Person\Person p
            LEFT JOIN p.bookings b WITH :date BETWEEN b.start AND b.end
            WHERE b IS NULL
            AND p.id IN (:personIds)
            "
        )
            ->setParameter('date', $date)
            ->setParameter('personIds', array_keys($personIds))
            ->getResult();

        foreach ($rows as $row) {
            $ret[] = array(
                $row['osuid'],
                implode(', ', $personIds[$row['id']]),
                $row['family_name'],
                $row['given_name'],
                $row['gender']
            );
        }

        return $ret;
    }
}
