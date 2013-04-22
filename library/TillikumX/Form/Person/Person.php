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
    protected $onidGateway;

    public function __construct($options = null)
    {
        parent::__construct($options);

        $this->onidGateway = new \Uhds\Model\Ldap\OnidGateway();
    }

    public function bind($person)
    {
        parent::bind($person);

        $this->pidm->setValue($person->pidm);
        $this->osuid->setValue($person->osuid);
        $this->onid->setValue($person->onid);
        $this->usg_id->setValue($person->usg_id);
        $this->passport_id->setValue($person->passport_id);
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
        $this->person->usg_id = $this->usg_id->getValue();
        $this->person->passport_id = $this->passport_id->getValue();
        $this->person->birthdate = $this->birthdate->getValue() ? new DateTime($this->birthdate->getValue()) : null;

        return $this;
    }

    public function init()
    {
        parent::init();

        $pidm = new \Zend_Form_Element_Text(
            'pidm',
            array(
                'description' => 'If you enter a value here, it will be' .
                                 ' checked against the ONID database and you' .
                                 ' will not need to add any other fields.',
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
                'description' => 'If you enter a value here, it will be' .
                                 ' checked against the ONID database and you' .
                                 ' will not need to add any other fields.',
                'order' => 2,
                'required' => false
            )
        );

        $onid = new \Uhds_Form_Element_Onid(
            'onid',
            array(
                'description' => 'If you enter a value here, it will be' .
                                 ' checked against the ONID database and you' .
                                 ' will not need to add any other fields.',
                'order' => 3,
                'required' => false
            )
        );

        $usgId = new \Zend_Form_Element_Text(
            'usg_id',
            array(
                'filters' => array(
                    'StringTrim',
                ),
                'label' => 'US Government-issued ID',
                'order' => 4,
                'required' => false,
            )
        );

        $passportId = new \Zend_Form_Element_Text(
            'passport_id',
            array(
                'filters' => array(
                    'StringTrim',
                ),
                'label' => 'Passport ID',
                'order' => 5,
                'required' => false,
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
                $usgId,
                $passportId,
                $birthdate
            )
        );
    }

    public function isValid($data)
    {
        if (!parent::isValid($data)) {
            return false;
        }

        if ($data['pidm']) {
            $onidEntry = $this->onidGateway->fetchByPidm($data['pidm']);
        } elseif ($data['osuid']) {
            $onidEntry = $this->onidGateway->fetchByOsuid($data['osuid']);
        } elseif ($data['onid']) {
            $onidEntry = $this->onidGateway->fetchByUsername($data['onid']);
        } else {
            $data['pidm'] = 'ignore-this-value-' . uniqid();
        }

        if ($onidEntry) {
            $data['family_name'] = $onidEntry->lastname;
            $data['given_name'] = $onidEntry->firstname;
            $data['pidm'] = $onidEntry->pidm;
            $data['osuid'] = $onidEntry->osuid;
            $data['onid'] = $onidEntry->username;
        }

        return parent::isValid($data);
    }
}
