<?php

namespace TillikumX\Controller\Action\Helper;

use DateTime;
use Vo\DateRange;
use Zend_Controller_Action_Helper_Abstract as AbstractHelper;

class DataTablePendingInto extends AbstractHelper
{
    protected static $bannerToRateCodes = array(
        'IHS1' => 'NTOS',
        'IHS2' => 'NTOD',
        'IHS3' => 'NTODPR',
        'IHS4' => 'NTOS',
        'IHST' => 'NTOH',
    );

    public function dataTablePendingInto()
    {
        $ac = $this->_actionController;
        $view = $ac->view;

        // Fetch Salesforce reservation information
        $commonDb = \Uhds_Db::factory('common');

        $reservationSql = $commonDb->select()
            ->from('into_salesforce_person', array('osuid'))
            ->where('into_salesforce_person.osuid IN (?)', $commonDb->select()
                ->from('into_salesforce_person_reservation', 'person_osuid')
                ->where('end >= ?', date('Y-m-d'))
            );

        $reservationRowByOsuids = array();
        foreach ($commonDb->fetchAll($reservationSql) as $row) {
            $reservationRowByOsuids[$row['osuid']] = $row;
        }

        $applicationGateway = new \Uhds\Model\HousingApplication\ApplicationGateway();

        $rows = array();
        foreach ($reservationRowByOsuids as $osuid => $row) {
            $person = $ac->getEntityManager()
                ->getRepository('TillikumX\Entity\Person\Person')
                ->findOneByOsuid($osuid);

            if ($person === null) {
                continue;
            }

            $application = $applicationGateway->fetch(
                $applicationGateway->generateId($person->id, 'into')
            );

            // Skip if the application doesn't exist or isn't processed
            if ($application === null || !$application->isProcessed()) {
                continue;
            }

            $isSmoker = null;
            if (isset($application->roommateprofile)) {
                foreach ($application->roommateprofile->toArray() as $k => $v) {
                    if (substr($k, 0, 3) === 'yn_') {
                        $isSmoker = substr($v, 0, 1) === 'y' ? true : false;
                    }
                }
            }

            $reservationRange = new DateRange(
                $person->getIntoHousingStart(),
                $person->getIntoHousingEnd()
            );

            unset($bookingRange);
            if ($person !== null && count($person->bookings) > 0) {
                $bookingRange = new DateRange(
                    new DateTime('9999-01-01'),
                    new DateTime('1000-01-01')
                );

                foreach ($person->bookings as $booking) {
                    $bookingRange = new DateRange(
                        min($bookingRange->getStart(), $booking->start),
                        max($bookingRange->getEnd(), $booking->end)
                    );
                }

                // Skip reservation ranges that are booked over
                if ($bookingRange->getEnd() >= $reservationRange->getEnd()) {
                    continue;
                }

                if (!$bookingRange->overlaps($reservationRange)) {
                    unset($bookingRange);
                }
            }

            $facilityBookingQueryString = http_build_query(
                array(
                    'json' => json_encode(
                        $this->getBookingData(
                            $reservationRange->getStart(),
                            $reservationRange->getEnd(),
                            $person
                        )
                    )
                )
            );

            $mealplanBookingQueryString = http_build_query(
                array(
                    'json' => json_encode(
                        $this->getMealplanData(
                            $reservationRange->getStart(),
                            $reservationRange->getEnd(),
                            $person
                        )
                    )
                )
            );

            $rows[] = array(
                'person_uri' => $ac->getHelper('Url')->direct(
                    'view',
                    'person',
                    'person',
                    array(
                        'id' => $person->id
                    )
                ),
                'name' => $person ? $person->display_name : '',
                'osuid' => $person ? $person->osuid : '',
                'age' => $person ? date_diff($person->birthdate, new DateTime(date('Y-m-d')))->y : '',
                'gender' => $person ? $person->gender : '',
                'booking_start' => isset($bookingRange) ? $bookingRange->getStart() : null,
                'booking_end' => isset($bookingRange) ? $bookingRange->getEnd() : null,
                'reservation_start' => $reservationRange->getStart(),
                'reservation_end' => $reservationRange->getEnd(),
                'is_smoker' => $isSmoker === null ? '?' : ($isSmoker ? 'Y' : 'N'),
                'housing_code' => isset($person) ? $person->getIntoHousingCode() : '',
                'facility_booking_uri' => $ac->getHelper('Url')->direct(
                    'index',
                    'create',
                    'booking',
                    array(
                        'pid' => $person ? $person->id : '',
                    )
                ) . '?' . $view->escape($facilityBookingQueryString),
                'mealplan_booking_uri' => $ac->getHelper('Url')->direct(
                    'index',
                    'create',
                    'mealplan',
                    array(
                        'pid' => $person ? $person->id : '',
                    )
                ) . '?' . $view->escape($mealplanBookingQueryString),
            );
        }

        return $rows;
    }

    public function direct()
    {
        return $this->dataTablePendingInto();
    }

    public function getBookingData(DateTime $start, DateTime $end, $person)
    {
        $ac = $this->_actionController;

        $i = $person->getIntoData();

        $housingCode = null;
        if (null !== $i) {
            foreach ($i['reservations'] as $reservation) {
                if ($reservation['end'] >= $start->format('Y-m-d')) {
                    $housingCode = $reservation['housing_code'];
                }
            }
        }

        $suffix = '';
        if ($person->isIntoAcademicEnglish() ||
            $person->isIntoPathways() ||
            $person->isIntoPathwaysGraduate()) {
            $suffix = 'AEPW';
        } elseif ($person->isIntoGeneralEnglish()) {
            $suffix = 'GE';
        } elseif ($i['person']['current_program'] === 'SAWE') {
            $suffix = 'AEPW';
        }

        if ($housingCode) {
            $rate = self::$bannerToRateCodes[$housingCode];
        }

        $ruleId = $rate . $suffix;

        // @todo remove reference to old_id
        $rule = $ac->getEntityManager()
            ->getRepository('Tillikum\Entity\Billing\Rule\Rule')
            ->findOneBy(
                array(
                    'old_id' => $ruleId,
                )
            );

        return array(
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
            'billing' => array(
                'rates' => array(
                    array(
                        'delete_me' => false,
                        'rule_id' => $rule ? $rule->id : '',
                        'start' => $start->format('Y-m-d'),
                        'end' => $end->format('Y-m-d'),
                    )
                ),
            ),
        );
    }

    public function getMealplanData(DateTime $start, DateTime $end, $person)
    {
        $ac = $this->_actionController;

        $i = $person->getIntoData();

        $housingCode = null;
        if (null !== $i) {
            foreach ($i['reservations'] as $reservation) {
                if ($reservation['end'] >= $start->format('Y-m-d')) {
                    $housingCode = $reservation['housing_code'];
                }
            }
        }

        $rate = '';
        if ($housingCode === 'IHST') {
            $rate = 'NTO4H';
        } else {
            if ($person->isIntoAcademicEnglish()
                || $person->isIntoPathways()
                || $person->isIntoPathwaysGraduate()
            ) {
                $rate = 'NTO3AEPW';
            } elseif ($person->isIntoGeneralEnglish()) {
                $rate = 'NTO3GE';
            } elseif ($i['person']['current_program'] === 'SAWE') {
                $rate = 'NTO3AEPW';
            }
        }

        $ruleId = $rate;

        // @todo remove reference to old_id
        $rule = $ac->getEntityManager()
            ->getRepository('Tillikum\Entity\Billing\Rule\Rule')
            ->findOneBy(
                array(
                    'old_id' => $ruleId,
                )
            );

        return array(
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
            'billing' => array(
                'rates' => array(
                    array(
                        'delete_me' => false,
                        'rule_id' => $rule ? $rule->id : '',
                        'start' => $start->format('Y-m-d'),
                        'end' => $end->format('Y-m-d'),
                    ),
                ),
            ),
        );
    }
}
