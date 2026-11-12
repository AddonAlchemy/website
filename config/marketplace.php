<?php

declare(strict_types=1);

return [

    'releases' => [
        'disk' => env('MARKETPLACE_RELEASE_DISK', 'local'),
        'directory' => 'releases',
        'extensions' => ['zip', 'jar'],
        'max_size_kb' => (int) env('MARKETPLACE_RELEASE_MAX_KB', 51200),
        'max_entries' => 5000,
        'max_uncompressed_bytes' => 536_870_912,
        'max_compression_ratio' => 150,
    ],

    'media' => [
        'disk' => env('MARKETPLACE_MEDIA_DISK', 'public'),
        'max_image_kb' => 3072,
        'max_screenshots' => 8,
    ],

    'downloads' => [
        'link_ttl_seconds' => (int) env('MARKETPLACE_DOWNLOAD_TTL', 300),
    ],

    'checkout' => [
        'auto_capture' => (bool) env('MARKETPLACE_AUTO_CAPTURE', false),
    ],

    'api' => [
        'rate_limit_per_minute' => (int) env('MARKETPLACE_API_RATE_LIMIT', 120),
    ],

    'settings' => [
        'brand_first' => 'ADDON',
        'brand_second' => 'ALCHEMY',
        'tagline' => 'Der Marktplatz für High-End Plugins, API-Erweiterungen und modulare Software-Lizenzen.',
        'accent_color' => '#88C040',
        'currency' => '€',
        'maintenance' => false,
        'maintenance_message' => 'Wir führen gerade Wartungsarbeiten durch. Bitte versuche es später erneut.',
        'max_ips' => 2,
        'subkey_max_plugins' => 3,
        'subkey_lock_days' => 14,
        'min_price_cents' => 500,
        'platform_fee_percent' => 35.0,
        'payment_methods' => [
            ['id' => 'paypal', 'label' => 'PayPal', 'percent' => 2.49, 'fixed_cents' => 35],
            ['id' => 'card', 'label' => 'Kreditkarte', 'percent' => 1.5, 'fixed_cents' => 25],
            ['id' => 'sepa', 'label' => 'SEPA-Lastschrift', 'percent' => 0.8, 'fixed_cents' => 25],
        ],
        'company_name' => '',
        'company_address' => '',
        'company_email' => '',
        'cookie_enabled' => true,
        'cookie_text' => 'Wir verwenden nur technisch notwendige Cookies, damit Login und Warenkorb funktionieren. Es findet kein Tracking zu Werbezwecken statt.',
        'legal_imprint' => <<<'MD'
            ## Impressum

            Angaben gemäß § 5 TMG

            [Firmenname]
            [Straße und Hausnummer]
            [PLZ] [Ort]
            [Land]

            ### Vertreten durch

            [Vertretungsberechtigte Person]

            ### Kontakt

            E-Mail: [kontakt@deine-domain.de]

            ### Umsatzsteuer-ID

            Umsatzsteuer-Identifikationsnummer gemäß § 27 a UStG: [DE000000000]

            ### Verbraucherstreitbeilegung

            Wir sind nicht verpflichtet und nicht bereit, an einem Streitbeilegungsverfahren vor einer Verbraucherschlichtungsstelle teilzunehmen.
            MD,
        'legal_privacy' => <<<'MD'
            ## Datenschutzerklärung

            ### 1. Verantwortlicher

            Verantwortlich für die Datenverarbeitung ist [Firmenname], [Adresse], E-Mail: [kontakt@deine-domain.de].

            ### 2. Welche Daten wir verarbeiten

            Bei der Registrierung: E-Mail-Adresse und Passwort (verschlüsselt). Bei Bestellungen: Rechnungsdaten und Bestellhistorie. Beim Betrieb: technisch notwendige Session-Cookies.

            ### 3. Zweck und Rechtsgrundlage

            Die Verarbeitung erfolgt zur Vertragserfüllung (Art. 6 Abs. 1 lit. b DSGVO) sowie zur Bereitstellung eines sicheren Dienstes (Art. 6 Abs. 1 lit. f DSGVO).

            ### 4. Weitergabe

            Eine Weitergabe erfolgt nur an Zahlungsdienstleister zur Abwicklung von Zahlungen sowie an Vendoren im Rahmen des Produkt-Supports.

            ### 5. Deine Rechte

            Du hast das Recht auf Auskunft, Berichtigung, Löschung, Einschränkung, Datenübertragbarkeit und Widerspruch. Kontakt: [kontakt@deine-domain.de].
            MD,
        'legal_terms' => <<<'MD'
            ## Allgemeine Geschäftsbedingungen (AGB)

            ### 1. Geltungsbereich

            Diese AGB gelten für alle Käufe digitaler Produkte über [Firmenname] („der Shop“).

            ### 2. Vertragsschluss

            Mit dem Abschluss der Bestellung gibst du ein verbindliches Angebot ab. Der Vertrag kommt mit der Bereitstellung des Produkts bzw. der Lizenz zustande.

            ### 3. Digitale Produkte & Lizenzen

            Erworbene Produkte werden als Lizenz über einen persönlichen Lizenzschlüssel freigeschaltet. Die Nutzung richtet sich nach den jeweiligen Produkt- und Lizenzbedingungen (u. a. IP- und Instanz-Beschränkungen).

            ### 4. Widerruf bei digitalen Inhalten

            Bei digitalen Inhalten erlischt das Widerrufsrecht, sobald mit der Ausführung begonnen wurde und du dem ausdrücklich zugestimmt hast.

            ### 5. Preise & Zahlung

            Alle Preise verstehen sich inkl. gesetzlicher Umsatzsteuer. Die Zahlung erfolgt über die angebotenen Zahlungsarten.
            MD,
        'legal_vendor_terms' => <<<'MD'
            ## Verkäufer-Bedingungen (Vendor Terms)

            ### 1. Vendor-Status

            Vendoren werden nach Prüfung durch einen Administrator freigeschaltet und dürfen eigene digitale Produkte anbieten.

            ### 2. Verantwortung für Inhalte

            Der Vendor ist allein verantwortlich für seine Produkte, Beschreibungen, Bilder und den Produkt-Support. Es dürfen keine rechtswidrigen, schädlichen oder urheberrechtsverletzenden Inhalte veröffentlicht werden.

            ### 3. Provision & Auszahlung

            Der Shop behält je Verkauf die Zahlungsgebühr sowie einen Plattform-Anteil ein (siehe Auszahlungs-Übersicht). Die Auszahlung des Netto-Betrags erfolgt an den Vendor.

            ### 4. Mindestpreis

            Produkte können gratis (0 €) oder ab dem festgelegten Mindestpreis angeboten werden.

            ### 5. Support-Pflicht

            Vendoren bearbeiten Support-Tickets zu ihren Produkten in angemessener Zeit.
            MD,
    ],

];
