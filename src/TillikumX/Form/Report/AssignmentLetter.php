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

class AssignmentLetter extends ReportForm implements EntityManagerAwareInterface
{
    protected $em;

    public function init()
    {
        parent::init();

        $startDate = new \Tillikum_Form_Element_Date(
            'start_date',
            array(
                'label' => 'Which date would a booking have to start on or after?',
                'required' => true
            )
        );

        $templateGateway = new \Uhds\Model\HousingApplication\TemplateGateway();
        $templates = $templateGateway->fetchManyByEnd(new \DateTime('-1 year'), '>=');

        $housingApplicationOptions = array();
        foreach ($templates as $template) {
            $housingApplicationOptions[$template->id] = $template->name;
        }

        natsort($housingApplicationOptions);

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

        $contract = new \Zend_Form_Element_Select(
            'contract',
            array(
                'label' => 'Which contract would you like to use in this report?',
                'multiOptions' => array(),
                'required' => true,
            )
        );

        $this->addElements(
            array(
                $startDate,
                $applications,
                $contract,
            )
        );
    }

    public function setEntityManager(EntityManager $em)
    {
        $this->em = $em;

        $contracts = $this->em->createQuery(
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
