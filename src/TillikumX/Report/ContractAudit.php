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

class ContractAudit extends AbstractReport
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
        return 'List people with booking and contract status information for a given date.';
    }

    public function getFormClass()
    {
        return 'TillikumX\Form\Report\ContractAudit';
    }

    public function getName()
    {
        return 'Contract audit';
    }

    public function generate()
    {
        $parameters = $this->getParameters();

        $contractId = $parameters['contract'];
        $date = new DateTime($parameters['date']);

        $rows = $this->tillikumEm->createQuery(
            "
            SELECT p.id, p.osuid, p.family_name, p.given_name,
                   b.start, b.end,
                   fc.name fname, fgc.name fgname,
                   s.requires_cosigned, s.is_signed, s.is_cancelled, s.is_cosigned
            FROM TillikumX\Entity\Person\Person p
            JOIN p.bookings b WITH :date BETWEEN b.start AND b.end
            JOIN b.facility f
            JOIN f.configs fc WITH b.start BETWEEN fc.start AND fc.end
            JOIN f.facility_group fg
            JOIN fg.configs fgc WITH b.start BETWEEN fgc.start AND fgc.end
            LEFT JOIN p.contract_signatures s WITH s.is_cancelled = false
            LEFT JOIN s.contract c WITH c.id = :contractId
            GROUP BY p.id
            ORDER BY fgname, fname
            "
        )
            ->setParameter('date', $date)
            ->setParameter('contractId', $contractId)
            ->getResult();


        $personIds = [];
        foreach ($rows as $row) {
            $personIds[] = $row['id'];
        }

        if (empty($personIds)) {
            $personIds[] = null;
        }

        $applicationResult = $this->uhdsEm->createQuery(
            '
            SELECT a.personId person_id, t.slug
            FROM Uhds\Entity\HousingApplication\Application\Application a
            JOIN a.template t
            WHERE a.state IN (:states) AND
                  a.personId IN (:personIds)
            '
        )
            ->setParameter('states', ['processed'])
            ->setParameter('personIds', $personIds)
            ->getResult();

        $personIdToApplicationMap = [];
        foreach ($applicationResult as $row) {
            $personIdToApplicationMap[$row['person_id']][] = $row['slug'];
        }

        $ret = [
            [
                'OSU ID',
                'Processed applications',
                'Last name',
                'First name',
                'Facility booking start date',
                'Facility booking end date',
                'Facility group name',
                'Facility name',
                'Has valid contract signature?',
            ]
        ];

        foreach ($rows as $row) {
            if (isset($personIdToApplicationMap[$row['id']])) {
                $templates = $personIdToApplicationMap[$row['id']];
            } else {
                $templates = [];
            }

            $isSignatureValid = false;
            if ($row['is_signed'] && !$row['requires_cosigned'] || $row['requires_cosigned'] && $row['is_cosigned']) {
                $isSignatureValid = true;
            }

            $ret[] = [
                $row['osuid'],
                implode(', ', $templates),
                $row['family_name'],
                $row['given_name'],
                $row['start']->format('Y-m-d'),
                $row['end']->format('Y-m-d'),
                $row['fgname'],
                $row['fname'],
                $isSignatureValid ? 'Yes' : 'No',
            ];
        }

        return $ret;
    }
}
