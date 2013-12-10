<?php

namespace TillikumX\Controller\Action\Helper;

use DateTime;
use Vo\DateRange;
use Zend_Controller_Action_Helper_Abstract as AbstractHelper;

class DataTablePendingInto extends AbstractHelper
{
    public function dataTablePendingInto()
    {
        $ac = $this->_actionController;
        $view = $ac->view;

        $sm = $ac->getServiceManager();

        $commonDb = \Uhds_Db::factory('common');
        $tillikumEm = $sm->get('doctrine.entitymanager.orm_default');
        $uhdsEm = $sm->get('doctrine.entitymanager.orm_uhds');

        $reservationSql = $commonDb->select()
            ->from('into_salesforce_person', ['osuid', 'is_smoker'])
            ->where('into_salesforce_person.osuid IN (?)', $commonDb->select()
                ->from('into_salesforce_person_reservation', 'person_osuid')
                ->where('end >= ?', date('Y-m-d'))
            );

        $reservationRowByOsuids = [];
        foreach ($commonDb->fetchAll($reservationSql) as $row) {
            $reservationRowByOsuids[$row['osuid']] = $row;
        }

        $rows = [];
        foreach ($reservationRowByOsuids as $osuid => $row) {
            $person = $tillikumEm->getRepository('TillikumX\Entity\Person\Person')
                ->findOneByOsuid($osuid);

            if ($person === null) {
                continue;
            }

            $application = $uhdsEm->getRepository('Uhds\Entity\HousingApplication\Application\Application')
                ->findOneBy([
                    'personId' => $person->id,
                    'template' => 13,
                    'state' => 'processed',
                ]);

            if (!$application) {
                continue;
            }

            $isSmoker = (bool) $row['is_smoker'] ? 'Yes' : 'No';

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
                [
                    'json' => json_encode(
                        $this->getBookingData(
                            $reservationRange->getStart(),
                            $reservationRange->getEnd(),
                            $person
                        )
                    )
                ]
            );

            $mealplanBookingQueryString = http_build_query(
                [
                    'json' => json_encode(
                        $this->getMealplanData(
                            $reservationRange->getStart(),
                            $reservationRange->getEnd(),
                            $person
                        )
                    )
                ]
            );

            $rows[] = [
                'person_uri' => $ac->getHelper('Url')->direct(
                    'view',
                    'person',
                    'person',
                    [
                        'id' => $person->id
                    ]
                ),
                'name' => $person ? $person->display_name : '',
                'osuid' => $person ? $person->osuid : '',
                'age' => ($person && $person->birthdate) ? date_diff($person->birthdate, new DateTime(date('Y-m-d')))->y : '',
                'gender' => $person ? $person->gender : '',
                'booking_start' => isset($bookingRange) ? $bookingRange->getStart() : null,
                'booking_end' => isset($bookingRange) ? $bookingRange->getEnd() : null,
                'reservation_start' => $reservationRange->getStart(),
                'reservation_end' => $reservationRange->getEnd(),
                'is_smoker' => $isSmoker,
                'housing_code' => isset($person) ? $person->getIntoHousingCode() : '',
                'facility_booking_uri' => $ac->getHelper('Url')->direct(
                    'index',
                    'create',
                    'booking',
                    [
                        'pid' => $person ? $person->id : '',
                    ]
                ) . '?' . $view->escape($facilityBookingQueryString),
                'mealplan_booking_uri' => $ac->getHelper('Url')->direct(
                    'index',
                    'create',
                    'mealplan',
                    [
                        'pid' => $person ? $person->id : '',
                    ]
                ) . '?' . $view->escape($mealplanBookingQueryString),
            ];
        }

        return $rows;
    }

    public function direct()
    {
        return $this->dataTablePendingInto();
    }

    public function getBookingData(DateTime $start, DateTime $end, $person)
    {
        return [
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
        ];
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

        $em = $ac->getEntityManager();

        $rule = null;
        if ($housingCode === 'IHST') {
            $rule = $em->find('Tillikum\Entity\Billing\Rule\Rule', 57);
        } elseif ($person->isIntoAcademicEnglish() || $person->isIntoPathways() || $person->isIntoPathwaysGraduate()) {
            $rule = $em->find('Tillikum\Entity\Billing\Rule\Rule', 52);
        } elseif ($person->isIntoGeneralEnglish()) {
            $rule = $em->find('Tillikum\Entity\Billing\Rule\Rule', 53);
        } elseif ($i['person']['current_program'] === 'SAWE') {
            $rule = $em->find('Tillikum\Entity\Billing\Rule\Rule', 52);
        }

        return [
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
            'billing' => [
                'rates' => [
                    [
                        'delete_me' => false,
                        'rule_id' => $rule ? $rule->id : '',
                        'start' => $start->format('Y-m-d'),
                        'end' => $end->format('Y-m-d'),
                    ],
                ],
            ],
        ];
    }
}
