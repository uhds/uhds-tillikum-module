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
use PDO;
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
            SELECT app.id, app.completed_at
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
        $utc = new DateTimeZone('UTC');
        while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
            list($personId, $templateId) = explode('-', $row['id']);
            $personIds[$personId] = array(
                'template_id' => $templateId,
                'completed_at' => strtotime($row['completed_at'] . 'Z'),
            );
        }

        $ret = array(
            array(
                'OSU ID',
                'Application types',
                'Application completed at',
                'Last name',
                'First name',
                'Gender',
                'Tags',
            )
        );

        if (count($personIds) === 0) {
            return $ret;
        }

        $people = $this->em->createQuery(
            '
            SELECT PARTIAL p.{id, family_name, given_name, gender, osuid}, tags
            FROM TillikumX\Entity\Person\Person p
            LEFT JOIN p.bookings b WITH :date BETWEEN b.start AND b.end
            LEFT JOIN p.tags tags
            WHERE b IS NULL
            AND p.id IN (:personIds)
            '
        )
            ->setParameter('date', $date)
            ->setParameter('personIds', array_keys($personIds))
            ->getResult();

        foreach ($people as $person) {
            $ret[] = array(
                $person->osuid,
                implode(', ', $personIds[$person->id]['template_id']),
                date('Y-m-d H:i:s', $personIds[$person->id]['completed_at']),
                $person->family_name,
                $person->given_name,
                $person->gender,
                implode(
                    ', ',
                    array_map(
                        function ($tag) {
                            return $tag->name;
                        },
                        $person->tags->toArray()
                    )
                )
            );
        }

        return $ret;
    }
}
