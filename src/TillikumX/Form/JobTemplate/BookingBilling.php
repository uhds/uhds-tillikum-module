<?php

namespace TillikumX\Form\JobTemplate;

class BookingBilling extends \Tillikum_Form
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
                'label' => 'Perform term billing?'
            )
        );

        $is_into_billing = new \Zend_Form_Element_Checkbox(
            'is_into_billing',
            array(
                'label' => 'Perform INTO billing?'
            )
        );

        $is_fh_billing = new \Zend_Form_Element_Checkbox(
            'is_fh_billing',
            array(
                'label' => 'Perform family housing billing?'
            )
        );

        $end = new \Tillikum_Form_Element_Date(
            'end',
            array(
                'label' => 'What date should be assessed through?',
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
                $end,
                $submit
            )
        );
    }
}
