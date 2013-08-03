<?php

namespace TillikumX\Form\JobTemplate;

class PropagateMealplan extends \Tillikum_Form
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

        $oldMealplanDate = new \Tillikum_Form_Element_Date(
            'old_mealplan_date',
            array(
                'description' => 'A date from the term you are propagating FROM that will always select the correct meal plan to propagate (recommended: last date of the previous term\'s plan).',
                'label' => 'What date do all eligible mealplans pass through?',
                'required' => true
            )
        );

        $currentBookingDate = new \Tillikum_Form_Element_Date(
            'current_booking_date',
            array(
                'description' => 'A facility booking date in the term you are propagating TO that will include people living with us for that term (recommended: first date of the next term).',
                'label' => 'What date should the resident be booked through in order to be considered ‘current?’',
                'required' => true
            )
        );

        $newMealplanStartDate = new \Tillikum_Form_Element_Date(
            'new_mealplan_start',
            array(
                'label' => 'What is the start date of the new mealplan?',
                'required' => true
            )
        );

        $newMealplanEndDate = new \Tillikum_Form_Element_Date(
            'new_mealplan_end',
            array(
                'label' => 'What is the end date of the new mealplan?',
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
                $oldMealplanDate,
                $currentBookingDate,
                $newMealplanStartDate,
                $newMealplanEndDate,
                $submit
            )
        );
    }
}
