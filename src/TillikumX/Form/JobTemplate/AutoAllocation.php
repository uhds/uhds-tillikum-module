<?php

namespace TillikumX\Form\JobTemplate;

class AutoAllocation extends \Tillikum_Form
{
    public function init()
    {
        $isDebug = new \Zend_Form_Element_Checkbox(
            'is_debug',
            array(
                'label' => 'Include debugging output (this produces a large file)?'
            )
        );

        $minMatchMetric = new \Tillikum_Form_Element_Number(
            'min_match_metric',
            array(
                'label' => 'What is the minimum match percentage for applicants to be paired?',
                'required' => true
            )
        );

        $facilityBookingStart = new \Tillikum_Form_Element_Date(
            'facility_booking_start',
            array(
                'label' => 'When should the facility booking start?',
                'required' => true
            )
        );

        $facilityBookingEnd = new \Tillikum_Form_Element_Date(
            'facility_booking_end',
            array(
                'label' => 'When should the facility booking end?',
                'required' => true
            )
        );

        $facilityRateStart = new \Tillikum_Form_Element_Date(
            'facility_rate_start',
            array(
                'label' => 'When should the facility rate start?',
                'required' => true
            )
        );

        $facilityRateEnd = new \Tillikum_Form_Element_Date(
            'facility_rate_end',
            array(
                'label' => 'When should the facility rate end?',
                'required' => true
            )
        );

        $mealplanBookingStart = new \Tillikum_Form_Element_Date(
            'mealplan_booking_start',
            array(
                'label' => 'When should the meal plan booking start?',
                'required' => true
            )
        );

        $mealplanBookingEnd = new \Tillikum_Form_Element_Date(
            'mealplan_booking_end',
            array(
                'label' => 'When should the meal plan booking end?',
                'required' => true
            )
        );

        $mealplanRateStart = new \Tillikum_Form_Element_Date(
            'mealplan_rate_start',
            array(
                'label' => 'When should the meal plan rate start?',
                'required' => true
            )
        );

        $mealplanRateEnd = new \Tillikum_Form_Element_Date(
            'mealplan_rate_end',
            array(
                'label' => 'When should the meal plan rate end?',
                'required' => true
            )
        );

        $buildingSelect = new \Tillikum\Form\Element\Building('buildings');

        $buildingsMustMatch = new \Zend_Form_Element_Multiselect(
            'buildings_must_match',
            array(
                'label' => 'For which buildings must user preferences exist'
                         . ' in order to be assigned to by auto-allocation?',
                'multiOptions' => $buildingSelect->getMultiOptions(),
                'size' => count($buildingSelect->getMultiOptions())
            )
        );

        $buildingsToExclude = new \Zend_Form_Element_Multiselect(
            'buildings_to_exclude',
            array(
                'label' => 'Which buildings do you want to exclude?',
                'multiOptions' => $buildingSelect->getMultiOptions(),
                'size' => count($buildingSelect->getMultiOptions())
            )
        );

        $roomtypesToExclude = new \Zend_Form_Element_Multiselect(
            'roomtypes_to_exclude',
            array(
                'label' => 'Which room types do you want to exclude?',
                'multiOptions' => array(
                    'APT' => 'APT',
                    'COOP' => 'COOP',
                    'TEMP' => 'TEMP'
                )
            )
        );

        $roomflagsToExclude = new \Zend_Form_Element_Multiselect(
            'roomflags_to_exclude',
            array(
                'label' => 'Which room flags do you want to exclude?',
                'multiOptions' => array(
                    'ra' => 'RA'
                )
            )
        );

        $persontagsToExclude = new \Zend_Form_Element_Multiselect(
            'persontags_to_exclude',
            array(
                'label' => 'Which person tags do you want to exclude?',
                'multiOptions' => array(
                    'athlete' => 'Athlete',
                    'ra' => 'RA',
                    'ra20112012' => 'RA 2011-2012'
                )
            )
        );

        $submit = new \Tillikum_Form_Element_Submit(
            '_submit',
            array(
                'label' => 'Submit'
            )
        );

        $this->addElements(
            array(
                $isDebug,
                $minMatchMetric,
                $facilityBookingStart,
                $facilityBookingEnd,
                $facilityRateStart,
                $facilityRateEnd,
                $mealplanBookingStart,
                $mealplanBookingEnd,
                $mealplanRateStart,
                $mealplanRateEnd,
                $buildingsMustMatch,
                $buildingsToExclude,
                $roomtypesToExclude,
                $roomflagsToExclude,
                $persontagsToExclude,
                $submit
            )
        );
    }
}
