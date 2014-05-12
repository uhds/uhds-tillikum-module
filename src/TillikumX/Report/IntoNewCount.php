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
use Tillikum\Report\AbstractReport;

class IntoNewCount extends AbstractReport
{
    public function getDescription()
    {
        return 'Counts of INTO reservations on active on a given date.';
    }

    public function getFormClass()
    {
        return 'TillikumX\Form\Report\IntoNewCount';
    }

    public function getName()
    {
        return 'INTO new counts';
    }

    public function generate()
    {
        $commonDb = \Uhds_Db::factory('common');

        $parameters = $this->getParameters();

        $date = new DateTime($parameters['date']);

        $sql = $commonDb->select()
            ->from(
                'into_salesforce_person',
                array('gender')
            )
            ->join(
                'into_salesforce_person_reservation',
                'into_salesforce_person.osuid = into_salesforce_person_reservation.person_osuid',
                array('count' => new \Zend_Db_Expr('COUNT(*)'))
            )
            ->where('into_salesforce_person_reservation.start <= ?', $date->format('Y-m-d'))
            ->where('into_salesforce_person_reservation.end >= ?', $date->format('Y-m-d'))
            ->group('into_salesforce_person.gender');

        $rows = $commonDb->fetchAll($sql);

        $ret = array(
            array(
                'Date',
                'Female reservations',
                'Male reservations',
                'Total reservations',
                'Female percentage of total',
                'Male percentage of total'
            )
        );

        $body = array(
            $date->format('Y-m-d'),
            0,
            0,
            0,
            '0%',
            '0%'
        );

        foreach ($rows as $row) {
            switch ($row['gender']) {
                case 'F':
                    $body[1] = $row['count'];
                    break;
                case 'M':
                    $body[2] = $row['count'];
                    break;
            }
        }

        $body[3] = $body[1] + $body[2];

        if ($body[3] > 0) {
            $body[4] = round($body[1] / $body[3] * 100) . '%';
            $body[5] = round($body[2] / $body[3] * 100) . '%';
        }

        $ret[] = $body;

        return $ret;
    }
}
