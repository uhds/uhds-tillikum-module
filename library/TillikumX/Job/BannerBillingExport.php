<?php
/**
 * The Tillikum Project (http://tillikum.org/)
 *
 * @link       http://tillikum.org/websvn/
 * @copyright  Copyright 2009-2012 Oregon State University (http://oregonstate.edu/)
 * @license    http://www.gnu.org/licenses/gpl-2.0-standalone.html GPLv2
 */

namespace TillikumX\Job;

use Doctrine\ORM\EntityManager;
use Tillikum\Entity;
use Tillikum\Job\AbstractJob;

class BannerBillingExport extends AbstractJob
{
    /**
     * Format string for each row of the export output
     *
     * The format string can be described as follows:
     *
     * - 9 character OSU ID, truncated to first 9 characters (sad day if this has to be done though)
     * - 25 character firstname lastname, left-justified, truncated to first 25 characters
     * - 8 character date with format 'mdY', truncated to first 8 characters
     * - 1 character for amount sign, '-' if negative and ' ' if positive
     * - 8 characters for amount as a floating point, 5 digits (left-padded with zeroes), decimal point, then 2 digits
     * - 30 character description, left-justified, truncated to first 30 characters
     * - 4 character rate code, truncated to 4 characters
     * - 1 literal 'x'
     * - 1 literal ' '
     * - 1 newline (\n)
     *
     * @var string
     */
    protected $chargeFormat = "%9.9s%-25.25s%8.8s%s%08.2f%-30.30s%4.4sx \n";

    /**
     * File name for the output
     *
     * @var string
     */
    protected $exportFilename;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;

        $this->exportFilename = 'OregonExFin' . date('YmdHis') . '00.txt';
    }

    public function getDescription()
    {
        return 'Generate a file for export to the Banner system, and mark relevant entries as posted.';
    }

    public function getFormClass()
    {
        return 'TillikumX\Form\Job\BannerBillingExport';
    }

    public function getName()
    {
        return 'Banner billing export';
    }

    public function run()
    {
        $job = $this->getJobEntity();
        $parameters = $this->getParameters();

        $result = $this->em->createQuery(
            "
            SELECT e.code, SUM(e.amount) as totalAmount,
                   r.description,
                   p.osuid, p.given_name, p.family_name
            FROM Tillikum\Entity\Billing\Entry\Entry e
            JOIN e.invoice i
            JOIN TillikumX\Entity\Person\Person p WITH i.person = p
            LEFT JOIN Tillikum\Entity\Billing\Entry\Post post WITH e = post.entry
            LEFT JOIN Tillikum\Entity\Billing\Rule\Config\Config rc
                WITH e.code = rc.code AND rc IN (
                    SELECT subrc
                    FROM Tillikum\Entity\Billing\Rule\Config\Config subrc
                    WHERE subrc.code = rc.code
                    GROUP BY subrc.code
                )
            LEFT JOIN rc.rule r
            WHERE post IS NULL AND
                  e.currency = :currency AND
                  e.code NOT LIKE :intoCode
            GROUP BY i, e.code
            ORDER BY p.osuid
            "
        )
            ->setParameter('currency', 'USD')
            ->setParameter('intoCode', 'NTO%')
            ->getResult();

        $output = '';
        $outputRowCount = 0;
        foreach ($result as $row) {
            if ($row['totalAmount'] != 0) {
                $output .= sprintf(
                    $this->chargeFormat,
                    $row['osuid'],
                    $row['given_name'] . ' ' . $row['family_name'],
                    date('mdY'),
                    $row['totalAmount'] < 0 ? '-' : ' ',
                    abs($row['totalAmount']),
                    $row['description'],
                    $row['code']
                );

                $outputRowCount += 1;
            }

        }

        $entries = $this->em->createQuery(
            "
            SELECT e
            FROM Tillikum\Entity\Billing\Entry\Entry e
            LEFT JOIN Tillikum\Entity\Billing\Entry\Post post WITH e = post.entry
            WHERE post IS NULL AND
                  e.currency = :currency AND
                  e.code NOT LIKE :intoCode
            "
        )
            ->setParameter('currency', 'USD')
            ->setParameter('intoCode', 'NTO%')
            ->getResult();

        foreach ($entries as $entry) {
            if (!$job->is_dry_run) {
                $postEntry = new Entity\Billing\Entry\Post();
                $postEntry->entry = $entry;
                $postEntry->description = 'Exported to banner';
                $postEntry->created_by = $job->created_by;

                $this->em->persist($postEntry);
            }
        }

        $attachment = new Entity\Job\Attachment\Attachment();
        $attachment->job = $job;
        $attachment->name = $this->exportFilename;
        $attachment->media_type = 'text/plain';
        $attachment->attachment = $output;

        $this->em->persist($attachment);

        $message = new Entity\Job\Message\Message();
        $message->job = $job;
        $message->level = LOG_INFO;
        $message->message = "Generated {$outputRowCount} rows without errors.";

        $this->em->persist($message);

        $job->run_state = Entity\Job\Job::RUN_STATE_STOPPED;
        $job->job_state = Entity\Job\Job::JOB_STATE_SUCCESS;

        $this->em->flush();
    }
}
