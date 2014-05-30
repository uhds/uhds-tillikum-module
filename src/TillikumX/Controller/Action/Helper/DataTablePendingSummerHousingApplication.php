<?php

namespace TillikumX\Controller\Action\Helper;

use DateTime;
use Vo\DateRange;
use Zend_Controller_Action_Helper_Abstract as AbstractHelper;

abstract class DataTablePendingSummerHousingApplication extends AbstractHelper
{
    abstract public function fetchApplications();

    abstract public function getBookingData($application);

    abstract public function getMealplanData($application);

    abstract public function getLastSpringBookingDate();

    abstract public function getFirstFallBookingDate();

    abstract public function getBookingStartDate();

    abstract public function getBookingEndDate();

    public function dataTablePendingSummerHousingApplication()
    {
        $ac = $this->_actionController;
        $view = $ac->view;

        $sm = $ac->getServiceManager();
        $tillikumEm = $sm->get('doctrine.entitymanager.orm_default');
        $uhdsEm = $sm->get('doctrine.entitymanager.orm_uhds');

        $peopleBookedById = [];

        $bookingRange = new DateRange(
            $this->getBookingStartDate(),
            $this->getBookingEndDate()
        );

        $bookedPeople = $tillikumEm->createQuery(
            "
            SELECT p.id
            FROM Tillikum\Entity\Person\Person p
            JOIN p.bookings b
            WHERE b.start <= :end AND b.end >= :start
            "
        )
            ->setParameter('start', $bookingRange->getStart())
            ->setParameter('end', $bookingRange->getEnd())
            ->getResult();

        $bookedPersonHash = [];
        foreach ($bookedPeople as $bookedPerson) {
            $bookedPersonHash[$bookedPerson['id']] = true;
        }

        $facilityGroups = $tillikumEm->createQuery(
            "
            SELECT fg.id, fgc.name
            FROM Tillikum\Entity\FacilityGroup\FacilityGroup fg
            JOIN fg.configs fgc
            WHERE fgc.start <= :now and fgc.end >= :now
            "
        )
            ->setParameter('now', new DateTime())
            ->getResult();

        $facilityGroupNamesByIds = [];
        foreach ($facilityGroups as $facilityGroup) {
            $facilityGroupNamesByIds[$facilityGroup['id']] = $facilityGroup['name'];
        }

        $applications = $this->fetchApplications();

        $personIds = [];
        foreach ($applications as $application) {
            $personIds[] = $application->getPersonId();
        }

        $people = [];
        if (count($personIds) > 0) {
            $people = $tillikumEm->createQuery(
                '
                SELECT partial p.{id, birthdate, gender, family_name, given_name, middle_name, display_name, osuid}
                FROM TillikumX\Entity\Person\Person p
                WHERE p.id IN (:personIds)
                '
            )
                ->setParameter('personIds', $personIds)
                ->getResult();
        }

        $ret = [];
        foreach ($applications as $application) {
            if (array_key_exists($application->getPersonId(), $bookedPersonHash)) {
                continue;
            }

            $buildingPreferences = $application->getSection('Building')->getPreferences();

            $prefArray = [];
            foreach ($buildingPreferences as $pref) {
                $options = $pref->getOptions();

                $opts = [];
                foreach ($options as $option) {
                    $opts[] = $option->getCode();
                }

                $prefArray[] = $view->escape(
                    sprintf(
                        '%s: %s %s%s',
                        $pref->getNumber(),
                        $facilityGroupNamesByIds[$pref->getBuildingId()],
                        $pref->getType(),
                        $opts ? ' (' . implode(', ', $opts) . ')' : ''
                    )
                );
            }
            natsort($prefArray);
            $prefString = implode("\n", $prefArray);

            $questionnaire = $application->getSection('Questionnaire');

            $facilityBookingQueryString = http_build_query(
                [
                    'json' => json_encode($this->getBookingData($application))
                ]
            );

            $mealplanBookingQueryString = http_build_query(
                [
                    'json' => json_encode($this->getMealplanData($application))
                ]
            );

            $person = $tillikumEm->find(
                'TillikumX\Entity\Person\Person',
                $application->getPersonId()
            );

            $diningSection = $application->getSection('Dining');

            $springBooking = $person->bookings->filter(
                \Tillikum\Common\Booking\Bookings::createIncludedDateFilter(
                    $this->getLastSpringBookingDate()
                )
            )
                ->first();

            $fallBooking = $person->bookings->filter(
                \Tillikum\Common\Booking\Bookings::createIncludedDateFilter(
                    $this->getFirstFallBookingDate()
                )
            )
                ->first();

            $attendanceSection = $application->getSection('Attendance');

            $attendanceStart = empty($attendanceSection->getStart()) ? null : $attendanceSection->getStart();
            $attendanceEnd = empty($attendanceSection->getEnd()) ? null : $attendanceSection->getEnd();

            $ret[] = [
                'person_uri' => $ac->getHelper('Url')->direct(
                    'view',
                    'person',
                    'person',
                    [
                        'id' => $person ? $person->id : '',
                    ]
                ),
                'name' => $person ? $person->display_name : '',
                'osuid' => $person ? $person->osuid : '',
                'age' => $person ? $person->getAge(new DateTime(date('Y-m-d'))) : '',
                'gender' => $person ? $person->gender : '',
                'spring_assignment' => $springBooking ? implode(' ', $springBooking->facility->getNamesOnDate($this->getLastSpringBookingDate())) : '',
                'fall_assignment' => $fallBooking ? implode(' ', $fallBooking->facility->getNamesOnDate($this->getFirstFallBookingDate())) : '',
                'attendance_start' => $attendanceStart,
                'attendance_end' => $attendanceEnd,
                'preferences' => $prefString,
                'application_completed_at' => $application->getLatestCompletedAt(),
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

        return $ret;
    }
}
