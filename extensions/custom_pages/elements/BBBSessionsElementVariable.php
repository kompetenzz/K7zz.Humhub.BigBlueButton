<?php

namespace k7zz\humhub\bbb\extensions\custom_pages\elements;

use humhub\modules\custom_pages\modules\template\elements\BaseElementVariableIterator;

class BBBSessionsElementVariable extends BaseElementVariableIterator
{
    public function __construct(BBBSessionsElement $elementContent)
    {
        parent::__construct($elementContent);

        foreach ($elementContent->getItems() as $session) {
            $this->items[] = BBBSessionElementVariable::instance($elementContent)->setRecord($session);
        }
    }

    public function __toString(): string
    {
        $sessions = iterator_to_array($this->elementContent->getItems(), false);
        $last = count($sessions) - 1;

        $html = '<div class="bbb-list">';
        foreach ($sessions as $i => $session) {
            $html .= BBBSessionElement::renderSessionRow($session, $i === $last);
        }
        $html .= '</div>';

        return $html;
    }
}
