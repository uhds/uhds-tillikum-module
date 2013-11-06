<?php
/**
 * The Tillikum Project (http://tillikum.org/)
 *
 * @link       http://tillikum.org/websvn/
 * @copyright  Copyright 2009-2012 Oregon State University (http://oregonstate.edu/)
 * @license    http://www.gnu.org/licenses/gpl-2.0-standalone.html GPLv2
 */

namespace TillikumX\Form\Report;

use DateTime;
use Doctrine\ORM\EntityManager;
use Tillikum\Form\Report\Report as ReportForm;
use Tillikum\ORM\EntityManagerAwareInterface;

class LivingLearning extends ReportForm implements EntityManagerAwareInterface
{
    private $tillikumEm;
    private $uhdsEm;

    public function __construct(EntityManager $uhdsEm)
    {
        $this->uhdsEm = $uhdsEm;

        parent::__construct();
    }

    public function init()
    {
        parent::init();

        $date = new \Tillikum_Form_Element_Date(
            'date',
            array(
                'label' => 'What is the date that bookings and mealplans intersect?',
                'required' => true,
            )
        );

        $templates = $this->uhdsEm->createQuery(
            '
            SELECT t.id, t.name
            FROM Uhds\Entity\HousingApplication\Template\Template t
            WHERE t.end >= :date
            ORDER BY t.name
            '
        )
            ->setParameter('date', new DateTime('-1 year'))
            ->getResult();

        $housingApplicationOptions = [];
        foreach ($templates as $template) {
            $housingApplicationOptions[$template['id']] = $template['name'];
        }

        $applications = new \Zend_Form_Element_Multiselect(
            'applications',
            array(
                'attribs' => array(
                    'size' => count($housingApplicationOptions)
                ),
                'label' => 'Which housing applications would you like to use in this report?',
                'multiOptions' => $housingApplicationOptions,
                'required' => true,
            )
        );

        $contracts = new \Zend_Form_Element_Multiselect(
            'contracts',
            array(
                'label' => 'Which contracts would you like to use in this report?',
                'multiOptions' => array(),
                'required' => true,
            )
        );

        $this->addElements(
            array(
                $date,
                $applications,
                $contracts,
            )
        );
    }

    public function setEntityManager(EntityManager $tillikumEm)
    {
        $this->tillikumEm = $tillikumEm;

        $contracts = $this->tillikumEm->createQuery(
            "
            SELECT c.id, c.name
            FROM Tillikum\Entity\Contract\Contract c
            WHERE c.end >= :date
            ORDER BY c.name
            "
        )
            ->setParameter('date', new DateTime('-1 year'))
            ->getResult();

        $contractOptions = array();
        foreach ($contracts as $contract) {
            $contractOptions[$contract['id']] = $contract['name'];
        }

        $this->contracts->setMultiOptions($contractOptions);
        $this->contracts->setAttrib('size', count($contractOptions));

        return $this;
    }
}
