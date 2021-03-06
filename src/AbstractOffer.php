<?php

namespace Windstep\YRLGenerator;

use Windstep\YRLGenerator\Traits\FiltersArray;
use XMLWriter;

class AbstractOffer
{
    use FiltersArray;

    /** @var XMLWriter */
    protected $engine;
    protected $properties = [];
    protected $id;
    protected $sameNameProperties = [
        'metro',
        'room-space'
    ];

    public function __construct($id, ?array $data = null)
    {
        $this->id = $id;
        $this->fill($data);
    }

    protected function prepareSelf()
    {
        // Anyone could override this function to
        // prepare offer itself
    }

    public function toXMLString(): string
    {
        $this->prepareSelf();
        $this->setupEngine();
        $this->engine->openMemory();
        $this->engine->startElement('offer');
        $this->engine->writeAttribute('internal-id', $this->id);

        foreach ($this->properties as $propertyName => $value) {
            $this->createElement($propertyName, $value);
        }

        $this->engine->fullEndElement();
        return $this->engine->flush(true);
    }

    protected function setupEngine()
    {
        $this->engine = new XMLWriter();
    }

    protected function propertyHasMethod(string $propertyMethod): bool
    {
        return method_exists($this, $propertyMethod);
    }

    protected function getMethodNameFromProperty(string $propertyName): string
    {
        $snakeCase = ucwords(str_replace(['-', '_'], ' ', $propertyName));
        $studlyCase = lcfirst(str_replace(' ', '', $snakeCase));
        return "create{$studlyCase}Element";
    }

    protected function createElement(string $propertyName, $value)
    {
        $propertyMethod = $this->getMethodNameFromProperty($propertyName);
        if ($this->propertyHasMethod($propertyMethod)) {
            $this->{$propertyMethod}($value);
        } elseif (in_array($propertyName, $this->sameNameProperties)) {
            $this->createSameNameElements($propertyName, $value);
        } elseif (is_array($value)) {
            $this->writeArrayElement($propertyName, $value);
        } else {
            $this->writeElement($propertyName, $value);
        }
    }

    protected function writeArrayElement(string $propertyName, array $arrayElement)
    {
        $this->engine->startElement($propertyName);
        foreach ($arrayElement as $key => $value) {
            $this->createElement($key, $value);
        }
        $this->engine->fullEndElement();
    }

    protected function createImageElement($images)
    {
        if ((!is_array($images) && count($images) < 1) || $images == null) {
            return;
        }

        foreach ($images as $image) {
            $this->writeElement('image', $image);
        }
    }

    protected function createSameNameElements($elementName, $values)
    {
        foreach ($values as $element) {
            $this->writeArrayElement($elementName, $element);
        }
    }

    protected function writeElement(string $elementName, $value, array $attributes = [])
    {
        if (null == $value) {
            return;
        }
        $value = $this->processValue($value);

        $this->engine->startElement($elementName);
        foreach ($attributes as $attributeName => $attributeValue) {
            $this->engine->writeAttribute($attributeName, $attributeValue);
        }
        $this->engine->text($value);
        $this->engine->fullEndElement();
    }

    public function fill(?array $data = [])
    {
        if (!$data) return;
        $this->properties = $data;
    }

    protected function processValue($value)
    {
        if (gettype($value) == 'float' || gettype($value) == 'double') {
            return number_format($value, 2);
        } elseif (gettype($value) == 'boolean') {
            return $value ? 'да' : 'нет';
        }

        return $value;
    }
}
