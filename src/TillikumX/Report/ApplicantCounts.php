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

class ApplicantCounts extends AbstractReport
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
        return 'Designed to aid in weekly statistics.';
    }

    public function getFormClass()
    {
        return 'TillikumX\Form\Report\ApplicantCounts';
    }

    public function getName()
    {
        return 'Applicant counts';
    }

    public function generate()
    {
        $parameters = $this->getParameters();

        $newApplication = $parameters['new_application'];
        $returnerApplication = $parameters['returner_application'];
        $date = new DateTime($parameters['date']);
        $openingDate = new DateTime($parameters['opening_date']);

        $numberCompletedByDate = $this->uhdsEm->createQuery(
            '
            SELECT COUNT(DISTINCT a.id)
            FROM Uhds\Entity\HousingApplication\Application\Application a
            JOIN Uhds\Entity\HousingApplication\Application\Completion c WITH c.application = a
            JOIN a.template t
            WHERE a.state IN (:states) AND
                  c.createdAt <= :completedAt AND
                  t.id = :templateId
            '
        )
            ->setParameter('states', ['completed', 'processed'])
            ->setParameter('completedAt', $date)
            ->setParameter('templateId', $newApplication)
            ->getSingleScalarResult();

        $numberCanceledByDate = $this->uhdsEm->createQuery(
            '
            SELECT COUNT(DISTINCT a.id)
            FROM Uhds\Entity\HousingApplication\Application\Application a
            JOIN Uhds\Entity\HousingApplication\Application\Cancellation c WITH c.application = a
            JOIN a.template t
            WHERE a.state IN (:states) AND
                  c.createdAt <= :canceledAt AND
                  t.id = :templateId
            '
        )
            ->setParameter('states', ['canceled'])
            ->setParameter('canceledAt', $date)
            ->setParameter('templateId', $newApplication)
            ->getSingleScalarResult();

        $result = $this->tillikumEm->createQuery(
            '
            SELECT p.id
            FROM Tillikum\Entity\Person\Person p
            JOIN p.bookings b
            LEFT JOIN p.tags t_ra WITH t_ra.id = \'ra\'
            LEFT JOIN p.tags t_sra WITH t_sra.id = \'sra\'
            WHERE :openingDate BETWEEN b.start AND b.end AND
                  t_ra.id IS NULL AND
                  t_sra.id IS NULL
            '
        )
            ->setParameter('openingDate', $openingDate)
            ->getScalarResult();

        $personIds = array_map('current', $result);

        if (empty($personIds)) {
            $personIds[] = null;
        }

        $returnersAssigned = $this->uhdsEm->createQuery(
            '
            SELECT COUNT(DISTINCT a.id)
            FROM Uhds\Entity\HousingApplication\Application\Application a
            JOIN a.template t
            WHERE a.state IN (:states) AND
                  a.personId IN (:personIds) AND
                  t.id = :templateId
            '
        )
            ->setParameter('states', ['processed'])
            ->setParameter('personIds', $personIds)
            ->setParameter('templateId', $returnerApplication)
            ->getSingleScalarResult();

        $ret = [
            [
                'Number of completed new applications as of ' . $date->format('n/j/Y'),
                'Number of cancelled new applications as of ' . $date->format('n/j/Y'),
                'Number of assigned returners (non-RA)',
            ]
        ];

        $ret[] = [
            $numberCompletedByDate,
            $numberCanceledByDate,
            $returnersAssigned,
        ];

        return $ret;
    }
}
