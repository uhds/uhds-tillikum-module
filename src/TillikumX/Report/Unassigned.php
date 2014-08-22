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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use PDO;
use Tillikum\Common\Contract\Signatures;
use Tillikum\Report\AbstractReport;

class Unassigned extends AbstractReport
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

        $templateIds = $parameters['applications'];
        $date = new DateTime($parameters['date']);
        $contractId = $parameters['contract'];

        $applicationResult = $this->uhdsEm->createQuery(
            '
            SELECT a.personId person_id, t.slug, c.createdAt completed_at
            FROM Uhds\Entity\HousingApplication\Application\Application a
            JOIN Uhds\Entity\HousingApplication\Application\Completion c WITH c.application = a
            JOIN a.template t
            WHERE a.state IN (:states) AND
                  t.id IN (:templateIds)
            GROUP BY a.personId
            '
        )
            ->setParameter('states', ['processed'])
            ->setParameter('templateIds', $templateIds)
            ->getResult();

        $personIds = array();
        foreach ($applicationResult as $row) {
            $personIds[$row['person_id']] = array(
                'template_id' => $row['slug'],
                'completed_at' => $row['completed_at'],
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
                'Has valid contract?',
                'Tags',
            )
        );

        if (count($personIds) === 0) {
            return $ret;
        }

        $people = $this->tillikumEm->createQuery(
            '
            SELECT PARTIAL p.{id, family_name, given_name, gender, osuid}, sigs, tags
            FROM TillikumX\Entity\Person\Person p
            LEFT JOIN p.contract_signatures sigs WITH sigs.contract = :contractId
            LEFT JOIN p.bookings b WITH :date BETWEEN b.start AND b.end
            LEFT JOIN p.tags tags
            WHERE b IS NULL
            AND p.id IN (:personIds)
            '
        )
            ->setParameter('date', $date)
            ->setParameter('contractId', $contractId)
            ->setParameter('personIds', array_keys($personIds))
            ->getResult();

        foreach ($people as $person) {
            $isSignatureValid = Signatures::areValid(
                new ArrayCollection(
                    $person->contract_signatures->toArray()
                )
            );

            $ret[] = array(
                $person->osuid,
                $personIds[$person->id]['template_id'],
                date('Y-m-d H:i:s', $personIds[$person->id]['completed_at']->format('U')),
                $person->family_name,
                $person->given_name,
                $person->gender,
                $isSignatureValid ? 'Yes' : 'No',
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
