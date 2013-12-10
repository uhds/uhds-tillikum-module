<?php

namespace TillikumX\Controller\Action\Helper;

use DateTime;
use Vo\DateRange;
use Zend_Controller_Action_Helper_Abstract as AbstractHelper;

abstract class DataTablePendingHousingApplication extends AbstractHelper
{
    public abstract function fetchApplications();

    public abstract function getBookingData($application);

    public abstract function getMealplanData($application);

    public abstract function getBookingStartDate();

    public abstract function getBookingEndDate();

    public function dataTablePendingHousingApplication()
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
            $ynArray = [];

            if ($questionnaire) {
                $ynArray[] = $questionnaire->getSmoker();
                $ynArray[] = $questionnaire->getMorning();
                $ynArray[] = $questionnaire->getNight();
                $ynArray[] = $questionnaire->getClean();
                $ynArray[] = $questionnaire->getTv();
                $ynArray[] = $questionnaire->getStudiesWithMusic();
                $ynArray[] = $questionnaire->getStudiesInSilence();
                $ynArray[] = $questionnaire->getStudiesInRoom();
                $ynArray[] = $questionnaire->getSocial();
                $ynArray[] = $questionnaire->getPrivate();
                $ynArray[] = $questionnaire->getGuests();
                $ynArray[] = $questionnaire->getFit();
            }
            $ynString = implode('', $ynArray);

            // @todo implement this
            $roommatePersonIds = [];

            $roommateRowData = [];
            if (count($roommatePersonIds) > 0) {
                $roommates = $tillikumEm->createQuery(
                    "
                    SELECT partial p.{id, family_name, given_name, middle_name, display_name, osuid}
                    FROM TillikumX\Entity\Person\Person p
                    WHERE p.id IN (:personIds)
                    "
                )
                    ->setParameter('personIds', $roommatePersonIds)
                    ->getResult();

                foreach ($roommates as $roommate) {
                    $roommateRowData[] = [
                        'text' => sprintf('%s (%s)', $roommate->display_name, $roommate->osuid),
                        'uri' => $ac->getHelper('Url')->direct(
                            'view',
                            'person',
                            'person',
                            [
                                'id' => $roommate->id,
                            ]
                        )
                    ];
                }
            }

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
                'dining_plan' => $diningSection ? $diningSection->getPlanId() : '',
                'preferences' => $prefString,
                'profile' => $ynString,
                'roommates' => $roommatePersonIds,
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
