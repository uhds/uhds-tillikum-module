<?php

namespace TillikumX\Form\JobTemplate;

class PropagateBookingRate extends \Tillikum_Form
{
    public function init()
    {
        $is_test = new \Zend_Form_Element_Checkbox(
            'is_test',
            array(
                'label' => 'Should this be a test run?'
            )
        );

        $is_term_billing = new \Zend_Form_Element_Checkbox(
            'is_term_billing',
            array(
                'label' => 'Perform term propagation?'
            )
        );

        $is_into_billing = new \Zend_Form_Element_Checkbox(
            'is_into_billing',
            array(
                'label' => 'Perform INTO propagation?'
            )
        );

        $is_fh_billing = new \Zend_Form_Element_Checkbox(
            'is_fh_billing',
            array(
                'label' => 'Perform family housing propagation?'
            )
        );

        $oldBookingDate = new \Tillikum_Form_Element_Date(
            'old_booking_date',
            array(
                'description' => 'A date in the term you are propagating TO that will include all valid bookings for that term (recommended: first date of the next term).',
                'label' => 'What date do all eligible bookings pass through?',
                'required' => true
            )
        );

        $oldRateDate = new \Tillikum_Form_Element_Date(
            'old_rate_date',
            array(
                'description' => 'A date from the term you are propagating FROM that will always select the correct rate to propagate (recommended: last date of the previous term\'s rate).',
                'label' => 'What date do all eligible rates pass through?',
                'required' => true
            )
        );

        $newRateStartDate = new \Tillikum_Form_Element_Date(
            'new_rate_start',
            array(
                'label' => 'What is the start date of the new rate?',
                'required' => true
            )
        );

        $newRateEndDate = new \Tillikum_Form_Element_Date(
            'new_rate_end',
            array(
                'label' => 'What is the end date of the new rate?',
                'required' => true
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
                $is_test,
                $is_term_billing,
                $is_into_billing,
                $is_fh_billing,
                $oldBookingDate,
                $oldRateDate,
                $newRateStartDate,
                $newRateEndDate,
                $submit
            )
        );
    }
}
