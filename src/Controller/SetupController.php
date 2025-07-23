<?php

namespace App\Controller;

use App\Entity\AppSettings;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class SetupController extends AbstractWebController
{
    private FormFactoryInterface $formFactory;

    public function __construct(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    #[Route('/setup', name: 'app_setup')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Only allow administrators to access this page
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('You do not have permission to access this page.');
        }

        // Get the current date format setting
        $dateFormatSetting = $entityManager->getRepository(AppSettings::class)->findOneBy(['setting_name' => 'date_format']);

        // If the setting doesn't exist, create it with the default value
        if (!$dateFormatSetting) {
            $dateFormatSetting = new AppSettings();
            $dateFormatSetting->setSettingName('date_format');
            $dateFormatSetting->setSettingValue('Y-m-d');
            $dateFormatSetting->setDescription('Application-wide date format');
            $entityManager->persist($dateFormatSetting);
            $entityManager->flush();
        }

        // Create the form
        $form = $this->formFactory->createBuilder()
            ->add('date_format', ChoiceType::class, [
                'label' => 'Date Format',
                'choices' => [
                    'YYYY-MM-DD' => 'Y-m-d',
                    'MM/DD/YYYY' => 'm/d/Y',
                    'DD/MM/YYYY' => 'd/m/Y',
                    'DD.MM.YYYY' => 'd.m.Y',
                    'Month DD, YYYY' => 'F j, Y'
                ],
                'data' => $dateFormatSetting->getSettingValue(),
                'attr' => ['class' => 'form-control']
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Save Settings',
                'attr' => ['class' => 'btn btn-primary mt-3']
            ])
            ->getForm();

        // Handle form submission
        return $this->handleForm(
            $request,
            $form,
            function ($data) use ($entityManager, $dateFormatSetting) {
                // Update the date format setting
                $dateFormatSetting->setSettingValue($data['date_format']);
                $entityManager->flush();

                return $this->redirectToRoute('app_setup');
            },
            'Settings updated successfully!',
            'There was an error updating the settings.',
            'setup/index.html.twig',
            [
                'settings' => [
                    'date_format' => $dateFormatSetting
                ]
            ]
        );
    }
}
