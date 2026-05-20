<?php

namespace Minishop\Tests\Unit\Services\Shipping;

use Minishop\Services\Shipping\CanadaPostCarrier;
use Minishop\Services\Shipping\ShipmentData;
use PHPUnit\Framework\TestCase;

class CanadaPostCarrierTest extends TestCase
{
    private CanadaPostCarrier $carrier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->carrier = new CanadaPostCarrier;
    }

    public function test_driver_key_is_canada_post(): void
    {
        $this->assertSame('canada_post', $this->carrier->driverKey());
    }

    public function test_parses_rate_response_correctly(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<price-quotes xmlns="http://www.canadapost.ca/ws/ship/rate-v4">
  <price-quote>
    <service-code>DOM.EP</service-code>
    <service-name>Expedited Parcel</service-name>
    <price-details>
      <due>12.50</due>
    </price-details>
    <service-standard>
      <expected-delivery-date>2026-04-07</expected-delivery-date>
    </service-standard>
  </price-quote>
</price-quotes>
XML;

        $reflector = new \ReflectionClass($this->carrier);
        $method = $reflector->getMethod('parseResponse');
        $method->setAccessible(true);

        $rates = $method->invoke($this->carrier, $xml);

        $this->assertCount(1, $rates);
        $rate = $rates->first();
        $this->assertSame('DOM.EP', $rate->serviceCode);
        $this->assertSame('Expedited Parcel', $rate->serviceName);
        $this->assertSame(1250, $rate->amountCents);
        $this->assertSame('2026-04-07', $rate->expectedDelivery);
        $this->assertSame('canada_post', $rate->carrier);
    }

    public function test_converts_due_amount_to_cents(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<price-quotes xmlns="http://www.canadapost.ca/ws/ship/rate-v4">
  <price-quote>
    <service-code>DOM.XP</service-code>
    <service-name>Xpresspost</service-name>
    <price-details>
      <due>25.99</due>
    </price-details>
  </price-quote>
</price-quotes>
XML;

        $reflector = new \ReflectionClass($this->carrier);
        $method = $reflector->getMethod('parseResponse');
        $method->setAccessible(true);

        $rates = $method->invoke($this->carrier, $xml);

        $this->assertSame(2599, $rates->first()->amountCents);
    }

    public function test_expected_delivery_is_null_when_not_in_response(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<price-quotes xmlns="http://www.canadapost.ca/ws/ship/rate-v4">
  <price-quote>
    <service-code>DOM.EP</service-code>
    <service-name>Expedited Parcel</service-name>
    <price-details>
      <due>12.50</due>
    </price-details>
  </price-quote>
</price-quotes>
XML;

        $reflector = new \ReflectionClass($this->carrier);
        $method = $reflector->getMethod('parseResponse');
        $method->setAccessible(true);

        $rates = $method->invoke($this->carrier, $xml);

        $this->assertNull($rates->first()->expectedDelivery);
    }

    public function test_returns_empty_collection_for_invalid_xml(): void
    {
        $reflector = new \ReflectionClass($this->carrier);
        $method = $reflector->getMethod('parseResponse');
        $method->setAccessible(true);

        $rates = $method->invoke($this->carrier, 'this is not xml');

        $this->assertTrue($rates->isEmpty());
    }

    public function test_builds_xml_using_dom_not_string_interpolation(): void
    {
        $shipment = new ShipmentData(
            originPostcode: 'K1A 0A6',
            destinationPostcode: 'V6B 2W9',
            destinationCountry: 'CA',
            weightGrams: 1000,
        );

        $reflector = new \ReflectionClass($this->carrier);
        $method = $reflector->getMethod('buildRequestXml');
        $method->setAccessible(true);

        $xml = $method->invoke($this->carrier, $shipment, '1234567');

        // Assert the XML is valid and contains expected values
        libxml_use_internal_errors(true);
        $doc = simplexml_load_string($xml);
        $this->assertNotFalse($doc, 'XML output must be valid');

        $this->assertStringContainsString('K1A0A6', $xml);
        $this->assertStringContainsString('V6B2W9', $xml);
        $this->assertStringContainsString('1.000', $xml); // 1000g → 1.000kg
    }

    public function test_builds_domestic_xml_for_canadian_destination(): void
    {
        $shipment = new ShipmentData(
            originPostcode: 'K1A 0A6',
            destinationPostcode: 'V6B 2W9',
            destinationCountry: 'CA',
            weightGrams: 500,
        );

        $reflector = new \ReflectionClass($this->carrier);
        $method = $reflector->getMethod('buildRequestXml');
        $method->setAccessible(true);

        $xml = $method->invoke($this->carrier, $shipment, null);

        $this->assertStringContainsString('<domestic>', $xml);
        $this->assertStringContainsString('<postal-code>', $xml);
        $this->assertStringNotContainsString('<usa>', $xml);
        $this->assertStringNotContainsString('<international>', $xml);
    }

    public function test_builds_usa_xml_for_us_destination(): void
    {
        $shipment = new ShipmentData(
            originPostcode: 'K1A 0A6',
            destinationPostcode: '10001',
            destinationCountry: 'US',
            weightGrams: 500,
        );

        $reflector = new \ReflectionClass($this->carrier);
        $method = $reflector->getMethod('buildRequestXml');
        $method->setAccessible(true);

        $xml = $method->invoke($this->carrier, $shipment, null);

        $this->assertStringContainsString('<usa>', $xml);
        $this->assertStringContainsString('<zip-code>', $xml);
        $this->assertStringNotContainsString('<domestic>', $xml);
        $this->assertStringNotContainsString('<international>', $xml);
    }

    public function test_builds_international_xml_for_non_ca_us_destination(): void
    {
        $shipment = new ShipmentData(
            originPostcode: 'K1A 0A6',
            destinationPostcode: 'SW1A 1AA',
            destinationCountry: 'GB',
            weightGrams: 500,
        );

        $reflector = new \ReflectionClass($this->carrier);
        $method = $reflector->getMethod('buildRequestXml');
        $method->setAccessible(true);

        $xml = $method->invoke($this->carrier, $shipment, null);

        $this->assertStringContainsString('<international>', $xml);
        $this->assertStringContainsString('<country-code>GB</country-code>', $xml);
        $this->assertStringNotContainsString('<domestic>', $xml);
        $this->assertStringNotContainsString('<usa>', $xml);
    }
}
