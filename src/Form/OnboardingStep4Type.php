<?php
// src/Form/OnboardingStep4Type.php

namespace App\Form;

use App\Entity\Progress;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OnboardingStep4Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('bodyFatPercentage', NumberType::class, [
                'label' => 'Body fat percentage? (if known)',
                'attr' => [
                    'placeholder' => 'Body fat %',
                    'min' => 0,
                    'max' => 60,
                    'step' => 0.1,
                    'class' => 'form-control'
                ],
                'required' => false,
            ])
            ->add('muscleMass', NumberType::class, [
                'label' => 'Muscle mass? (if known)',
                'attr' => [
                    'placeholder' => 'Muscle mass in kg',
                    'min' => 0,
                    'max' => 150,
                    'step' => 0.1,
                    'class' => 'form-control'
                ],
                'required' => false,
            ])
            ->add('chestMeasurement', NumberType::class, [
                'label' => 'Chest measurement? (cm)',
                'attr' => [
                    'placeholder' => 'Chest',
                    'min' => 50,
                    'max' => 200,
                    'step' => 0.1,
                    'class' => 'form-control'
                ],
                'mapped' => false,
                'required' => false,
            ])
            ->add('waistMeasurement', NumberType::class, [
                'label' => 'Waist measurement? (cm)',
                'attr' => [
                    'placeholder' => 'Waist',
                    'min' => 40,
                    'max' => 200,
                    'step' => 0.1,
                    'class' => 'form-control'
                ],
                'mapped' => false,
                'required' => false,
            ])
            ->add('hipsMeasurement', NumberType::class, [
                'label' => 'Hips measurement? (cm)',
                'attr' => [
                    'placeholder' => 'Hips',
                    'min' => 50,
                    'max' => 200,
                    'step' => 0.1,
                    'class' => 'form-control'
                ],
                'mapped' => false,
                'required' => false,
            ])
            ->add('armsMeasurement', NumberType::class, [
                'label' => 'Arms measurement? (cm)',
                'attr' => [
                    'placeholder' => 'Arms',
                    'min' => 20,
                    'max' => 100,
                    'step' => 0.1,
                    'class' => 'form-control'
                ],
                'mapped' => false,
                'required' => false,
            ])
            ->add('thighsMeasurement', NumberType::class, [
                'label' => 'Thighs measurement? (cm)',
                'attr' => [
                    'placeholder' => 'Thighs',
                    'min' => 30,
                    'max' => 150,
                    'step' => 0.1,
                    'class' => 'form-control'
                ],
                'mapped' => false,
                'required' => false,
            ])
            ->add('healthConditions', TextareaType::class, [
                'label' => 'Any health conditions we should know about?',
                'attr' => [
                    'placeholder' => 'E.g., diabetes, high blood pressure, injuries, etc.',
                    'rows' => 3,
                    'class' => 'form-control'
                ],
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Progress::class,
        ]);
    }
}