<?php
/**
 * The Tillikum Project (http://tillikum.org/)
 *
 * @link       http://tillikum.org/websvn/
 * @copyright  Copyright 2009-2012 Oregon State University (http://oregonstate.edu/)
 * @license    http://www.gnu.org/licenses/gpl-2.0-standalone.html GPLv2
 */

namespace TillikumX\Form\Person;

use DateTime;
use Tillikum\Form\Person\Person as PersonForm;

class Person extends PersonForm
{
    public function bind($person)
    {
        parent::bind($person);

        $this->pidm->setValue($person->pidm);
        $this->osuid->setValue($person->osuid);
        $this->onid->setValue($person->onid);
        $this->birthdate->setValue($person->birthdate ? $person->birthdate->format('Y-m-d') : '');

        return $this;
    }

    public function bindValues()
    {
        parent::bindValues();

        if (!$this->person) {
            return;
        }

        $this->person->pidm = $this->pidm->getValue();
        $this->person->osuid = $this->osuid->getValue();
        $this->person->onid = $this->onid->getValue();
        $this->person->birthdate = $this->birthdate->getValue() ? new DateTime($this->birthdate->getValue()) : null;

        return $this;
    }

    public function init()
    {
        parent::init();

        $pidm = new \Zend_Form_Element_Text(
            'pidm',
            array(
                'description' => 'Use caution when changing this value.',
                'filters' => array(
                    'StringTrim',
                ),
                'label' => 'PIDM',
                'order' => 1,
                'required' => false
            )
        );

        $osuid = new \Uhds_Form_Element_Osuid(
            'osuid',
            array(
                'description' => 'Use caution when changing this value.',
                'order' => 2,
                'required' => false
            )
        );

        $onid = new \Uhds_Form_Element_Onid(
            'onid',
            array(
                'description' => 'Use caution when changing this value.',
                'order' => 3,
                'required' => false
            )
        );

        $birthdate = new \Tillikum_Form_Element_Date(
            'birthdate',
            array(
                'label' => 'Birth date',
                'order' => 9
            )
        );

        $this->addElements(
            array(
                $pidm,
                $osuid,
                $onid,
                $birthdate
            )
        );
    }
}
