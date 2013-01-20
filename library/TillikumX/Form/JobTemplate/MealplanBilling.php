<?php

namespace TillikumX\Form\JobTemplate;

class MealplanBilling extends \Tillikum_Form
{
    public function init()
    {
        $is_test = new \Zend_Form_Element_Checkbox(
            'is_test',
            array(
                'label' => 'Should this be a test run?'
            )
        );

        $do_dining_export = new \Zend_Form_Element_Checkbox(
            'do_dining_export',
            array(
                'label' => 'Should this job create a file suitable for import by CS Gold?'
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
                $do_dining_export,
                $is_term_billing,
                $is_into_billing,
                $is_fh_billing,
                $end,
                $submit
            )
        );
    }
}
