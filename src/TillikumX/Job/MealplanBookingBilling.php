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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Tillikum\Entity;
use Tillikum\Job\AbstractJob;
use Zend\Di\Di;

class MealplanBookingBilling extends AbstractJob
{
    protected $em;
    protected $di;

    public function __construct(EntityManager $em, Di $di)
    {
        $this->di = $di;
        $this->em = $em;
    }

    public function getDescription()
    {
        return 'Assess billing for all resident meal plan bookings.';
    }

    public function getFormClass()
    {
        return 'TillikumX\Form\Job\MealplanBookingBilling';
    }

    public function getName()
    {
        return 'Meal plan booking billing assessment';
    }

    public function run()
    {
        $job = $this->getJobEntity();
        $parameters = $this->getParameters();
        $endParameter = new DateTime($parameters['end']);

        $csvResource = fopen('php://temp/maxmemory:' . 1 * 1024 * 1024, 'r+');
        $csGoldResource = fopen('php://temp/maxmemory:' . 1 * 1024 * 1024, 'r+');

        fputcsv(
            $csvResource,
            array(
                'OSU ID',
                'Family name',
                'Given name',
                'Billed rate ID',
                'Billed rate start',
                'Billed rate end',
                'Billing ID',
                'Old billed through',
                'New billed through',
                'Meal plan',
            )
        );

        fwrite($csGoldResource, "/INST=-1\r\n");
        fwrite($csGoldResource, "/DELIMITER=\",\"\r\n");
        fwrite($csGoldResource, "/FIELDS=UPDATE_MODE,PRIMARYKEYVALUE,LASTNAME,FIRSTNAME,ACTIVE,SVC1PLANNUM,SVC1ACTIVE,SVC1AMOUNT,PATRONFLAGS\r\n");

        $result = $this->em->createQuery(
            "
            SELECT b, bi, r
            FROM Tillikum\Entity\Booking\Mealplan\Mealplan b
            JOIN b.mealplan m
            JOIN b.billing bi
            JOIN bi.rates r
            WHERE bi.through < r.end OR bi.through IS NULL
            "
        )
            ->getResult();

        $raTag = $this->em->find('Tillikum\Entity\Person\Tag', $parameters['ra_tag_id']);

        if (!$raTag) {
            $message = new Entity\Job\Message\Message();
            $message->job = $job;
            $message->level = LOG_ERR;
            $message->message = "Could not look up RA tag id {$parameters['ra_tag_id']}. Aborting.";
            $this->em->persist($message);

            $job->run_state = Entity\Job\Job::RUN_STATE_STOPPED;
            $job->job_state = Entity\Job\Job::JOB_STATE_ERROR;

            $this->em->flush();

            exit(1);
        }

        $bookingCount = $eventCount = $rateCount = 0;
        $billingEvents = new ArrayCollection();
        foreach ($result as $booking) {
            $billing = $booking->billing;
            $person = $booking->person;

            $bookingCount += 1;

            $newThrough = clone($endParameter);

            foreach ($billing->rates as $rate) {
                $csGoldEvents = new ArrayCollection();
                // Nothing to do
                if ($billing->through >= $rate->end) {
                    continue;
                }

                $newThrough = min($rate->end, $endParameter);

                if ($billing->through == $newThrough) {
                    continue;
                }

                $rateCount += 1;

                if ($billing->through >= $rate->start) {
                    $creditEvent = new Entity\Billing\Event\MealplanBooking();
                    $creditEvent->person = $booking->person;
                    $creditEvent->rule = $rate->rule;
                    $creditEvent->is_processed = false;
                    $creditEvent->mealplan = $booking->mealplan;
                    $creditEvent->is_credit = true;
                    $creditEvent->start = $rate->start;
                    $creditEvent->end = min($rate->end, $billing->through);

                    $billingEvents->add($creditEvent);
                    $csGoldEvents->add(clone($creditEvent));

                    $eventCount += 1;
                }

                $chargeEvent = new Entity\Billing\Event\MealplanBooking();
                $chargeEvent->person = $booking->person;
                $chargeEvent->rule = $rate->rule;
                $chargeEvent->is_processed = false;
                $chargeEvent->mealplan = $booking->mealplan;
                $chargeEvent->is_credit = false;
                $chargeEvent->start = $rate->start;
                $chargeEvent->end = $newThrough;

                $billingEvents->add($chargeEvent);
                $csGoldEvents->add(clone($chargeEvent));

                $newThrough = min($rate->end, $endParameter);

                $eventCount += 1;

                fputcsv(
                    $csvResource,
                    array(
                        $booking->person->osuid ?: $booking->person->id,
                        $booking->person->family_name,
                        $booking->person->given_name,
                        $rate->id,
                        $rate->start->format('Y-m-d'),
                        $rate->end->format('Y-m-d'),
                        $billing->id,
                        $billing->through ? $billing->through->format('Y-m-d'): '',
                        $newThrough->format('Y-m-d'),
                        $booking->mealplan->name
                    )
                );

                $processor = $this->di->get(
                    'Tillikum\Billing\Event\Processor\BookingProcessor'
                );

                $processedAmount = 0;
                try {
                    foreach ($csGoldEvents as $event) {
                        $entries = $processor->process($event);

                        foreach ($entries as $entry) {
                            $processedAmount += $entry->amount;
                        }
                    }
                } catch (\Exception $e) {
                    $message = new Entity\Job\Message\Message();
                    $message->job = $job;
                    $message->level = LOG_WARNING;
                    $message->message = "Unable to process event ID {$event->id} for CS Gold export: " .
                                        $e->getMessage();
                    $this->em->persist($message);

                    continue;
                }

                if ($processedAmount == 0) {
                    continue;
                }

                $csGoldResult = $this->em
                    ->getRepository('TillikumX\Entity\Mealplan\CsGold')
                    ->findOneBy(array('mealplan' => $booking->mealplan));

                $planNumber = '?';
                if ($csGoldResult) {
                    $planNumber = $csGoldResult->gold_id;
                }

                $row = array(
                    'A',
                    $person->osuid,
                    $person->family_name ?: '',
                    $person->given_name ?: '',
                    'Y',
                    $planNumber,
                    'Y',
                    '++' . ($processedAmount * 100),
                    $person->tags->contains($raTag) ? '(P15 P19)' : '(P19)',
                );

                fwrite($csGoldResource, implode(',', $row) . "\r\n");
            }

            if (!$job->is_dry_run) {
                $billing->through = $newThrough;
            }
        }

        if (!$job->is_dry_run) {
            foreach ($billingEvents as $event) {
                $this->em->persist($event);
            }
        }

        rewind($csvResource);
        $csv = stream_get_contents($csvResource);
        fclose($csvResource);

        $attachment = new Entity\Job\Attachment\Attachment();
        $attachment->job = $job;
        $attachment->name = 'Meal plan booking billing ' . date('Y-m-d'). '.csv';
        $attachment->media_type = 'text/csv';
        $attachment->attachment = $csv;
        $this->em->persist($attachment);

        rewind($csGoldResource);
        $csGoldCsv = stream_get_contents($csGoldResource);
        fclose($csGoldResource);

        $attachment = new Entity\Job\Attachment\Attachment();
        $attachment->job = $job;
        $attachment->name = 'CS Gold billing export ' . date('Y-m-d'). '.csv';
        $attachment->media_type = 'text/csv';
        $attachment->attachment = $csGoldCsv;
        $this->em->persist($attachment);

        $message = new Entity\Job\Message\Message();
        $message->job = $job;
        $message->level = LOG_INFO;
        $message->message = "Processed {$rateCount} rates in {$bookingCount} bookings.";
        $this->em->persist($message);

        $message = new Entity\Job\Message\Message();
        $message->job = $job;
        $message->level = LOG_INFO;
        $message->message = "Generated {$eventCount} billing events.";
        $this->em->persist($message);

        $job->run_state = Entity\Job\Job::RUN_STATE_STOPPED;
        $job->job_state = Entity\Job\Job::JOB_STATE_SUCCESS;

        $this->em->flush();
    }
}
