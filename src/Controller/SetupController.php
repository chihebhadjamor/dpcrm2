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

        // Get the current font size setting
        $fontSizeSetting = $entityManager->getRepository(AppSettings::class)->findOneBy(['setting_name' => 'font_size']);

        // If the setting doesn't exist, create it with the default value (Medium)
        if (!$fontSizeSetting) {
            $fontSizeSetting = new AppSettings();
            $fontSizeSetting->setSettingName('font_size');
            $fontSizeSetting->setSettingValue('medium');
            $fontSizeSetting->setDescription('Application-wide font size');
            $entityManager->persist($fontSizeSetting);
            $entityManager->flush();
        }

        // Define date format options with labels and values
        $dateFormatOptions = [
            'YYYY-MM-DD' => 'Y-m-d',
            'MM/DD/YYYY' => 'm/d/Y',
            'DD/MM/YYYY' => 'd/m/Y',
            'DD.MM.YYYY' => 'd.m.Y',
            'Month DD, YYYY' => 'F j, Y'
        ];

        // Define font size options with labels and values
        $fontSizeOptions = [
            'Small' => 'small',
            'Medium' => 'medium',
            'Large' => 'large'
        ];

        // Create the form
        $form = $this->formFactory->createBuilder()
            ->add('date_format', ChoiceType::class, [
                'label' => 'Date Format',
                'choices' => $dateFormatOptions,
                'data' => $dateFormatSetting->getSettingValue(),
                'attr' => ['class' => 'form-control']
            ])
            ->add('font_size', ChoiceType::class, [
                'label' => 'Application Font Size',
                'choices' => $fontSizeOptions,
                'data' => $fontSizeSetting->getSettingValue(),
                'attr' => ['class' => 'form-control']
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Save Settings',
                'attr' => ['class' => 'btn btn-primary mt-3']
            ])
            ->getForm();

        // Create a structured settings array for the table layout
        $settingsTable = [
            [
                'name' => 'date_format',
                'label' => 'Date Format',
                'summary' => 'Configure the date format that will be used throughout the application.',
                'description' => $dateFormatSetting->getDescription(),
                'current_value' => $dateFormatSetting->getSettingValue(),
                'form_field' => 'date_format',
                'options' => $dateFormatOptions,
                'example_type' => 'date',
                'example_values' => [
                    'Y-m-d' => (new \DateTime())->format('Y-m-d'),
                    'm/d/Y' => (new \DateTime())->format('m/d/Y'),
                    'd/m/Y' => (new \DateTime())->format('d/m/Y'),
                    'd.m.Y' => (new \DateTime())->format('d.m.Y'),
                    'F j, Y' => (new \DateTime())->format('F j, Y')
                ]
            ],
            [
                'name' => 'font_size',
                'label' => 'Font Size',
                'summary' => 'Configure the font size that will be used throughout the application.',
                'description' => $fontSizeSetting->getDescription(),
                'current_value' => $fontSizeSetting->getSettingValue(),
                'form_field' => 'font_size',
                'options' => $fontSizeOptions,
                'example_type' => 'font',
                'example_values' => [
                    'small' => 'This is how text will appear with small font size.',
                    'medium' => 'This is how text will appear with medium font size.',
                    'large' => 'This is how text will appear with large font size.'
                ]
            ]
        ];

        // Handle form submission
        return $this->handleForm(
            $request,
            $form,
            function ($data) use ($entityManager, $dateFormatSetting, $fontSizeSetting) {
                // Update the date format setting
                $dateFormatSetting->setSettingValue($data['date_format']);

                // Update the font size setting
                $fontSizeSetting->setSettingValue($data['font_size']);

                $entityManager->flush();

                return $this->redirectToRoute('app_setup');
            },
            'Settings updated successfully!',
            'There was an error updating the settings.',
            'setup/index.html.twig',
            [
                'settings' => [
                    'date_format' => $dateFormatSetting,
                    'font_size' => $fontSizeSetting
                ],
                'settingsTable' => $settingsTable
            ]
        );
    }
}
