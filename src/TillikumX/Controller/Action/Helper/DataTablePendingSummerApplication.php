<?php

abstract class TillikumX_Controller_Action_Helper_DataTablePendingSummerApplication
    extends TillikumX_Controller_Action_Helper_DataTablePendingHousingApplication
{
    public function dataTablePendingSummerApplication()
    {
        $ac = $this->_actionController;
        $view = $ac->view;

        $db = Uhds_CouchDb_Database::factory('housing_application');

        $peopleBookedById = array();

        $bookingRange = new \Vo\DateRange(
            $this->getBookingStartDate(),
            $this->getBookingEndDate()
        );

        $personGateway = new \TillikumX\Model\PersonGateway();
        $facilityBookingGateway = new \Tillikum\Model\Booking\FacilityBookingGateway();
        $basicBookings = $facilityBookingGateway->fetchBasicByEnd($this->getBookingStartDate());

        foreach ($basicBookings as $basicBooking) {
            if ($bookingRange->overlaps(new \Vo\DateRange($basicBooking['start'], $basicBooking['end']))) {
                $peopleBookedById[$basicBooking['person_id']] = true;
            }
        }

        $jsarray = array();
        do {
            $done = false;
            $startkey = isset($startkey) ? $startkey : null;
            $result = $this->getView($startkey, self::ROWS_AT_A_TIME + 1);

            $applications = $result->extractDocuments();
            foreach (new LimitIterator(new ArrayIterator($applications), 0, self::ROWS_AT_A_TIME) as $application) {
                if (array_key_exists(substr($application->_id, 0, 9), $peopleBookedById)) {
                    continue;
                }

                $applicant = $application->personalprofile->data->applicant;

                $person = $personGateway->fetchByOsuid(substr($application->_id, 0, 9));

                if (null !== $person) {
                    $spring_room = null;
                    $spring_booking = $person->bookings->getByDate(new DateTime('2011-06-10'));
                    if (null !== $spring_booking) {
                        $spring_room = $view->fullFacilityName($ac->getHelper('FullFacilityName')->direct($spring_booking->facility, null, new DateTime('2011-06-10')));
                    }

                    $fall_room = null;
                    $fall_booking = $person->bookings->getByDate(new DateTime('2011-09-18'));
                    if (null !== $fall_booking) {
                        $fall_room = $view->fullFacilityName($ac->getHelper('FullFacilityName')->direct($fall_booking->facility, null, new DateTime('2011-09-18')));
                    }
                }

                $prefs = $application->building->data->preferences;
                $pref_array = array();
                if (isset($prefs)) {
                    $i = 1;
                    foreach ($prefs as $building => $pref) {
                        $pref_array[] = $view->escape(
                            sprintf(
                                '%s: %s (%s)',
                                $i,
                                $building,
                                $pref->roomtype . (isset($pref->options) ? '; ' . implode(', ', $pref->options) : '')
                            )
                        );
                        $i++;
                    }
                }
                $pref_string = implode('<br />', $pref_array);

                $booking_uri_qstr = http_build_query(
                    array(
                        'json' => Zend_Json::encode(
                            array(
                                'facility' => $this->getBookingData($application),
                                'mealplan' => $this->getMealplanData($application)
                            )
                        )
                    )
                );

                $start = empty($application->attendance->data->start) ? '' : $application->attendance->data->start;
                $end = empty($application->attendance->data->end) ? '' : $application->attendance->data->end;

                $jsarray[] = array(
                    'osuid' => substr($application->_id, 0, 9),
                    'person_uri' => $view->url(
                        array(
                            'module' => 'person',
                            'controller' => 'person',
                            'action' => 'view',
                            'id' => substr($application->_id, 0, 9)
                        ),
                        null,
                        true
                    ),
                    'name' => $view->escape(
                        $view->formatPersonName(
                            $applicant->lastname,
                            $applicant->firstname,
                            $applicant->middlename
                        )
                    ),
                    'age' => age_in_years(new DateTime($applicant->birthdate)),
                    'gender' => $view->escape($applicant->gender),
                    'spring_room' => isset($spring_room) ? $spring_room : '',
                    'fall_room' => isset($fall_room) ? $fall_room : '',
                    'desired_start_string' => $view->formatDate($start),
                    'desired_start' => $start,
                    'desired_end_string' => $view->formatDate($end),
                    'desired_end' => $end,
                    'preferences' => $pref_string,
                    'application_date_string' => $view->formatDateTime($application->completed),
                    'application_date' => $application->completed,
                    'booking' => 'Book',
                    'booking_uri' => $view->url(
                        array(
                            'module' => 'booking',
                            'controller' => 'create',
                            'action' => 'index',
                            'pid' => substr($application->_id, 0, 9)
                        ),
                        null,
                        true
                    ) . '?' . $view->escape($booking_uri_qstr)
                );
            }

            if (count($result->rows) > self::ROWS_AT_A_TIME) {
                $startkey = $result->rows[self::ROWS_AT_A_TIME]->key;
            } else {
                $done = true;
            }
        } while (!$done);

        return Zend_Json::encode($jsarray);
    }

    public function direct()
    {
        return $this->dataTablePendingSummerApplication();
    }
}
