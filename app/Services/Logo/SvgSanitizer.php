<?php

namespace App\Services\Logo;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Whitelist sanitizer for SVG markup.
 *
 * Applied to every uploaded or imported template before it is stored, and again
 * before any SVG leaves the API, so stored assets can never carry scripts,
 * event handlers or external references into a user's browser.
 */
class SvgSanitizer
{
    private const ALLOWED_ELEMENTS = [
        'svg', 'g', 'path', 'rect', 'circle', 'ellipse', 'line', 'polyline',
        'polygon', 'image', 'text', 'tspan', 'textPath', 'defs', 'linearGradient', 'radialGradient',
        'stop', 'clipPath', 'filter', 'feDropShadow', 'title', 'desc',
    ];

    private const ALLOWED_ATTRIBUTES = [
        'id', 'd', 'href', 'startOffset', 'fill', 'fill-rule', 'clip-rule', 'stroke', 'stroke-width',
        'stroke-linecap', 'stroke-linejoin', 'stroke-dasharray', 'stroke-miterlimit',
        'paint-order', 'opacity', 'fill-opacity', 'stroke-opacity', 'transform', 'x', 'y',
        'x1', 'y1', 'x2', 'y2', 'cx', 'cy', 'r', 'rx', 'ry', 'width', 'height',
        'viewBox', 'xmlns', 'points', 'font-family', 'font-size', 'font-weight',
        'font-style', 'letter-spacing', 'text-anchor', 'dominant-baseline',
        'offset', 'stop-color', 'stop-opacity', 'gradientUnits',
        'gradientTransform', 'clip-path', 'preserveAspectRatio', 'version',
        'filter', 'dx', 'dy', 'stdDeviation', 'flood-color', 'flood-opacity',
        'role', 'aria-hidden',
    ];

    public function sanitize(string $svg): string
    {
        $source = new DOMDocument();
        $previous = libxml_use_internal_errors(true);

        $loaded = $source->loadXML($svg, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded || $source->documentElement === null) {
            return $this->emptySvg();
        }

        if ($source->documentElement->nodeName !== 'svg') {
            return $this->emptySvg();
        }

        $target = new DOMDocument('1.0', 'UTF-8');
        $root = $target->createElement('svg');
        $target->appendChild($root);

        $this->copyAttributes($source->documentElement, $root);
        $this->copyChildren($source->documentElement, $root, $target);

        if (! $root->hasAttribute('xmlns')) {
            $root->setAttribute('xmlns', 'http://www.w3.org/2000/svg');
        }

        return $target->saveXML($root) ?: $this->emptySvg();
    }

    private function copyChildren(DOMNode $from, DOMElement $to, DOMDocument $doc): void
    {
        foreach ($from->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE || $child->nodeType === XML_CDATA_SECTION_NODE) {
                $text = $child->nodeValue ?? '';

                if (trim($text) !== '') {
                    $to->appendChild($doc->createTextNode($text));
                }

                continue;
            }

            if (! $child instanceof DOMElement) {
                continue;
            }

            if (! in_array($child->nodeName, self::ALLOWED_ELEMENTS, true)) {
                continue;
            }

            $element = $doc->createElement($child->nodeName);
            $this->copyAttributes($child, $element);

            if ($child->nodeName === 'image' && ! $element->hasAttribute('href')) {
                continue;
            }

            if ($child->hasChildNodes()) {
                $this->copyChildren($child, $element, $doc);
            }

            $to->appendChild($element);
        }
    }

    private function copyAttributes(DOMElement $from, DOMElement $to): void
    {
        foreach ($from->attributes as $attribute) {
            $name = $attribute->nodeName;

            if (! in_array($name, self::ALLOWED_ATTRIBUTES, true)) {
                continue;
            }

            $value = trim($attribute->nodeValue ?? '');

            if ($name === 'href') {
                $allowedHref = str_starts_with($value, '#')
                    || str_starts_with($value, '/storage/')
                    || str_starts_with($value, '/logodesignnepal/')
                    || (bool) preg_match('/^data:image\/(png|webp|jpeg|jpg);base64,[A-Za-z0-9+\/=]+$/', $value);

                if (! $allowedHref) {
                    continue;
                }
            }

            if (preg_match('/(javascript|data:text\/html|<\s*script)/i', $value)) {
                continue;
            }

            if (str_starts_with($name, 'on')) {
                continue;
            }

            $to->setAttribute($name, $value);
        }
    }

    private function emptySvg(): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"></svg>';
    }
}
