<?php
/**
 * The Tillikum Project (http://tillikum.org/)
 *
 * @link       http://tillikum.org/websvn/
 * @copyright  Copyright 2009-2012 Oregon State University (http://oregonstate.edu/)
 * @license    http://www.gnu.org/licenses/gpl-2.0-standalone.html GPLv2
 */

namespace TillikumX\Report;

use DateTime;
use Doctrine\ORM\EntityManager;
use Tillikum\Report\AbstractReport;

class ApplicantCounts extends AbstractReport
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getDescription()
    {
        return 'Designed to aid in weekly statistics.';
    }

    public function getFormClass()
    {
        return 'TillikumX\Form\Report\ApplicantCounts';
    }

    public function getName()
    {
        return 'Applicant counts';
    }

    public function generate()
    {
        $conn = $this->em->getConnection();
        $parameters = $this->getParameters();

        $newApplication = $parameters['new_application'];
        $returnerApplication = $parameters['returner_application'];
        $date = new DateTime($parameters['date']);
        $openingDate = new DateTime($parameters['opening_date']);

        $applicationPersonId = 'SUBSTRING_INDEX(app.id, \'-\', 1)';
        $applicationTemplateId = 'SUBSTRING_INDEX(app.id, \'-\', -(1))'; 

        $numberCompletedByDate = $conn->fetchColumn(
            "
            SELECT COUNT(*) FROM tillikum_housing_application_application app
            WHERE state IN (?, ?)
            AND completed_at <= ?
            AND $applicationTemplateId = ?
            ",
            array(
                'completed',
                'processed',
                gmdate('Y-m-d H:i:s', $date->format('U')),
                $newApplication,
            ),
            0
        );

        $numberCancelledByDate = $conn->fetchColumn(
            "
            SELECT COUNT(*) FROM tillikum_housing_application_application app
            WHERE state = ?
            AND cancelled_at <= ?
            AND $applicationTemplateId = ?
            ",
            array(
                'cancelled',
                gmdate('Y-m-d H:i:s', $date->format('U')),
                $newApplication,
            ),
            0
        );

        // @todo Convert to DQL once the housing application has been migrated
        // to Doctrine
        $returnersAssigned = $conn->fetchColumn(
            "
            SELECT COUNT(*) FROM tillikum_housing_application_application app
            JOIN tillikum_person person ON person.id = $applicationPersonId
            JOIN tillikum_booking_facility booking ON $applicationPersonId = booking.person_id
            LEFT JOIN tillikum_person__tag ra_tag ON $applicationPersonId = ra_tag.person_id AND ra_tag.tag_id = 'ra'
            LEFT JOIN tillikum_person__tag sra_tag ON $applicationPersonId = sra_tag.person_id AND sra_tag.tag_id = 'sra'
            WHERE $applicationTemplateId = ?
            AND app.state = ?
            AND booking.start <= ?
            AND booking.end >= ?
            AND ra_tag.person_id IS NULL
            AND sra_tag.person_id IS NULL
            ",
            array(
                $returnerApplication,
                'processed',
                $openingDate->format('Y-m-d'),
                $openingDate->format('Y-m-d'),
            ),
            0
        );

        $ret = array(
            array(
                'Number of completed new applications as of ' . $date->format('n/j/Y'),
                'Number of cancelled new applications as of ' . $date->format('n/j/Y'),
                'Number of assigned returners (non-RA)'
            )
        );

        $ret[] = array(
            $numberCompletedByDate,
            $numberCancelledByDate,
            $returnersAssigned
        );

        return $ret;
    }
}
