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

class Unassigned extends ReportForm implements EntityManagerAwareInterface
{
    private $uhdsEm;
    private $tillikumEm;

    public function __construct(EntityManager $uhdsEm)
    {
        $this->uhdsEm = $uhdsEm;

        parent::__construct();
    }

    public function init()
    {
        parent::init();

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
                    'size' => count($housingApplicationOptions),
                ),
                'label' => 'Which applications would you like to use?',
                'multiOptions' => $housingApplicationOptions,
                'required' => true,
            )
        );

        $contract = new \Zend_Form_Element_Select(
            'contract',
            array(
                'label' => 'Which contract would you like to use in this report?',
                'multiOptions' => [],
                'required' => true,
            )
        );

        $date = new \Tillikum_Form_Element_Date(
            'date',
            array(
                'label' => 'Which date should be checked for lack of assignment?',
                'required' => true,
            )
        );

        $this->addElements(
            array(
                $applications,
                $contract,
                $date,
            )
        );
    }

    public function setEntityManager(EntityManager $em)
    {
        $this->tillikumEm = $em;

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

        $this->contract->setMultiOptions($contractOptions);
        $this->contract->setAttrib('size', count($contractOptions));

        return $this;
    }
}
