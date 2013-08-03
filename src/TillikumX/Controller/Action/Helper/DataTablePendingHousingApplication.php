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

        $em = $ac->getEntityManager();

        $peopleBookedById = array();

        $bookingRange = new DateRange(
            $this->getBookingStartDate(),
            $this->getBookingEndDate()
        );

        $bookedPeople = $em->createQuery(
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

        $bookedPersonHash = array();
        foreach ($bookedPeople as $bookedPerson) {
            $bookedPersonHash[$bookedPerson['id']] = true;
        }

        $rmnGateway = new \Uhds\Model\HousingApplication\RmnGateway();

        $facilityGroups = $em->createQuery(
            "
            SELECT fg.id, fgc.name
            FROM Tillikum\Entity\FacilityGroup\FacilityGroup fg
            JOIN fg.configs fgc
            WHERE fgc.start <= :now and fgc.end >= :now
            "
        )
            ->setParameter('now', new DateTime())
            ->getResult();

        $facilityGroupNamesByIds = array();
        foreach ($facilityGroups as $facilityGroup) {
            $facilityGroupNamesByIds[$facilityGroup['id']] = $facilityGroup['name'];
        }

        $applications = $this->fetchApplications();

        $personIds = array();
        foreach ($applications as $application) {
            $personIds[] = $application->person_id;
        }

        if (count($personIds) > 0) {
            $people = $em->createQuery(
                "
                SELECT partial p.{id, birthdate, gender, family_name, given_name, middle_name, display_name, osuid}
                FROM TillikumX\Entity\Person\Person p
                WHERE p.id IN (:personIds)
                "
            )
                ->setParameter('personIds', $personIds)
                ->getResult();
        } else {
            $people = array();
        }

        $ret = array();
        foreach ($applications as $application) {
            if (array_key_exists($application->person_id, $bookedPersonHash)) {
                continue;
            }

            $personalProfile = $application->personalprofile;
            $prefs = $application->building;

            $prefArray = array();
            for ($i = 1; $i <= 5; $i++) {
                $buildingIdPref = "preference_$i" . '_building_id';
                $roomtypePref = "preference_$i" . '_roomtype';
                $optionsPref = "preference_$i" . '_options';

                if (empty($prefs->$buildingIdPref)) {
                    continue;
                }

                $prefArray[] = $view->escape(
                    sprintf(
                        '%s: %s (%s)',
                        $i,
                        $facilityGroupNamesByIds[$prefs->$buildingIdPref],
                        $prefs->$roomtypePref . (isset($prefs->$optionsPref) ? '; ' . implode(', ', (array) $prefs->$optionsPref) : '')
                    )
                );
            }
            $prefString = implode("\n", $prefArray);

            $roommateIds = $rmnGateway->fetchMutualConfirmedIds($application->id);
            $roommatePersonIds = array();
            foreach ($roommateIds as $roommateId) {
                list($roommatePersonId, $templateId) = explode('-', $roommateId, 2);
                $roommatePersonIds[] = $roommatePersonId;
            }

            $roommateRowData = array();
            if (count($roommatePersonIds) > 0) {
                $roommates = $em->createQuery(
                    "
                    SELECT partial p.{id, family_name, given_name, middle_name, display_name, osuid}
                    FROM TillikumX\Entity\Person\Person p
                    WHERE p.id IN (:personIds)
                    "
                )
                    ->setParameter('personIds', $roommatePersonIds)
                    ->getResult();

                foreach ($roommates as $roommate) {
                    $roommateRowData[] = array(
                        'text' => sprintf('%s (%s)', $roommate->display_name, $roommate->osuid),
                        'uri' => $ac->getHelper('Url')->direct(
                            'view',
                            'person',
                            'person',
                            array(
                                'id' => $roommate->id,
                            )
                        )
                    );
                }
            }

            $ynString = '';
            foreach ($application->roommateprofile->toArray() as $k => $v) {
                if (substr($k, 0, 3) === 'yn_') {
                    $ynString .= $v;
                }
            }

            $facilityBookingQueryString = http_build_query(
                array(
                    'json' => json_encode($this->getBookingData($application))
                )
            );

            $mealplanBookingQueryString = http_build_query(
                array(
                    'json' => json_encode($this->getMealplanData($application))
                )
            );

            $person = $em->find(
                'TillikumX\Entity\Person\Person',
                $application->person_id
            );

            $ret[] = array(
                'person_uri' => $ac->getHelper('Url')->direct(
                    'view',
                    'person',
                    'person',
                    array(
                        'id' => $application->person_id
                    )
                ),
                'name' => $person ? $person->display_name : '',
                'osuid' => $person ? $person->osuid : '',
                'age' => $person ? date_diff($person->birthdate, new DateTime(date('Y-m-d')))->y : '',
                'gender' => $person ? $person->gender : '',
                'dining_plan' => isset($application->dining) ? $application->dining->plan : '',
                'preferences' => $prefString,
                'profile' => $ynString,
                'roommates' => $roommateRowData,
                'application_completed_at' => $application->completed_at,
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

        return $ret;
    }
}
