<?php
/**
 * The Tillikum Project (http://tillikum.org/)
 *
 * @link       http://tillikum.org/websvn/
 * @copyright  Copyright 2009-2012 Oregon State University (http://oregonstate.edu/)
 * @license    http://www.gnu.org/licenses/gpl-2.0-standalone.html GPLv2
 */

namespace TillikumX\Form\Job;

use DateTime;
use Doctrine\ORM\EntityManager;
use Tillikum\Form\Job\Job as JobForm;

class MealplanBookingBilling extends JobForm
{
    public function init()
    {
        parent::init();

        $end = new \Tillikum_Form_Element_Date(
            'end',
            array(
                'description' => 'You should pick the latest possible date for this term (consider INTO) - remember that billing will not extend beyond the end of the rate.',
                'label' => 'What date do you want to bill through?',
                'required' => true,
            )
        );

        $raTagId = new \Zend_Form_Element_Select(
            'ra_tag_id',
            array(
                'label' => 'Which tag do you want to use to classify current RAs?',
                'multiOptions' => array(),
                'required' => true,
            )
        );

        $this->addElements(
            array(
                $end,
                $raTagId,
            )
        );
    }

    public function setEntityManager(EntityManager $em)
    {
        $tags = $em
            ->getRepository('Tillikum\Entity\Person\Tag')
            ->findBy(array('is_active' => true));

        $tagOptions = array();
        foreach ($tags as $tag) {
            $tagOptions[$tag->id] = $tag->name;
        }

        $this->ra_tag_id->setMultiOptions($tagOptions);
    }
}
