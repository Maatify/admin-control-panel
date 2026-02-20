<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\ContentDocuments\Enum;

enum DocumentTypeKeyEnum: string
{
    case TERMS = 'terms';
    case PRIVACY = 'privacy';
    case COOKIE_POLICY = 'cookie-policy';
    case REFUND_POLICY = 'refund-policy';
    case CANCELLATION_POLICY = 'cancellation-policy';
    case SHIPPING_POLICY = 'shipping-policy';
    case PAYMENT_POLICY = 'payment-policy';
    case SUBSCRIPTION_POLICY = 'subscription-policy';
    case PRICING_POLICY = 'pricing-policy';
    case DISCLAIMER = 'disclaimer';
    case GDPR_NOTICE = 'gdpr-notice';
    case DATA_PROCESSING_AGREEMENT = 'data-processing-agreement';
    case ACCEPTABLE_USE_POLICY = 'acceptable-use-policy';
    case ABOUT_US = 'about-us';
    case CONTACT_US = 'contact-us';
    case VENDOR_TERMS = 'vendor-terms';
    case AFFILIATE_TERMS = 'affiliate-terms';
    case COMMUNITY_GUIDELINES = 'community-guidelines';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $case): string => $case->value,
            self::cases()
        );
    }

    public static function fromString(string $value): self
    {
        return self::from($value);
    }

    public static function exists(string $value): bool
    {
        return null !== self::tryFrom($value);
    }

    public function label(): string
    {
        return match ($this) {
            self::TERMS => 'Terms',
            self::PRIVACY => 'Privacy',
            self::COOKIE_POLICY => 'Cookie Policy',
            self::REFUND_POLICY => 'Refund Policy',
            self::CANCELLATION_POLICY => 'Cancellation Policy',
            self::SHIPPING_POLICY => 'Shipping Policy',
            self::PAYMENT_POLICY => 'Payment Policy',
            self::SUBSCRIPTION_POLICY => 'Subscription Policy',
            self::PRICING_POLICY => 'Pricing Policy',
            self::DISCLAIMER => 'Disclaimer',
            self::GDPR_NOTICE => 'GDPR Notice',
            self::DATA_PROCESSING_AGREEMENT => 'Data Processing Agreement',
            self::ACCEPTABLE_USE_POLICY => 'Acceptable Use Policy',
            self::ABOUT_US => 'About Us',
            self::CONTACT_US => 'Contact Us',
            self::VENDOR_TERMS => 'Vendor Terms',
            self::AFFILIATE_TERMS => 'Affiliate Terms',
            self::COMMUNITY_GUIDELINES => 'Community Guidelines',
        };
    }
}
