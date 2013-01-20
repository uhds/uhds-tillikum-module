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

class Unassigned extends ReportForm
{
    public function init()
    {
        parent::init();

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
                $date,
            )
        );
    }
}
