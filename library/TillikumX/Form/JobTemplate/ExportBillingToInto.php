<?php

namespace TillikumX\Form\JobTemplate;

class ExportBillingToInto extends \Tillikum_Form
{
    public function init()
    {
        $is_test = new \Zend_Form_Element_Checkbox(
            'is_test',
            array(
                'label' => 'Should this be a test run?'
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
                $submit
            )
        );
    }
}
