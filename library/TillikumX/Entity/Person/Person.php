<?php
/**
 * The Tillikum Project (http://tillikum.org/)
 *
 * @link       http://tillikum.org/websvn/
 * @copyright  Copyright 2009-2012 Oregon State University (http://oregonstate.edu/)
 * @license    http://www.gnu.org/licenses/gpl-2.0-standalone.html GPLv2
 */

namespace TillikumX\Entity\Person;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Tillikum\Entity\Person\Person as TillikumPerson;
use Vo\DateRange;

/**
 * @ORM\Entity(repositoryClass="TillikumX\Repository\Person\Person")
 * @ORM\Table(name="tillikumx_person", indexes={
 *     @ORM\Index(name="idx_nickname", columns={"nickname"}),
 *     @ORM\Index(name="idx_osuid", columns={"osuid"}),
 *     @ORM\Index(name="idx_onid", columns={"onid"})
 * })
 */
class Person extends TillikumPerson
{
    /**
     * @ORM\Column(nullable=true, unique=true)
     */
    protected $pidm;

    /**
     * @ORM\Column(nullable=true)
     */
    protected $osuid;

    /**
     * @ORM\Column(nullable=true)
     */
    protected $onid;

    /**
     * @ORM\Column(nullable=true)
     */
    protected $nickname;

    /**
     * @ORM\Column(nullable=true, type="text")
     */
    protected $medical;

    /**
     * @ORM\Column(nullable=true)
     */
    protected $ethnicity_code;

    /**
     * @ORM\Column(nullable=true)
     */
    protected $residency_code;

    /**
     * @ORM\Column(nullable=true)
     */
    protected $origin_country;

    /**
     * @ORM\Column(nullable=true)
     */
    protected $student_type_code;

    /**
     * @ORM\Column(nullable=true)
     */
    protected $class_standing;

    /**
     * @ORM\Column(nullable=true)
     */
    protected $level_code;

    /**
     * @ORM\Column(nullable=true)
     */
    protected $admit_term;

    /**
     * @ORM\Column(nullable=true)
     */
    protected $primary_degree;

    /**
     * @ORM\Column(nullable=true)
     */
    protected $primary_major_1;

    /**
     * @ORM\Column(nullable=true)
     */
    protected $primary_major_2;

    /**
     * @ORM\Column(nullable=true)
     */
    protected $primary_minor_1;

    /**
     * @ORM\Column(nullable=true)
     */
    protected $hours_registered;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $can_sms = false;

    /**
     * INTO Salesforce information cache
     *
     * @var array
     */
    protected static $intoCache = array();

    /**
     * Determines if the person is an INTO student on the given date
     *
     * @return bool
     */
    public function isInto(DateTime $date = null)
    {
        $into = self::fetchInto($this->osuid);

        if (null === $into) {
            return false;
        }

        if (empty($into['reservations'])) {
            return false;
        }

        $ends = array();
        foreach ($into['reservations'] as $reservation) {
            $ends[] = $reservation['end'];
        }

        $range = DateRange::upTo(new DateTime(max($ends)));

        return $range->includes($date ? $date : new DateTime(date('Y-m-d')));
    }

    /**
     * Returns the person's campus address
     *
     * The return value will be a reference to the campus address object
     * in the person document.
     *
     * null will be returned if no campus address exists.
     */
    public function getCampusAddress()
    {
        $campusAddress = $this->addresses->filter(
            function ($address) {
                return $address->type->id === 'campus';
            }
        )
            ->first();

        if ($campusAddress) {
            return $campusAddress;
        }

        return null;
    }

    /**
     * Returns the person's directory address
     *
     * The return value will be a reference to the directory address object
     * in the person document.
     *
     * null will be returned if no directory address exists.
     */
    public function getDirectoryAddress()
    {
        $directoryAddress = $this->addresses->filter(
            function ($address) {
                return $address->type->id === 'directory';
            }
        )
            ->first();

        if ($directoryAddress) {
            return $directoryAddress;
        }

        return null;
    }

    /**
     * Returns the person's directory email address
     *
     * The empty string will be returned if no directory email address exists.
     *
     * @return string
     */
    public function getDirectoryEmail()
    {
        $directoryEmail = $this->emails->filter(
            function ($email) {
                return $email->type->id === 'directory';
            }
        )
            ->first();

        if ($directoryEmail) {
            return $directoryEmail->value;
        }

        return '';
    }

    /**
     * Returns the person's directory phone number
     *
     * The empty string will be returned if no directory phone number exists.
     *
     * @return string
     */
    public function getDirectoryPhone()
    {
        $directoryPhone = $this->phone_numbers->filter(
            function ($phone) {
                return $phone->type->id === 'directory';
            }
        )
            ->first();

        if ($directoryPhone) {
            return $directoryPhone->value;
        }

        return '';
    }

    /**
     * Fetch the user’s display name, if it exists
     *
     * If the display name does not exist, it will format the user’s name as
     * "family_name, given_name “nickname” middle_name".
     *
     * @return string
     */
    public function getDisplayName()
    {
        if ($this->display_name !== null) {
            return $this->display_name;
        }

        return sprintf(
            '%s%s%s%s',
            $this->family_name ?: '',
            $this->given_name ? ', ' . $this->given_name : '',
            $this->nickname ? ' “' . $this->nickname . '”' : '',
            $this->middle_name ? ' ' . $this->middle_name : ''
        );
    }

    public function getIntoData()
    {
        return self::fetchInto($this->osuid);
    }

    /**
     * Returns the person's INTO housing start date
     *
     * Returns null if no start date exists.
     *
     * @return DateTime
     */
    public function getIntoHousingStart()
    {
        $into = self::fetchInto($this->osuid);

        if (null === $into) {
            return null;
        }

        if (empty($into['reservations'])) {
            return null;
        }

        $starts = array();
        foreach ($into['reservations'] as $reservation) {
            $starts[] = $reservation['start'];
        }

        return new DateTime(min($starts));
    }

    /**
     * Returns the person's INTO housing end date
     *
     * Returns null if no end date exists.
     *
     * @return DateTime
     */
    public function getIntoHousingEnd()
    {
        $into = self::fetchInto($this->osuid);

        if (null === $into) {
            return null;
        }

        if (empty($into['reservations'])) {
            return null;
        }

        $ends = array();
        foreach ($into['reservations'] as $reservation) {
            $ends[] = $reservation['end'];
        }

        return new DateTime(max($ends));
    }

    /**
     * Returns the person's latest INTO housing code
     *
     * Returns the empty string if no code exists.
     *
     * @return string
     */
    public function getIntoHousingCode()
    {
        $into = self::fetchInto($this->osuid);

        if (null === $into) {
            return '';
        }

        if (empty($into['reservations'])) {
            return '';
        }

        $dateToCode = array();
        foreach ($into['reservations'] as $reservation) {
            $dateToCode[$reservation['start']] = $reservation['housing_code'];
        }

        return $dateToCode[max(array_keys($dateToCode))];
    }

    /**
     * Returns the person's ONID email address
     *
     * This address is automatically generated based on the user's ONID
     * username and concatenating that with the current ONID domain suffix.
     *
     * @return string
     */
    public function getOnidEmail()
    {
        if (empty($this->onid)) {
           return '';
        }

        return \Uhds_Util::constructOnidEmailAddress($this->onid);
    }

    /**
     * Returns the person’s user-input phone number
     *
     * @return string
     */
    public function getUserPhoneNumber()
    {
        $userPhone = $this->phone_numbers->filter(
            function ($phone) {
                return $phone->type->id === 'user';
            }
        )
            ->first();

        if ($userPhone) {
            return $userPhone->value;
        }

        return '';
    }

    /**
     * Returns whether a student is INTO academic english
     *
     * @return bool
     */
    public function isIntoAcademicEnglish()
    {
        $into = self::fetchInto($this->osuid);

        if (null === $into) {
            return false;
        }

        return $into['person']['current_program'] === 'AE' ? true : false;
    }

    /**
     * Returns whether a student is INTO general english
     *
     * @return bool
     */
    public function isIntoGeneralEnglish()
    {
        $into = self::fetchInto($this->osuid);

        if (null === $into) {
            return false;
        }

        return $into['person']['current_program'] === 'GE' ? true : false;
    }

    /**
     * Returns whether a student is INTO pathways
     *
     * @return bool
     */
    public function isIntoPathways()
    {
        $into = self::fetchInto($this->osuid);

        if (null === $into) {
            return false;
        }

        return $into['person']['current_program'] === 'UGP' ? true : false;
    }

    /**
     * Returns whether a student is an INTO pathways graduate
     *
     * @return bool
     */
    public function isIntoPathwaysGraduate()
    {
        $into = self::fetchInto($this->osuid);

        if (null === $into) {
            return false;
        }

        return $into['person']['current_program'] === 'GP' ? true : false;
    }

    protected static function fetchInto($osuid)
    {
        if (null === $osuid) {
            return null;
        }

        if (!array_key_exists($osuid, self::$intoCache)) {
            $db = \Uhds_Db::factory('common');

            self::$intoCache[$osuid] = null;

            $row = $db->fetchRow($db->select()
                ->from('into_salesforce_person')
                ->where('osuid = ?', $osuid)
            );

            if (false !== $row) {
                $reservationRows = $db->fetchAll($db->select()
                    ->from('into_salesforce_person_reservation')
                    ->where('person_osuid = ?', $osuid)
                );

                self::$intoCache[$osuid] = array(
                    'person' => $row,
                    'reservations' => $reservationRows
                );
            }
        }

        return self::$intoCache[$osuid];
    }
}
