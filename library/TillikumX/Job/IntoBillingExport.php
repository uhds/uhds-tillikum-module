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

class IntoBillingExport extends AbstractJob
{
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getDescription()
    {
        return 'Export charges to INTO-OSU.';
    }

    public function getFormClass()
    {
        return 'TillikumX\Form\Job\IntoBillingExport';
    }

    public function getName()
    {
        return 'INTO billing export';
    }

    public function run()
    {
        $job = $this->getJobEntity();
        $parameters = $this->getParameters();

        $csvResource = fopen('php://temp/maxmemory:' . 16 * 1024 * 1024, 'r+');

        $translateEventType = function ($event) {
            if ($event instanceof Entity\Billing\Event\AdHoc) {
                return 'Ad hoc charge';
            } elseif ($event instanceof Entity\Billing\Event\FacilityBooking) {
                return 'Facility booking';
            } elseif ($event instanceof Entity\Billing\Event\MealplanBooking) {
                return 'Meal plan booking';
            } else {
                return 'Unknown';
            }
        };

        $translateEventStart = function ($event) {
            if ($event instanceof Entity\Billing\Event\FacilityBooking) {
                return $event->start->format('Y-m-d');
            } elseif ($event instanceof Entity\Billing\Event\MealplanBooking) {
                return $event->start->format('Y-m-d');
            } else {
                return '';
            }
        };

        $translateEventEnd = function ($event) {
            if ($event instanceof Entity\Billing\Event\FacilityBooking) {
                return $event->end->format('Y-m-d');
            } elseif ($event instanceof Entity\Billing\Event\MealplanBooking) {
                return $event->end->format('Y-m-d');
            } else {
                return '';
            }
        };

        fputcsv(
            $csvResource,
            array(
                'OSU ID',
                'Family name',
                'Given name',
                'Detail code',
                'Event type',
                'Event start date',
                'Event end date',
                'Event creation date (Pacific time)',
                'Entry currency',
                'Entry amount',
                'Entry description',
                'Entry creation date (Pacific time)',
            )
        );

        $result = $this->em->createQuery(
            "
            SELECT e
            FROM Tillikum\Entity\Billing\Entry\Entry e
            LEFT JOIN Tillikum\Entity\Billing\Entry\Post post WITH e = post.entry
            WHERE post IS NULL AND
                  e.code LIKE :intoCode
            "
        )
            ->setParameter('intoCode', 'NTO%')
            ->getResult();

        $output = '';
        $outputRowCount = 0;
        foreach ($result as $entry) {
            $event = $entry->events->first();
            $person = $entry->invoice->person;

            fputcsv(
                $csvResource,
                array(
                    $person->osuid,
                    $person->family_name,
                    $person->given_name,
                    $entry->code,
                    $event ? $translateEventType($event) : '',
                    $event ? $translateEventStart($event) : '',
                    $event ? $translateEventEnd($event) : '',
                    $event ? date('Y-m-d H:i:s', $event->created_at->format('U')) : '',
                    $entry->currency,
                    $entry->amount,
                    $entry->description,
                    date('Y-m-d H:i:s', $entry->created_at->format('U')),
                )
            );

            if (!$job->is_dry_run) {
                $postEntry = new Entity\Billing\Entry\Post();
                $postEntry->entry = $entry;
                $postEntry->description = 'Exported to INTO';
                $postEntry->created_by = $job->created_by;

                $this->em->persist($postEntry);
            }

            $outputRowCount += 1;
        }

        rewind($csvResource);
        $csv = stream_get_contents($csvResource);
        fclose($csvResource);

        $attachment = new Entity\Job\Attachment\Attachment();
        $attachment->job = $job;
        $attachment->name = 'INTO billing export ' . date('Y-m-d') . '.csv';
        $attachment->media_type = 'text/csv';
        $attachment->attachment = $csv;
        $this->em->persist($attachment);

        $message = new Entity\Job\Message\Message();
        $message->job = $job;
        $message->level = LOG_INFO;
        $message->message = "Exported {$outputRowCount} billing entries.";
        $this->em->persist($message);

        $job->run_state = Entity\Job\Job::RUN_STATE_STOPPED;
        $job->job_state = Entity\Job\Job::JOB_STATE_SUCCESS;

        $this->em->flush();
    }
}
