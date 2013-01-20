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
use Tillikum\Form\Report\Report as ReportForm;

class NewAssignments extends ReportForm
{
    public function init()
    {
        parent::init();

        $earliestAppDate = new \Tillikum_Form_Element_Date(
            'earliest_app_date',
            array(
                'label' => 'Earliest application completion date (to be considered ‘new’)?',
                'required' => true
            )
        );

        $startDate = new \Tillikum_Form_Element_Date(
            'start_date',
            array(
                'label' => 'Earliest booking start date?',
                'required' => true
            )
        );

        $endDate = new \Tillikum_Form_Element_Date(
            'end_date',
            array(
                'label' => 'Latest booking start date?',
                'required' => true
            )
        );

        $templateGateway = new \Uhds\Model\HousingApplication\TemplateGateway();
        $templates = $templateGateway->fetchManyByEnd(new DateTime('-1 year'), '>=');

        $housingApplicationOptions = array();
        foreach ($templates as $template) {
            $housingApplicationOptions[$template->id] = $template->name;
        }

        natsort($housingApplicationOptions);

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
                $startDate,
                $endDate,
                $applications,
            )
        );
    }
}
