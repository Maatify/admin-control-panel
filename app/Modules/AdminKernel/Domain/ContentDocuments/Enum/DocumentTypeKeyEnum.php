<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\ContentDocuments\Enum;

enum DocumentTypeKeyEnum: string
{
    case TERMS = 'terms';
    case PRIVACY = 'privacy';
    case COOKIE_POLICY = 'cookie_policy';
    case REFUND_POLICY = 'refund_policy';
    case CANCELLATION_POLICY = 'cancellation_policy';
    case SHIPPING_POLICY = 'shipping_policy';
    case PAYMENT_POLICY = 'payment_policy';
    case SUBSCRIPTION_POLICY = 'subscription_policy';
    case PRICING_POLICY = 'pricing_policy';
    case DISCLAIMER = 'disclaimer';
    case GDPR_NOTICE = 'gdpr_notice';
    case DATA_PROCESSING_AGREEMENT = 'data_processing_agreement';
    case ACCEPTABLE_USE_POLICY = 'acceptable_use_policy';
    case ABOUT_US = 'about_us';
    case CONTACT_US = 'contact_us';
    case VENDOR_TERMS = 'vendor_terms';
    case AFFILIATE_TERMS = 'affiliate_terms';
    case COMMUNITY_GUIDELINES = 'community_guidelines';

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
