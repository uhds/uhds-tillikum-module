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

class ContractAudit extends ReportForm implements EntityManagerAwareInterface
{
    private $em;

    public function init()
    {
        parent::init();

        $date = new \Tillikum_Form_Element_Date(
            'date',
            [
                'label' => 'Date on which listed bookings are active',
                'required' => true,
            ]
        );

        $contract = new \Zend_Form_Element_Select(
            'contract',
            array(
                'label' => 'Which contract would you like to use in this report?',
                'multiOptions' => [],
                'required' => true,
            )
        );

        $this->addElements([
            $date,
            $contract,
        ]);
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
