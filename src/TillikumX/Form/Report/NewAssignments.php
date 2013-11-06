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

class NewAssignments extends ReportForm
{
    private $uhdsEm;

    public function __construct(EntityManager $uhdsEm)
    {
        $this->uhdsEm = $uhdsEm;

        parent::__construct();
    }

    public function init()
    {
        parent::init();

        $earliestAppDate = new \Tillikum_Form_Element_Date(
            'earliest_booking_creation_date',
            array(
                'label' => 'Earliest booking creation date?',
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
                    'size' => count($housingApplicationOptions),
                ),
                'label' => 'Which applications would you like to use?',
                'multiOptions' => $housingApplicationOptions,
                'required' => true,
            )
        );

        $this->addElements(
            array(
                $earliestAppDate,
                $applications,
            )
        );
    }
}
