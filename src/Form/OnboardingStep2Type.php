<?php
// src/Form/OnboardingStep2Type.php

namespace App\Form;

use App\Entity\Progress;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Range;

class OnboardingStep2Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('primaryGoal', ChoiceType::class, [
                'label' => 'What is your primary goal?',
                'choices' => [
                    'Weight Loss' => 'weight_loss',
                    'Muscle Gain' => 'muscle_gain',
                    'Maintenance' => 'maintenance',
                    'Improve Endurance' => 'endurance',
                    'Increase Strength' => 'strength'
                ],
                'expanded' => true,
                'multiple' => false,
                'constraints' => [
                    new NotBlank(['message' => 'Please select your primary goal'])
                ]
            ])
            ->add('targetWeight', NumberType::class, [
                'label' => 'What is your target weight? (kg)',
                'attr' => [
                    'placeholder' => 'Target weight in kilograms',
                    'min' => 30,
                    'max' => 300,
                    'step' => 0.1,
                    'class' => 'form-control'
                ],
                'required' => false,
            ])
            ->add('goalTimeline', ChoiceType::class, [
                'label' => 'In what timeframe would you like to achieve this?',
                'choices' => [
                    'Per week' => 'per_week',
                    'Per month' => 'per_month',
                    'In 3 months' => 'per_3_months',
                    'In 6 months' => 'per_6_months'
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
            ])
            ->add('dailyCalorieIntake', IntegerType::class, [
                'label' => 'How many calories do you typically eat per day? (optional)',
                'attr' => [
                    'placeholder' => 'Daily calorie intake',
                    'min' => 1000,
                    'max' => 5000,
                    'class' => 'form-control'
                ],
                'required' => false,
                'constraints' => [
                    new Range(['min' => 1000, 'max' => 5000, 'notInRangeMessage' => 'Calories should be between {{ min }} and {{ max }}'])
                ]
            ])
            ->add('dailyWaterIntake', NumberType::class, [
                'label' => 'How many liters of water do you drink per day? (optional)',
                'attr' => [
                    'placeholder' => 'Water intake in liters',
                    'min' => 0.5,
                    'max' => 10,
                    'step' => 0.1,
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