<?php

declare(strict_types=1);

namespace OpenPix\Pix\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Sales\Api\Data\OrderInterface;
use OpenPix\Pix\Model\Pix\Pix;
use OpenPix\Pix\Model\Pix\PixParcelado;

class OpenPixPayment implements ResolverInterface
{
    /**
     * Exposes the payment instructions only when the queried order is paid by OpenPix.
     * The parent CustomerOrder resolver already validates customer ownership or the guest
     * order token before this field is resolved.
     *
     * @param array<string, mixed> $value
     * @param array<string, mixed>|null $args
     * @return array<string, string>|null
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null
    ): ?array {
        $order = $value['model'] ?? null;
        if (!$order instanceof OrderInterface) {
            return null;
        }

        $method = (string) $order->getPayment()->getMethod();
        if (!in_array($method, [Pix::CODE, PixParcelado::CODE], true)) {
            return null;
        }

        $brCode = (string) $order->getData('openpix_brcode');
        $qrCodeImage = (string) $order->getData('openpix_qrcodeimage');
        $paymentLinkUrl = (string) $order->getData('openpix_paymentlinkurl');

        if ($brCode === '' && $qrCodeImage === '' && $paymentLinkUrl === '') {
            return null;
        }

        return array_filter([
            'qr_code_image' => $qrCodeImage,
            'br_code' => $brCode,
            'payment_link_url' => $paymentLinkUrl,
        ]);
    }
}
