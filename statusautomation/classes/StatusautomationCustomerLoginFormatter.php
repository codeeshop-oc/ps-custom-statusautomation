<?php

/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
use Symfony\Component\Translation\TranslatorInterface;

class StatusautomationCustomerLoginFormatter implements FormFormatterInterface
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function getFormat()
    {
        return [
            'back' => (new FormField())
                ->setName('back')
                ->setType('hidden'),
            'whatsapp' => (new FormField())
                ->setName('whatsapp')
                ->setType('text')
                ->setLabel($this->translator->trans('WhatsApp Number', [], 'Shop.Theme.TsWhatsapp'))
                ->setRequired(true)
                ->addConstraint('isCleanHtml')
                ->addAvailableValue(
                    'comment',
                    $this->translator->trans('Please enter a valid WhatsApp number without country code or 0 at start (9 digits only).', [], 'Modules.Statusautomation.Shop')
                ),
        ];
    }
}
