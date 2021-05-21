<?php

namespace App\Controller;

use App\Entity\ExpertBooking;
use App\Entity\ExpertMeeting;
use App\Entity\TimeSlot;
use App\Entity\User;
use App\Form\ExpertBookingType;
use App\Repository\ExpertBookingRepository;
use App\Service\Chat\AutomaticMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/expert/booking")
 */
class ExpertBookingController extends AbstractController
{
    /**
     * @Route("/{expertMeeting}/new", name="expert_booking_new", methods={"GET","POST"})
     * @Route("/{expertBooking}/edit", name="expert_booking_edit", methods={"GET","POST"})
     */
    public function form(Request $request, ?ExpertBooking $expertBooking, ?ExpertMeeting $expertMeeting): Response
    {
        //If creation get expertMeeting
        if (!$expertBooking){
            $expertBooking = new ExpertBooking();
            $expertBooking->setExpertMeeting($expertMeeting);
        }else{
            $expertMeeting = $expertBooking->getExpertMeeting();
        }

        $form = $this->createForm(ExpertBookingType::class, $expertBooking);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $expertBooking->setUser($this->getUser());
            $expertBooking->setStatus('waiting');

            $timeSlot = $expertBooking->getTimeSlot();
            $timeSlot->setStatus(true);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($expertBooking);
            $entityManager->persist($timeSlot);
            $entityManager->flush();

            return $this->redirectToRoute('dashboard');
        }

        //Get array dates and array startTimes corresponding to dates
        $dates = $this->getUniqueDates($expertMeeting->getAvailableTimeSlots());
        $startTimes = $this->getTimesById($expertMeeting->getAvailableTimeSlots(), $dates);

        return $this->render('expert_booking/form.html.twig', [
            'expertBooking' => $expertBooking,
            'expertMeeting' => $expertMeeting,
            'form' => $form->createView(),
            'edit' => $expertBooking->getId() !== null,
            'dates' => $dates,
            'startTimes' => $startTimes
        ]);
    }

    /**
     * @Route("/confirm/submit/{expertUser}/{timeSlot}", name="expert_booking_confirm_submit", options={"expose"=true})
     */
    public function confirmSubmitModal(User $expertUser, TimeSlot $timeSlot, AutomaticMessage $automaticMessage): response
    {
        //Send message to user
        $automaticMessage->fromAdvisorToUser($expertUser, 'Bonne nouvelle !<br> Une demande de RDV expert est en attente de confirmation. <a href="' . $this->generateUrl('expert_booking_list', ['status' => 'toConfirm']) . '">Cliquez ici</a>');

        return $this->render('expert_booking/modal/confirmSubmit.html.twig', [
            'timeSlotDate' => $timeSlot->getDate()->format('d/m/Y'),
            'timeSlotTimeStart' => $timeSlot->getStartTime()->format('H:i')
        ]);
    }

    /**
     * Return unique date format in array
     * @param $timesSlot
     * @return array
     */
    private function getUniqueDates($timesSlot): array
    {
        $dates = [];
        foreach($timesSlot as $timeSlot){
            if (!in_array($timeSlot->getDate()->format('d/m/Y'), $dates))
                $dates [$timeSlot->getId()] = $timeSlot->getDate()->format('d/m/Y');
        }

        return $dates;
    }

    /**
     * Return 2 dimensions array corresponding to times of each date
     */
    private function getTimesById($timesSlot, $dates): array
    {
        $startsTimes = [];
        foreach($dates as $date){
            //Create array containing date key and value times
            $startsTimes [$date] = [];
            foreach($timesSlot as $timeSlot){
                if ($timeSlot->getDate()->format('d/m/Y') === $date){
                    $startsTimes [$date][$timeSlot->getId()] =  $timeSlot->getStartTime()->format('H:i');
                }
            }
        }

        return $startsTimes;
    }

    /**
     * @Route("/list/{status}", name="expert_booking_list")
     */
    public function list($status, ExpertBookingRepository $expertBookingRepository): Response
    {
        $expertMeeting = $this->getUser()->getExpertMeetings();

        if ($status == 'toConfirm')
            $list = $expertBookingRepository->findByStatus($expertMeeting[0], 'waiting');
        elseif ($status == 'toCome')
            $list = $expertBookingRepository->findByStatus($expertMeeting[0], 'confirmed');
        elseif ($status == 'passed')
            $list = $expertBookingRepository->findByStatus($expertMeeting[0], 'confirmed');

        return $this->render('dashboard/expertBooking/list.html.twig', [
            'profile' => $this->getUser()->getProfile(),
            'list' => $list,
            'status' => $status
        ]);
    }

    /**
     * @Route("/confirm/{id}", name="expert_booking_confirm", options={"expose"=true})
     */
    public function confirm(ExpertBooking $expertBooking, EntityManagerInterface  $manager, AutomaticMessage $automaticMessage): Response
    {
        if ($expertBooking->getStatus() === 'waiting'){
            $expertBooking->setStatus('confirmed');

            $manager->persist($expertBooking);
            $manager->flush();
        }

        //Send message to user
        $automaticMessage->fromAdvisorToUser($expertBooking->getUser(), 'Bonne nouvelle !<br> Votre rendez-vous vient d\'être confirmé.');


        return new JsonResponse(['message' => 'is confirmed'],200);
    }

    /**
     * @Route("/cancel/{id}", name="expert_booking_cancel")
     */
    public function cancel(ExpertBooking $expertBooking, EntityManagerInterface  $manager): Response
    {
        $expertBooking->setStatus('canceled');

        $manager->persist($expertBooking);
        $manager->flush();

        return new JsonResponse(['message' => 'is canceled'],200);
    }
}
