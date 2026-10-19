<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Form\Type;

use MauticPlugin\MauticOmnichannelMessagingBundle\Integration\OmnichannelIntegration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Dynamic form that builds credential fields for one messaging channel.
 */
final class ChannelConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $channel = $options['channel'] ?? 'whatsapp';
        $fields  = OmnichannelIntegration::getFormFields()[$channel] ?? [];

        foreach ($fields as $field) {
            $type = match ($field['type']) {
                'bool'     => CheckboxType::class,
                'password' => PasswordType::class,
                default    => TextType::class,
            };

            $opts = [
                'label'    => $field['label'],
                'required' => $field['required'] ?? false,
                'attr'     => ['class' => 'form-control'],
            ];

            if ('bool' === $field['type']) {
                $opts['required'] = false;
                unset($opts['attr']['class']);
            }

            if ('password' === $field['type']) {
                $opts['attr']['autocomplete'] = 'new-password';
            }

            $builder->add($field['key'], $type, $opts);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'channel' => 'whatsapp',
        ]);
        $resolver->setAllowedTypes('channel', 'string');
    }

    public function getBlockPrefix(): string
    {
        return 'omnichannel_channel_config';
    }
}
