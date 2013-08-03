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

class Holds extends AbstractReport
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getDescription()
    {
        return 'List facility holds active on a given date.';
    }

    public function getFormClass()
    {
        return 'TillikumX\Form\Report\Holds';
    }

    public function getName()
    {
        return 'Holds';
    }

    public function generate()
    {
        $parameters = $this->getParameters();

        $holds = $this->em->createQuery(
            "
            SELECT h.start, h.end, h.space, h.gender, h.description,
                   h.created_at, h.created_by,
                   fc.name fname, fc.capacity,
                   fgc.name fgname
            FROM Tillikum\Entity\Facility\Hold\Hold h
            JOIN h.facility f
            JOIN f.configs fc
            JOIN f.facility_group fg
            JOIN fg.configs fgc
            WHERE fc.start <= h.start AND fc.end >= h.end AND
                  fgc.start <= h.start AND fgc.end >= h.end AND
                  h.start <= :date AND h.end >= :date AND
                  fg.id IN (:facilityGroupIds)
            ORDER BY fgname, fname
            "
        )
            ->setParameter('date', $parameters['date'])
            ->setParameter('facilityGroupIds', $parameters['facility_groups'])
            ->getResult();

        $ret = array(
            array(
                'Facility',
                'Facility group',
                'Room capacity',
                'Held spaces',
                'Hold gender',
                'Hold description',
                'Hold start date',
                'Hold end date'
            )
        );

        foreach ($holds as $hold) {
            $ret[] = array(
                $hold['fname'],
                $hold['fgname'],
                $hold['capacity'],
                $hold['space'],
                $hold['gender'],
                $hold['description'],
                $hold['start']->format('n/j/Y'),
                $hold['end']->format('n/j/Y'),
            );
        }

        return $ret;
    }
}
