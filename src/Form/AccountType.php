<?php

namespace App\Form;

use App\Entity\Account;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccountType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Account Name',
                'attr' => ['class' => 'form-control mb-3']
            ])
            ->add('contact', TextType::class, [
                'label' => 'Contact',
                'attr' => ['class' => 'form-control mb-3']
            ])
            ->add('website', UrlType::class, [
                'label' => 'Website',
                'required' => false,
                'attr' => ['class' => 'form-control mb-3']
            ])
            ->add('phone', TextType::class, [
                'label' => 'Phone Number',
                'required' => false,
                'attr' => ['class' => 'form-control mb-3']
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => Account::getAvailableStatuses(),
                'attr' => ['class' => 'form-select mb-3']
            ])
            ->add('priority', ChoiceType::class, [
                'label' => 'Priority',
                'choices' => Account::getAvailablePriorities(),
                'attr' => ['class' => 'form-select mb-3']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Account::class,
        ]);
    }
}
