<?php

namespace App\Form;

use App\Entity\NutritionPlan;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NutritionPlanType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'e.g., Weight Loss Plan'],
                'label' => 'Plan Name'
            ])
            ->add('description', TextareaType::class, [
                'attr' => ['class' => 'form-control', 'rows' => 4, 'placeholder' => 'Describe the nutrition plan...'],
                'label' => 'Description'
            ])
            ->add('duration', IntegerType::class, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Number of days'],
                'label' => 'Duration (days)'
            ])
            ->add('objective', TextareaType::class, [
                'attr' => ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'e.g., Lose weight, Gain muscle...'],
                'label' => 'Objective'
            ])
            ->add('dailyWaterIntake', IntegerType::class, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Liters per day'],
                'label' => 'Daily Water Intake (L)',
                'required' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => NutritionPlan::class,
        ]);
    }
}   