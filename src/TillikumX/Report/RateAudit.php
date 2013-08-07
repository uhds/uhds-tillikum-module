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

class RateAudit extends AbstractReport
{
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getDescription()
    {
        return 'Facility and meal plan booking rate audit.';
    }

    public function getFormClass()
    {
        return 'TillikumX\Form\Report\RateAudit';
    }

    public function getName()
    {
        return 'Rate audit';
    }

    public function generate()
    {
        $parameters = $this->getParameters();

        $date = new DateTime($parameters['date']);

        $rows = $this->em->createQuery(
            "
            SELECT p.osuid, p.family_name, p.given_name, p.gender,
                   fb.start fbstart, fb.end fbend,
                   fr.start frstart, fr.end frend,
                   frule.description frdescription,
                   fc.name fname, fgc.name fgname,
                   mb.start mbstart, mb.end mbend,
                   mr.start mrstart, mr.end mrend,
                   mrule.description mrdescription,
                   mbp.name mealplan
            FROM TillikumX\Entity\Person\Person p
            JOIN p.bookings fb
            JOIN fb.billing fbi
            JOIN fbi.rates fr
            JOIN fr.rule frule
            JOIN fb.facility f
            JOIN f.configs fc
            JOIN f.facility_group fg
            JOIN fg.configs fgc
            LEFT JOIN p.mealplans mb
            LEFT JOIN mb.mealplan mbp
            LEFT JOIN mb.billing mbi
            LEFT JOIN mbi.rates mr
            LEFT JOIN mr.rule mrule
            WHERE fc.start <= fr.start AND fc.end >= fr.end AND
            fgc.start <= fr.start AND fgc.end >= fr.end AND
            :date BETWEEN fr.start AND fr.end AND
            (mb IS NULL OR :date BETWEEN mr.start AND mr.end)
            "
        )
            ->setParameter('date', $date)
            ->getResult();

        $ret = [
            [
                'OSU ID',
                'Last name',
                'First name',
                'Gender',
                'Facility group',
                'Facility',
                'Facility booking start',
                'Facility booking end',
                'Facility rate description',
                'Facility rate start',
                'Facility rate end',
                'Meal plan',
                'Meal plan booking start',
                'Meal plan booking end',
                'Meal plan rate description',
                'Meal plan rate start',
                'Meal plan rate end',
            ]
        ];

        foreach ($rows as $row) {
            $ret[] = [
                $row['osuid'],
                $row['family_name'],
                $row['given_name'],
                $row['gender'],
                $row['fgname'],
                $row['fname'],
                $row['fbstart']->format('Y-m-d'),
                $row['fbend']->format('Y-m-d'),
                $row['frdescription'],
                $row['frstart']->format('Y-m-d'),
                $row['frend']->format('Y-m-d'),
                $row['mealplan'],
                $row['mbstart'] ? $row['mbstart']->format('Y-m-d') : '',
                $row['mbend'] ? $row['mbend']->format('Y-m-d') : '',
                $row['mrdescription'],
                $row['mrstart'] ? $row['mrstart']->format('Y-m-d') : '',
                $row['mrend'] ? $row['mrend']->format('Y-m-d') : '',
            ];
        }

        return $ret;
    }
}
