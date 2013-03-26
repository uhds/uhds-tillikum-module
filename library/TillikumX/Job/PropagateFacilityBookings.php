<?php
/**
 * The Tillikum Project (http://tillikum.org/)
 *
 * @link       http://tillikum.org/websvn/
 * @copyright  Copyright 2009-2012 Oregon State University (http://oregonstate.edu/)
 * @license    http://www.gnu.org/licenses/gpl-2.0-standalone.html GPLv2
 */

namespace TillikumX\Job;

use DateTime;
use Doctrine\ORM\EntityManager;
use Tillikum\Entity;
use Tillikum\Job\AbstractJob;
use Vo\DateRange;

class PropagateFacilityBookings extends AbstractJob
{
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getDescription()
    {
        return 'Propagate facility booking rates from one term to the next.';
    }

    public function getFormClass()
    {
        return 'TillikumX\Form\Job\PropagateFacilityBookings';
    }

    public function getName()
    {
        return 'Propagate facility booking rates';
    }

    public function run()
    {
        $job = $this->getJobEntity();
        $parameters = $this->getParameters();

        $oldRateParameter = new DateTime($parameters['old_rate_date']);

        $newRateStartParameter = new DateTime($parameters['new_rate_start']);
        $newRateEndParameter = new DateTime($parameters['new_rate_end']);

        $csvResource = fopen('php://temp/maxmemory:' . 1 * 1024 * 1024, 'r+');

        fputcsv(
            $csvResource,
            array(
                'OSU ID',
                'Facility group name',
                'Facility name',
                'Rate code',
                'Rate start',
                'Rate end',
            )
        );

        $result = $this->em->createQuery(
            "
            SELECT b, f, bi, r
            FROM Tillikum\Entity\Booking\Facility\Facility b
            JOIN b.facility f
            JOIN f.facility_group fg
            JOIN b.billing bi
            JOIN bi.rates r
            WHERE :oldRateDate BETWEEN r.start AND r.end AND
                  :newRateEndDate BETWEEN b.start AND b.end AND
                  fg.id != :facilityId
            GROUP BY b.person
            "
        )
            ->setParameter('oldRateDate', $oldRateParameter)
            ->setParameter('newRateEndDate', $newRateEndParameter)
            // Orchard Court
            ->setParameter('facilityId', 'd7e9b0536c8fec14ea94e94cb0e63eb9')
            ->getResult();

        $bookingCount = $rateCount = 0;
        foreach ($result as $booking) {
            $bookingCount += 1;
            $billing = $booking->billing;
            $person = $booking->person;

            foreach ($billing->rates as $rate) {
                $rateRange = new DateRange($rate->start, $rate->end);
                $newRateRange = new DateRange($newRateStartParameter, $newRateEndParameter);

                if ($newRateRange->overlaps($rateRange)) {
                    $message = new Entity\Job\Message\Message();
                    $message->job = $job;
                    $message->level = LOG_WARNING;
                    $message->message = "New rate date range for OSU ID {$person->osuid} would overlap existing rate from {$rate->start->format('Y-m-d')} to {$rate->end->format('Y-m-d')}, skipping.";

                    $this->em->persist($message);

                    continue 2;
                }
            }

            $rate = $billing->rates->filter(
                \Tillikum\Common\Booking\Bookings::createIncludedDateFilter($oldRateParameter)
            )
                ->last();

            $newRate = clone($rate);
            $newRate->start = $newRateStartParameter;
            $newRate->end = $newRateEndParameter;
            $newRate->created_by = $parameters['identity'];
            $newRate->created_at = new DateTime();
            $newRate->updated_by = $parameters['identity'];
            $newRate->updated_at = new DateTime();

            if (!$job->is_dry_run) {
                $this->em->persist($newRate);
            }

            $rateCount += 1;

            fputcsv(
                $csvResource,
                array(
                    $person->osuid,
                    $booking->facility->facility_group->getConfigOnDate($newRate->start)->name,
                    $booking->facility->getConfigOnDate($newRate->start)->name,
                    $newRate->rule->description,
                    $newRate->start->format('Y-m-d'),
                    $newRate->end->format('Y-m-d'),
                )
            );
        }

        rewind($csvResource);
        $csv = stream_get_contents($csvResource);
        fclose($csvResource);

        $attachment = new Entity\Job\Attachment\Attachment();
        $attachment->job = $job;
        $attachment->name = 'Facility booking propagation ' . date('Y-m-d'). '.csv';
        $attachment->media_type = 'text/csv';
        $attachment->attachment = $csv;
        $this->em->persist($attachment);

        $message = new Entity\Job\Message\Message();
        $message->job = $job;
        $message->level = LOG_INFO;
        $message->message = "Created {$rateCount} new rates from {$bookingCount} bookings.";
        $this->em->persist($message);

        $job->run_state = Entity\Job\Job::RUN_STATE_STOPPED;
        $job->job_state = Entity\Job\Job::JOB_STATE_SUCCESS;

        $this->em->flush();
    }
}
